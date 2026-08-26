<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CalendarEventControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_calendar_is_open_to_staff(): void
    {
        $technician = User::factory()->withRole("technician")->create();

        $this->actingAs($technician)->get(route("calendar.index"))->assertOk();
    }

    public function test_guest_has_no_access_to_calendar(): void
    {
        // Гостя не пускает auth-middleware — проект отдаёт свою страницу 401.
        $this->get(route("calendar.index"))->assertStatus(401);
    }

    public function test_event_is_created_with_current_user_as_organizer(): void
    {
        $master = User::factory()->withRole("master")->create();

        $response = $this->actingAs($master)->post(route("calendar.events.store"), [
            "title" => "Планёрка отдела",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "color" => "green",
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas("calendar_events", [
            "title" => "Планёрка отдела",
            "organizer_id" => $master->id,
            "color" => "green",
        ]);
    }

    public function test_end_before_start_is_rejected(): void
    {
        $master = User::factory()->withRole("master")->create();

        $this->actingAs($master)
            ->post(route("calendar.events.store"), [
                "title" => "Задом наперёд",
                "starts_at" => "2026-08-26 12:00",
                "ends_at" => "2026-08-26 11:00",
            ])
            ->assertSessionHasErrors("ends_at");
    }

    public function test_all_day_event_spans_the_whole_day(): void
    {
        $master = User::factory()->withRole("master")->create();

        $this->actingAs($master)->post(route("calendar.events.store"), [
            "title" => "Инвентаризация",
            "all_day" => "1",
            "starts_date" => "2026-08-26",
            "ends_date" => "2026-08-26",
        ]);

        $event = CalendarEvent::first();
        $this->assertTrue($event->all_day);
        $this->assertSame("00:00", $event->starts_at->format("H:i"));
        $this->assertSame("23:59", $event->ends_at->format("H:i"));
    }

    public function test_outsider_cannot_view_someone_elses_event(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $outsider = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->actingAs($outsider)->get(route("calendar.show", $event))->assertForbidden();
    }

    public function test_participant_can_view_the_event(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $participant = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $participant->id,
        ]);

        $this->actingAs($participant)->get(route("calendar.show", $event))->assertOk();
    }

    public function test_master_sees_any_event(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $master = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->actingAs($master)->get(route("calendar.show", $event))->assertOk();
    }

    public function test_only_organizer_or_manager_can_delete(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $outsider = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->actingAs($outsider)
            ->delete(route("calendar.destroy", $event))
            ->assertForbidden();
        $this->assertDatabaseHas("calendar_events", ["id" => $event->id]);

        $this->actingAs($organizer)
            ->delete(route("calendar.destroy", $event))
            ->assertRedirect();
        $this->assertDatabaseMissing("calendar_events", ["id" => $event->id]);
    }

    /**
     * Техник видит в календаре свои события и те, куда его позвали, но не
     * чужие. Проверяем через набор данных, попавший в сетку.
     */
    public function test_month_grid_is_scoped_to_the_viewer(): void
    {
        $viewer = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();

        $mine = CalendarEvent::factory()->create([
            "organizer_id" => $viewer->id,
            "starts_at" => "2026-08-15 10:00",
            "ends_at" => "2026-08-15 11:00",
        ]);
        $foreign = CalendarEvent::factory()->create([
            "organizer_id" => $other->id,
            "starts_at" => "2026-08-15 12:00",
            "ends_at" => "2026-08-15 13:00",
        ]);

        $response = $this->actingAs($viewer)->get(route("calendar.index", ["month" => "2026-08"]));

        $response->assertOk();
        $response->assertSee("calendar/events/{$mine->id}", false); // ссылка на своё событие есть
        $response->assertDontSee("calendar/events/{$foreign->id}", false);
    }
}
