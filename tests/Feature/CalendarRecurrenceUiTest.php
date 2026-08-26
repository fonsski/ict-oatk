<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Настройка повторов через форму и отмена отдельных дат серии.
 */
class CalendarRecurrenceUiTest extends TestCase
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

    public function test_weekly_recurrence_is_saved_from_the_form(): void
    {
        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Планёрка",
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 09:30",
            "recurrence_freq" => "weekly",
            "recurrence_byday" => ["MO", "WE"],
            "recurrence_end_mode" => "until",
            "recurrence_until" => "2026-10-01",
        ]);

        $this->assertDatabaseHas("calendar_events", [
            "title" => "Планёрка",
            "recurrence_freq" => "weekly",
            "recurrence_byday" => "MO,WE",
        ]);

        $event = CalendarEvent::first();
        $this->assertSame("2026-10-01", $event->recurrence_until->format("Y-m-d"));
        $this->assertNull($event->recurrence_count);
    }

    public function test_count_mode_clears_until(): void
    {
        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Каждый день 5 раз",
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 09:30",
            "recurrence_freq" => "daily",
            "recurrence_end_mode" => "count",
            "recurrence_count" => 5,
            "recurrence_until" => "2026-12-31",
        ]);

        $event = CalendarEvent::first();
        $this->assertSame(5, $event->recurrence_count);
        $this->assertNull($event->recurrence_until);
    }

    public function test_no_frequency_clears_the_whole_block(): void
    {
        $this->actingAs($this->master())->post(route("calendar.events.store"), [
            "title" => "Одиночное",
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 09:30",
            "recurrence_freq" => "",
            "recurrence_byday" => ["MO"],
            "recurrence_count" => 5,
        ]);

        $event = CalendarEvent::first();
        $this->assertNull($event->recurrence_freq);
        $this->assertNull($event->recurrence_byday);
        $this->assertNull($event->recurrence_count);
    }

    public function test_invalid_frequency_is_rejected(): void
    {
        $this->actingAs($this->master())
            ->post(route("calendar.events.store"), [
                "title" => "Кривой повтор",
                "starts_at" => "2026-08-24 09:00",
                "ends_at" => "2026-08-24 09:30",
                "recurrence_freq" => "hourly",
            ])
            ->assertSessionHasErrors("recurrence_freq");
    }

    public function test_cancelling_one_occurrence_removes_only_that_date(): void
    {
        $master = $this->master();
        $event = CalendarEvent::factory()->weekly("MO")->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-24 09:00", // понедельник
            "ends_at" => "2026-08-24 10:00",
        ]);

        // Отменяем понедельник 31 августа.
        $this->actingAs($master)
            ->post(route("calendar.cancel-occurrence", $event), ["date" => "2026-08-31"])
            ->assertRedirect();

        $this->assertTrue(
            $event->exceptions()
                ->where("is_cancelled", true)
                ->whereDate("occurrence_date", "2026-08-31")
                ->exists(),
        );

        // Эта дата исчезла из недельного вида, а серия осталась.
        $response = $this->actingAs($master)->get(route("calendar.index", [
            "view" => "week",
            "date" => "2026-08-31",
        ]));
        $response->assertOk();
        $response->assertDontSee("calendar/events/{$event->id}", false);

        // А предыдущий понедельник (24-е) на месте.
        $prev = $this->actingAs($master)->get(route("calendar.index", [
            "view" => "week",
            "date" => "2026-08-24",
        ]));
        $prev->assertSee("calendar/events/{$event->id}", false);
    }

    public function test_show_offers_cancel_for_a_specific_occurrence(): void
    {
        $master = $this->master();
        $event = CalendarEvent::factory()->weekly("MO")->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
        ]);

        $response = $this->actingAs($master)->get(route("calendar.show", [
            "event" => $event,
            "date" => "2026-08-31",
        ]));

        $response->assertOk();
        $response->assertSee("Отменить 31.08");
        $response->assertSee("Удалить всю серию");
    }

    public function test_editing_can_turn_a_single_event_into_a_recurring_one(): void
    {
        $master = $this->master();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
            "recurrence_freq" => null,
        ]);

        $this->actingAs($master)->put(route("calendar.update", $event), [
            "title" => $event->title,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
            "recurrence_freq" => "daily",
            "recurrence_end_mode" => "never",
        ]);

        $this->assertSame("daily", $event->refresh()->recurrence_freq);
    }
}
