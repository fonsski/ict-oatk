<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Equipment;
use App\Models\Room;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Бронь кабинета с проверкой занятости и связи события с заявкой/оборудованием.
 */
class CalendarLinksTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function master(): User
    {
        return User::factory()->withRole("master")->create();
    }

    private function room(): Room
    {
        return Room::create(["number" => "306", "name" => "Мастерская"]);
    }

    private function ticket(): Ticket
    {
        return Ticket::create([
            "title" => "Не печатает МФУ",
            "description" => "Замятие бумаги.",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => "Иванов",
            "reporter_phone" => "+79001234567",
        ]);
    }

    public function test_booking_a_free_room_succeeds(): void
    {
        $room = $this->room();

        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Встреча",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "room_id" => $room->id,
        ])->assertRedirect();

        $this->assertDatabaseHas("calendar_events", ["room_id" => $room->id]);
    }

    public function test_booking_a_busy_room_is_blocked(): void
    {
        $room = $this->room();
        CalendarEvent::factory()->create([
            "room_id" => $room->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);

        $this->actingAs($this->master())
            ->post(route("calendar.events.store"), [
                "title" => "Наложение",
                "starts_at" => "2026-08-26 10:30",
                "ends_at" => "2026-08-26 11:30",
                "room_id" => $room->id,
            ])
            ->assertSessionHasErrors("room_id");

        // Событие с наложением не создано.
        $this->assertDatabaseMissing("calendar_events", ["title" => "Наложение"]);
    }

    public function test_busy_room_can_be_forced(): void
    {
        $room = $this->room();
        CalendarEvent::factory()->create([
            "room_id" => $room->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);

        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Всё равно",
            "starts_at" => "2026-08-26 10:30",
            "ends_at" => "2026-08-26 11:30",
            "room_id" => $room->id,
            "ignore_room_conflict" => "1",
        ])->assertRedirect();

        $this->assertDatabaseHas("calendar_events", ["title" => "Всё равно"]);
    }

    public function test_non_overlapping_times_in_same_room_are_fine(): void
    {
        $room = $this->room();
        CalendarEvent::factory()->create([
            "room_id" => $room->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);

        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Позже",
            "starts_at" => "2026-08-26 11:00",
            "ends_at" => "2026-08-26 12:00",
            "room_id" => $room->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_conflict_check_accounts_for_recurring_events(): void
    {
        $room = $this->room();
        // Планёрка каждый понедельник 09:00–10:00 в этом кабинете.
        CalendarEvent::factory()->weekly("MO")->create([
            "room_id" => $room->id,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
        ]);

        // Пробуем занять кабинет в следующий понедельник в то же время.
        $this->actingAs($this->master())
            ->post(route("calendar.events.store"), [
                "title" => "Конфликт с серией",
                "starts_at" => "2026-08-31 09:30",
                "ends_at" => "2026-08-31 10:30",
                "room_id" => $room->id,
            ])
            ->assertSessionHasErrors("room_id");
    }

    public function test_editing_does_not_conflict_with_itself(): void
    {
        $room = $this->room();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $this->master()->id,
            "room_id" => $room->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);

        $this->actingAs($event->organizer)->put(route("calendar.update", $event), [
            "title" => "Обновлено",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:30",
            "room_id" => $room->id,
        ])->assertRedirect()->assertSessionHasNoErrors();
    }

    public function test_event_links_to_a_ticket(): void
    {
        $ticket = $this->ticket();

        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Выезд по заявке",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "ticket_id" => $ticket->id,
        ]);

        $event = CalendarEvent::where("title", "Выезд по заявке")->first();
        $this->assertTrue($event->tickets->contains($ticket));
    }

    public function test_event_links_to_equipment(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Профилактика",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "equipment_ids" => [$equipment->id],
        ]);

        $event = CalendarEvent::where("title", "Профилактика")->first();
        $this->assertTrue($event->equipment->contains($equipment));
    }

    public function test_editing_can_change_the_linked_ticket(): void
    {
        $master = $this->master();
        $a = $this->ticket();
        $b = $this->ticket();
        $event = CalendarEvent::factory()->create(["organizer_id" => $master->id]);
        $event->tickets()->sync([$a->id]);

        $this->actingAs($master)->put(route("calendar.update", $event), [
            "title" => $event->title,
            "starts_at" => $event->starts_at->format("Y-m-d H:i"),
            "ends_at" => $event->ends_at->format("Y-m-d H:i"),
            "ticket_id" => $b->id,
        ]);

        $event->refresh()->load("tickets");
        $this->assertFalse($event->tickets->contains($a));
        $this->assertTrue($event->tickets->contains($b));
    }

    public function test_show_page_lists_linked_ticket(): void
    {
        $master = $this->master();
        $ticket = $this->ticket();
        $event = CalendarEvent::factory()->create(["organizer_id" => $master->id]);
        $event->tickets()->sync([$ticket->id]);

        $this->actingAs($master)
            ->get(route("calendar.show", $event))
            ->assertOk()
            ->assertSee("Заявка #{$ticket->id}");
    }
}
