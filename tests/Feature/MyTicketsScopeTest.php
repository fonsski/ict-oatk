<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Раздел «Мои заявки» (tickets.index) показывает каждому только его заявки.
 *
 * Раньше admin и master видели тут вообще все обращения системы — вкладка
 * «Мои заявки» дублировала «Все заявки» и показывала чужое. Полный список
 * для управляющих живёт отдельно, в AllTicketsController.
 */
class MyTicketsScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function ticketBy(User $author, array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            "title" => "Заявка " . uniqid(),
            "description" => "Описание не короче десяти символов.",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => $author->name,
            "reporter_phone" => $author->phone,
            "user_id" => $author->id,
        ], $overrides));
    }

    /**
     * У мастера в «Моих заявках» — только те, где он автор или исполнитель,
     * а не весь список системы.
     */
    public function test_master_sees_only_own_tickets_here(): void
    {
        $master = User::factory()->withRole("master")->create();
        $someoneElse = User::factory()->withRole("technician")->create();

        $mine = $this->ticketBy($master);
        $assignedToMe = $this->ticketBy($someoneElse, [
            "assigned_to_id" => $master->id,
        ]);
        $foreign = $this->ticketBy($someoneElse);

        $response = $this->actingAs($master)->get(route("tickets.index"));

        $response->assertOk();
        $tickets = $response->viewData("tickets");
        $ids = $tickets->pluck("id")->all();

        $this->assertContains($mine->id, $ids);
        $this->assertContains($assignedToMe->id, $ids);
        $this->assertNotContains($foreign->id, $ids, "Чужая заявка не должна попадать в «Мои заявки»");
    }

    public function test_technician_sees_only_own_tickets_here(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $other = User::factory()->withRole("technician")->create();

        $mine = $this->ticketBy($technician);
        $foreign = $this->ticketBy($other);

        $response = $this->actingAs($technician)->get(route("tickets.index"));
        $ids = $response->viewData("tickets")->pluck("id")->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($foreign->id, $ids);
    }

    /**
     * Полный список для управляющих никуда не делся — он в «Все заявки».
     */
    public function test_all_tickets_still_shows_everything_to_master(): void
    {
        $master = User::factory()->withRole("master")->create();
        $other = User::factory()->withRole("technician")->create();

        $this->ticketBy($master);
        $foreign = $this->ticketBy($other);

        $response = $this->actingAs($master)->get(route("all-tickets.index"));
        $ids = $response->viewData("tickets")->pluck("id")->all();

        $this->assertContains($foreign->id, $ids, "«Все заявки» должны показывать чужие заявки управляющему");
    }
}
