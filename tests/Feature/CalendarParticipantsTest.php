<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\User;
use App\Notifications\TicketNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Участники события и ответы на приглашения (RSVP).
 */
class CalendarParticipantsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_creating_an_event_invites_the_chosen_participants(): void
    {
        Notification::fake();

        $master = User::factory()->withRole("master")->create();
        $a = User::factory()->withRole("technician")->create();
        $b = User::factory()->withRole("technician")->create();

        $this->actingAs($master)->post(route("calendar.events.store"), [
            "title" => "Планёрка",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "participant_ids" => [$a->id, $b->id],
        ]);

        $event = CalendarEvent::first();
        $this->assertDatabaseHas("calendar_event_participants", [
            "event_id" => $event->id,
            "user_id" => $a->id,
            "response" => CalendarEventParticipant::RESPONSE_PENDING,
        ]);

        // Оба приглашённых получили уведомление со ссылкой на событие.
        foreach ([$a, $b] as $person) {
            Notification::assertSentTo($person, TicketNotification::class, function ($n) use ($event) {
                $payload = $n->toArray(null);
                return str_contains($payload["link"] ?? "", "/calendar/events/{$event->id}");
            });
        }
    }

    public function test_organizer_is_never_added_as_a_participant(): void
    {
        Notification::fake();

        $master = User::factory()->withRole("master")->create();

        $this->actingAs($master)->post(route("calendar.events.store"), [
            "title" => "Сам с собой",
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "participant_ids" => [$master->id],
        ]);

        $this->assertDatabaseCount("calendar_event_participants", 0);
    }

    public function test_participant_can_respond_and_organizer_is_notified(): void
    {
        Notification::fake();

        $organizer = User::factory()->withRole("master")->create();
        $participant = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $participant->id,
        ]);

        $this->actingAs($participant)
            ->post(route("calendar.respond", $event), ["response" => "accepted"])
            ->assertRedirect(route("calendar.show", $event));

        $this->assertDatabaseHas("calendar_event_participants", [
            "event_id" => $event->id,
            "user_id" => $participant->id,
            "response" => CalendarEventParticipant::RESPONSE_ACCEPTED,
        ]);

        // Организатор получил уведомление об ответе.
        Notification::assertSentTo($organizer, TicketNotification::class);
    }

    public function test_non_participant_cannot_respond(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $outsider = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);

        $this->actingAs($outsider)
            ->post(route("calendar.respond", $event), ["response" => "accepted"])
            ->assertForbidden();
    }

    public function test_invalid_response_is_rejected(): void
    {
        $organizer = User::factory()->withRole("master")->create();
        $participant = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $participant->id,
        ]);

        $this->actingAs($participant)
            ->post(route("calendar.respond", $event), ["response" => "может_быть"])
            ->assertSessionHasErrors("response");
    }

    public function test_editing_adds_and_removes_participants(): void
    {
        Notification::fake();

        $organizer = User::factory()->withRole("master")->create();
        $stays = User::factory()->withRole("technician")->create();
        $removed = User::factory()->withRole("technician")->create();
        $added = User::factory()->withRole("technician")->create();

        $event = CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);
        foreach ([$stays, $removed] as $u) {
            CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $u->id]);
        }

        $this->actingAs($organizer)->put(route("calendar.update", $event), [
            "title" => $event->title,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
            "participant_ids" => [$stays->id, $added->id],
        ]);

        $this->assertDatabaseHas("calendar_event_participants", ["event_id" => $event->id, "user_id" => $stays->id]);
        $this->assertDatabaseHas("calendar_event_participants", ["event_id" => $event->id, "user_id" => $added->id]);
        $this->assertDatabaseMissing("calendar_event_participants", ["event_id" => $event->id, "user_id" => $removed->id]);

        // Приглашение уходит только новому участнику, не остававшемуся.
        Notification::assertSentTo($added, TicketNotification::class);
        Notification::assertNotSentTo($stays, TicketNotification::class);
    }

    public function test_moving_the_time_notifies_existing_participants(): void
    {
        Notification::fake();

        $organizer = User::factory()->withRole("master")->create();
        $participant = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create([
            "organizer_id" => $organizer->id,
            "starts_at" => "2026-08-26 10:00",
            "ends_at" => "2026-08-26 11:00",
        ]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $participant->id]);

        $this->actingAs($organizer)->put(route("calendar.update", $event), [
            "title" => $event->title,
            "starts_at" => "2026-08-26 15:00",
            "ends_at" => "2026-08-26 16:00",
            "participant_ids" => [$participant->id],
        ]);

        // Время сдвинулось — уже приглашённого предупредили.
        Notification::assertSentTo($participant, TicketNotification::class);
    }

    public function test_deleting_an_event_notifies_participants(): void
    {
        Notification::fake();

        $organizer = User::factory()->withRole("master")->create();
        $participant = User::factory()->withRole("technician")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $organizer->id]);
        CalendarEventParticipant::create(["event_id" => $event->id, "user_id" => $participant->id]);

        $this->actingAs($organizer)->delete(route("calendar.destroy", $event));

        Notification::assertSentTo($participant, TicketNotification::class);
    }
}
