<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Разграничение доступа к заявкам после перехода на публичную подачу:
 * управлять заявками может только персонал (admin/master/technician),
 * гость не имеет доступа к списку и карточкам.
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

    private function makeTicket(): Ticket
    {
        return Ticket::create([
            'title' => 'Не включается монитор',
            'description' => 'Монитор не подаёт признаков жизни после выходных.',
            'category' => 'hardware',
            'priority' => 'medium',
            'status' => 'open',
            'reporter_name' => 'Иванов Иван',
            'reporter_phone' => '+79001234567',
        ]);
    }

    public function test_guest_cannot_view_ticket(): void
    {
        $ticket = $this->makeTicket();

        // Приложение не редиректит гостя, а отдаёт страницу 401 —
        // см. обработчик AuthenticationException в bootstrap/app.php.
        $this->get(route('tickets.show', $ticket))->assertUnauthorized();
    }

    public function test_guest_cannot_open_ticket_list(): void
    {
        $this->get(route('tickets.index'))->assertUnauthorized();
    }

    public function test_technician_can_view_any_ticket(): void
    {
        $technician = User::factory()->withRole('technician')->create();
        $ticket = $this->makeTicket();

        $this->actingAs($technician)
            ->get(route('tickets.show', $ticket))
            ->assertSuccessful();
    }

    public function test_admin_can_view_and_delete_ticket(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->delete(route('tickets.destroy', $ticket))
            ->assertRedirect(route('tickets.index'));

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_technician_taking_ticket_in_work_assigns_and_updates_status(): void
    {
        $technician = User::factory()->withRole('technician')->create();
        $ticket = $this->makeTicket();

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
