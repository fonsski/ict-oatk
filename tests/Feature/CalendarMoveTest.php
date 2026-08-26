<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Перетаскивание события на другой день (endpoint move).
 */
class CalendarMoveTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_moving_keeps_time_and_duration(): void
    {
        $master = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-26 14:00",
            "ends_at" => "2026-08-26 15:30",
        ]);

        $this->actingAs($master)
            ->postJson(route("calendar.move", $event), ["date" => "2026-08-29"])
            ->assertOk()
            ->assertJson(["ok" => true]);

        $event->refresh();
        $this->assertSame("2026-08-29 14:00", $event->starts_at->format("Y-m-d H:i"));
        $this->assertSame("2026-08-29 15:30", $event->ends_at->format("Y-m-d H:i"));
    }

    public function test_moving_to_a_precise_time_sets_start_and_keeps_duration(): void
    {
        $master = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:30", // 1.5 часа
        ]);

        $this->actingAs($master)
            ->postJson(route("calendar.move", $event), ["starts_at" => "2026-08-27 14:15"])
            ->assertOk();

        $event->refresh();
        $this->assertSame("2026-08-27 14:15", $event->starts_at->format("Y-m-d H:i"));
        $this->assertSame("2026-08-27 15:45", $event->ends_at->format("Y-m-d H:i"));
    }

    public function test_recurring_event_cannot_be_dragged(): void
    {
        $master = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->weekly("MO")->create([
            "organizer_id" => $master->id,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
        ]);

        $this->actingAs($master)
            ->postJson(route("calendar.move", $event), ["date" => "2026-08-31"])
            ->assertStatus(422);
    }

    public function test_outsider_cannot_move_event(): void
    {
        $organizer = User::factory()->withRole("technician")->create();
        $outsider = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->actingAs($outsider)
            ->postJson(route("calendar.move", $event), ["date" => "2026-08-29"])
            ->assertForbidden();
    }
}
