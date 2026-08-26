<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Виды календаря: месяц (по умолчанию), неделя и день.
 */
class CalendarViewsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_week_view_renders_and_shows_events_of_the_week(): void
    {
        $user = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $user->id,
            "title" => "Событие недели",
            "starts_at" => "2026-08-19 10:00",
            "ends_at" => "2026-08-19 11:00",
        ]);

        $response = $this->actingAs($user)->get(route("calendar.index", [
            "view" => "week",
            "date" => "2026-08-20",
        ]));

        $response->assertOk();
        $response->assertSee("Событие недели");
        $response->assertSee("calendar/events/{$event->id}", false);
    }

    public function test_day_view_shows_only_that_day(): void
    {
        $user = User::factory()->withRole("master")->create();
        CalendarEvent::factory()->create([
            "organizer_id" => $user->id,
            "title" => "Событие вторника",
            "starts_at" => "2026-08-18 09:00",
            "ends_at" => "2026-08-18 10:00",
        ]);
        CalendarEvent::factory()->create([
            "organizer_id" => $user->id,
            "title" => "Событие среды",
            "starts_at" => "2026-08-19 09:00",
            "ends_at" => "2026-08-19 10:00",
        ]);

        $response = $this->actingAs($user)->get(route("calendar.index", [
            "view" => "day",
            "date" => "2026-08-18",
        ]));

        $response->assertOk();
        $response->assertSee("Событие вторника");
        $response->assertDontSee("Событие среды");
    }

    public function test_unknown_view_falls_back_to_month(): void
    {
        $user = User::factory()->withRole("technician")->create();

        $response = $this->actingAs($user)->get(route("calendar.index", ["view" => "quarter"]));

        $response->assertOk();
        // В месячном виде есть заголовки дней недели.
        $response->assertSee("Пн");
        $response->assertSee("Вс");
    }

    public function test_declined_event_is_marked_for_the_viewer(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $viewer = User::factory()->withRole("technician")->create();
        $event = \App\Models\CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "title" => "Отклонённая встреча",
            "starts_at" => "2026-08-19 10:00",
            "ends_at" => "2026-08-19 11:00",
        ]);
        \App\Models\CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $viewer->id,
            "response" => "declined",
        ]);

        // Участник видит событие приглушённым/зачёркнутым (стиль отклонённого).
        $this->actingAs($viewer)
            ->get(route("calendar.index", ["month" => "2026-08"]))
            ->assertOk()
            ->assertSee("line-through opacity-60", false);

        // Организатор — не отклонял, для него метки нет.
        $this->actingAs($organizer)
            ->get(route("calendar.index", ["month" => "2026-08"]))
            ->assertOk()
            ->assertDontSee("line-through opacity-60", false);
    }

    public function test_week_view_switch_links_are_present(): void
    {
        $user = User::factory()->withRole("technician")->create();

        $response = $this->actingAs($user)->get(route("calendar.index", [
            "view" => "week",
            "date" => "2026-08-20",
        ]));

        $response->assertOk();
        // Переключатель ведёт на все три вида.
        $response->assertSee("view=month", false);
        $response->assertSee("view=week", false);
        $response->assertSee("view=day", false);
    }
}
