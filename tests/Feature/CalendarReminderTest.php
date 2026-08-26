<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Напоминания о скором начале событий (команда calendar:send-reminders).
 */
class CalendarReminderTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_reminder_is_sent_when_event_is_near(): void
    {
        Notification::fake();
        Carbon::setTestNow("2026-08-26 09:35");

        $organizer = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00", // через 25 минут
            "ends_at" => "2026-08-26 11:00",
            "reminder_minutes" => 30,
        ]);

        $this->artisan("calendar:send-reminders")->assertSuccessful();

        Notification::assertSentTo($organizer, TicketNotification::class);
        $this->assertDatabaseHas("calendar_reminder_dispatches", [
            "event_id" => $event->id,
        ]);

        Carbon::setTestNow();
    }

    public function test_reminder_goes_to_participants_but_not_those_who_declined(): void
    {
        Notification::fake();
        Carbon::setTestNow("2026-08-26 09:45");

        $organizer = User::factory()->withRole("master")->create();
        $coming = User::factory()->withRole("technician")->create();
        $declined = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "reminder_minutes" => 30,
        ]);
        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $coming->id,
            "response" => "accepted",
        ]);
        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $declined->id,
            "response" => "declined",
        ]);

        $this->artisan("calendar:send-reminders");

        Notification::assertSentTo($coming, TicketNotification::class);
        Notification::assertNotSentTo($declined, TicketNotification::class);

        Carbon::setTestNow();
    }

    public function test_reminder_is_not_sent_when_event_is_far_away(): void
    {
        Notification::fake();
        Carbon::setTestNow("2026-08-26 08:00");

        $organizer = User::factory()->withRole("master")->create();
        CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00", // через 2 часа
            "ends_at" => "2026-08-26 11:00",
            "reminder_minutes" => 30,
        ]);

        $this->artisan("calendar:send-reminders");

        Notification::assertNothingSent();
        $this->assertDatabaseCount("calendar_reminder_dispatches", 0);

        Carbon::setTestNow();
    }

    public function test_reminder_is_not_sent_twice(): void
    {
        Notification::fake();
        Carbon::setTestNow("2026-08-26 09:35");

        $organizer = User::factory()->withRole("master")->create();
        CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "reminder_minutes" => 30,
        ]);

        $this->artisan("calendar:send-reminders");
        $this->artisan("calendar:send-reminders");

        $this->assertDatabaseCount("calendar_reminder_dispatches", 1);

        Carbon::setTestNow();
    }

    public function test_recurring_event_reminds_for_the_upcoming_occurrence(): void
    {
        Notification::fake();
        // 2026-08-31 — понедельник; напоминание за день (до начала < 24ч).
        Carbon::setTestNow("2026-08-30 09:05");

        $organizer = User::factory()->withRole("master")->create();
        CalendarEvent::factory()->weekly("MO")->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-24 09:00",
            "ends_at" => "2026-08-24 10:00",
            "reminder_minutes" => 1440, // за день
        ]);

        $this->artisan("calendar:send-reminders");

        Notification::assertSentTo($organizer, TicketNotification::class);
        $this->assertDatabaseCount("calendar_reminder_dispatches", 1);

        Carbon::setTestNow();
    }
}
