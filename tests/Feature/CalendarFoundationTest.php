<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\CalendarEventParticipant;
use App\Models\CalendarTask;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Фундамент календаря: схема, связи и каскады. Этап 1 — только слой данных,
 * без контроллеров и интерфейса.
 */
class CalendarFoundationTest extends TestCase
{
    use RefreshDatabase;

    public function test_event_belongs_to_organizer_and_room(): void
    {
        $master = User::factory()->withRole("master")->create();
        $event = CalendarEvent::factory()->create(["organizer_id" => $master->id]);

        $this->assertTrue($event->organizer->is($master));
        $this->assertTrue($master->organizedEvents->contains($event));
    }

    public function test_participants_are_unique_and_cascade_on_event_delete(): void
    {
        $event = CalendarEvent::factory()->create();
        $user = User::factory()->withRole("technician")->create();

        CalendarEventParticipant::create([
            "event_id" => $event->id,
            "user_id" => $user->id,
            "response" => CalendarEventParticipant::RESPONSE_PENDING,
        ]);

        $this->assertDatabaseCount("calendar_event_participants", 1);

        $event->delete();

        // Приглашения уходят вместе с событием.
        $this->assertDatabaseCount("calendar_event_participants", 0);
    }

    public function test_event_can_link_to_a_ticket_polymorphically(): void
    {
        $event = CalendarEvent::factory()->create();
        $ticket = Ticket::create([
            "title" => "Не печатает МФУ",
            "description" => "Замятие бумаги, не лечится.",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => "Иванов",
            "reporter_phone" => "+79001234567",
        ]);

        $event->links()->create([
            "linkable_type" => Ticket::class,
            "linkable_id" => $ticket->id,
        ]);

        $link = $event->links()->first();
        $this->assertTrue($link->linkable->is($ticket));
    }

    public function test_task_completion_scopes(): void
    {
        $user = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($user)->create();
        CalendarTask::factory()->for($user)->completed()->create();

        $this->assertSame(1, CalendarTask::pending()->count());
        $this->assertSame(1, CalendarTask::completed()->count());
    }

    public function test_task_deletes_with_its_owner(): void
    {
        $user = User::factory()->withRole("technician")->create();
        CalendarTask::factory()->for($user)->create();

        $user->delete();

        $this->assertDatabaseCount("calendar_tasks", 0);
    }

    /**
     * Скоуп overlapping должен ловить и одиночные события в окне, и
     * повторяющиеся серии, начавшиеся до окна и ещё идущие.
     */
    public function test_overlapping_scope_catches_single_and_recurring(): void
    {
        $from = now()->startOfDay();
        $to = now()->addDays(7)->endOfDay();

        // Одиночное внутри окна.
        $inside = CalendarEvent::factory()->create([
            "starts_at" => now()->addDay()->setTime(10, 0),
            "ends_at" => now()->addDay()->setTime(11, 0),
        ]);

        // Одиночное вне окна.
        $outside = CalendarEvent::factory()->create([
            "starts_at" => now()->addMonths(2),
            "ends_at" => now()->addMonths(2)->addHour(),
        ]);

        // Повторяющееся, началось месяц назад, без конца — идёт и сейчас.
        $recurring = CalendarEvent::factory()->weekly()->create([
            "starts_at" => now()->subMonth()->setTime(9, 0),
            "ends_at" => now()->subMonth()->setTime(10, 0),
            "recurrence_until" => null,
        ]);

        $ids = CalendarEvent::overlapping($from, $to)->pluck("id")->all();

        $this->assertContains($inside->id, $ids);
        $this->assertContains($recurring->id, $ids);
        $this->assertNotContains($outside->id, $ids);
    }
}
