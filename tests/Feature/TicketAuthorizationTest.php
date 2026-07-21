<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Проверяет разграничение доступа к заявкам в TicketController:
 * обычный пользователь работает только со своими заявками,
 * персонал (admin/master/technician) — со всеми.
 */
class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Очередь в тестах синхронная, поэтому WebSocketNotificationListener
        // отрабатывает прямо в запросе и пытается достучаться до WS-сервера.
        // Без заглушки каждый такой тест ждёт таймаута.
        Http::fake();
    }

    private function makeTicket(?User $owner = null): Ticket
    {
        return Ticket::create([
            'title' => 'Не включается монитор',
            'description' => 'Монитор не подаёт признаков жизни после выходных.',
            'category' => 'hardware',
            'priority' => 'medium',
            'status' => 'open',
            'user_id' => $owner?->id,
        ]);
    }

    public function test_guest_cannot_view_ticket(): void
    {
        $ticket = $this->makeTicket();

        // Приложение не редиректит гостя, а отдаёт страницу 401 —
        // см. обработчик AuthenticationException в bootstrap/app.php.
        $this->get(route('tickets.show', $ticket))->assertUnauthorized();
    }

    public function test_user_cannot_view_foreign_ticket(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $intruder = User::factory()->withRole('user')->create();

        $ticket = $this->makeTicket($owner);

        $this->actingAs($intruder)
            ->get(route('tickets.show', $ticket))
            ->assertForbidden();
    }

    public function test_user_can_view_own_ticket(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $ticket = $this->makeTicket($owner);

        $this->actingAs($owner)
            ->get(route('tickets.show', $ticket))
            ->assertSuccessful();
    }

    public function test_technician_can_view_any_ticket(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $technician = User::factory()->withRole('technician')->create();

        $ticket = $this->makeTicket($owner);

        $this->actingAs($technician)
            ->get(route('tickets.show', $ticket))
            ->assertSuccessful();
    }

    public function test_user_cannot_delete_foreign_ticket(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $intruder = User::factory()->withRole('user')->create();

        $ticket = $this->makeTicket($owner);

        $this->actingAs($intruder)
            ->delete(route('tickets.destroy', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }

    public function test_user_cannot_comment_on_foreign_ticket(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $intruder = User::factory()->withRole('user')->create();

        $ticket = $this->makeTicket($owner);

        $this->actingAs($intruder)
            ->post(route('tickets.comment.store', $ticket), [
                'content' => 'Пытаюсь прокомментировать чужую заявку',
            ])
            ->assertForbidden();
    }

    public function test_regular_user_cannot_take_ticket_in_work(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $ticket = $this->makeTicket($owner);

        $this->actingAs($owner)
            ->post(route('tickets.start', $ticket))
            ->assertForbidden();

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'open',
        ]);
    }

    public function test_technician_taking_ticket_in_work_assigns_and_updates_status(): void
    {
        $owner = User::factory()->withRole('user')->create();
        $technician = User::factory()->withRole('technician')->create();

        $ticket = $this->makeTicket($owner);

        $this->actingAs($technician)->post(route('tickets.start', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'in_progress',
            'assigned_to_id' => $technician->id,
        ]);
    }

    public function test_closed_ticket_cannot_be_taken_in_work(): void
    {
        $technician = User::factory()->withRole('technician')->create();
        $ticket = $this->makeTicket();
        $ticket->update(['status' => 'closed']);

        $this->actingAs($technician)->post(route('tickets.start', $ticket));

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'status' => 'closed',
        ]);
    }
}
