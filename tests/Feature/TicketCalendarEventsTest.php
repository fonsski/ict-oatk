<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Обратная связь: страница заявки показывает привязанные к ней события.
 */
class TicketCalendarEventsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
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

    public function test_ticket_relation_returns_linked_events(): void
    {
        $ticket = $this->ticket();
        $event = CalendarEvent::factory()->create(["title" => "Выезд по заявке"]);
        $event->tickets()->sync([$ticket->id]);

        $this->assertTrue($ticket->calendarEvents->contains($event));
    }

    public function test_ticket_page_shows_linked_events_to_staff(): void
    {
        $master = User::factory()->withRole("master")->create();
        $ticket = $this->ticket();
        $event = CalendarEvent::factory()->create([
            "title" => "Плановый выезд",
            "starts_at" => "2026-08-28 14:00",
            "ends_at" => "2026-08-28 15:00",
        ]);
        $event->tickets()->sync([$ticket->id]);

        $this->actingAs($master)
            ->get(route("tickets.show", $ticket))
            ->assertOk()
            ->assertSee("События календаря")
            ->assertSee("Плановый выезд");
    }
}
