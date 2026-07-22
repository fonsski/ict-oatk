<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Мягкое удаление заявок: удаление больше не теряет данные,
 * корзина доступна управляющим ролям, безвозвратное удаление — только админу.
 */
class TicketSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function makeTicket(?User $owner = null): Ticket
    {
        return Ticket::create([
            'title' => 'Не работает принтер',
            'description' => 'Принтер в кабинете 210 не печатает.',
            'category' => 'hardware',
            'priority' => 'medium',
            'status' => 'open',
            'user_id' => $owner?->id,
        ]);
    }

    public function test_destroy_soft_deletes_instead_of_erasing(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)->delete(route('tickets.destroy', $ticket));

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
        $this->assertNotNull(Ticket::withTrashed()->find($ticket->id));
    }

    public function test_soft_deleted_ticket_disappears_from_normal_queries(): void
    {
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->assertNull(Ticket::find($ticket->id));
        $this->assertSame(0, Ticket::count());
        $this->assertSame(1, Ticket::withTrashed()->count());
    }

    public function test_soft_deleted_ticket_is_not_viewable(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($admin)
            ->get(route('tickets.show', $ticket->id))
            ->assertNotFound();
    }

    public function test_admin_can_open_trash(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($admin)
            ->get(route('tickets.trashed'))
            ->assertOk()
            ->assertSee('Не работает принтер', false);
    }

    public function test_master_can_open_trash(): void
    {
        $master = User::factory()->withRole('master')->create();

        $this->actingAs($master)->get(route('tickets.trashed'))->assertOk();
    }

    public function test_technician_cannot_open_trash(): void
    {
        $technician = User::factory()->withRole('technician')->create();

        $this->actingAs($technician)
            ->get(route('tickets.trashed'))
            ->assertForbidden();
    }

    public function test_regular_user_cannot_open_trash(): void
    {
        $user = User::factory()->withRole('user')->create();

        $this->actingAs($user)->get(route('tickets.trashed'))->assertForbidden();
    }

    public function test_trash_link_is_shown_only_to_managing_roles(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $technician = User::factory()->withRole('technician')->create();

        $this->actingAs($admin)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertSee(route('tickets.trashed'), false);

        $this->actingAs($technician)
            ->get(route('tickets.index'))
            ->assertOk()
            ->assertDontSee(route('tickets.trashed'), false);
    }

    public function test_admin_can_restore_ticket(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($admin)->post(route('tickets.restore', $ticket->id));

        $this->assertNotSoftDeleted('tickets', ['id' => $ticket->id]);
        $this->assertNotNull(Ticket::find($ticket->id));
    }

    public function test_restore_leaves_a_system_comment(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($admin)->post(route('tickets.restore', $ticket->id));

        $this->assertDatabaseHas('ticket_comments', [
            'ticket_id' => $ticket->id,
            'content' => 'Заявка восстановлена из корзины',
            'is_system' => true,
        ]);
    }

    public function test_technician_cannot_restore_ticket(): void
    {
        $technician = User::factory()->withRole('technician')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($technician)
            ->post(route('tickets.restore', $ticket->id))
            ->assertForbidden();

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_admin_can_force_delete_with_comments(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();

        TicketComment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $admin->id,
            'content' => 'Комментарий к заявке',
        ]);

        $ticket->delete();

        $this->actingAs($admin)->delete(
            route('tickets.force-delete', $ticket->id),
        );

        $this->assertDatabaseMissing('tickets', ['id' => $ticket->id]);
        $this->assertDatabaseMissing('ticket_comments', [
            'ticket_id' => $ticket->id,
        ]);
    }

    public function test_master_cannot_force_delete(): void
    {
        $master = User::factory()->withRole('master')->create();
        $ticket = $this->makeTicket();
        $ticket->delete();

        $this->actingAs($master)
            ->delete(route('tickets.force-delete', $ticket->id))
            ->assertForbidden();

        $this->assertSoftDeleted('tickets', ['id' => $ticket->id]);
    }

    public function test_force_delete_requires_ticket_to_be_trashed_first(): void
    {
        $admin = User::factory()->withRole('admin')->create();
        $ticket = $this->makeTicket();

        $this->actingAs($admin)
            ->delete(route('tickets.force-delete', $ticket->id))
            ->assertRedirect(route('tickets.trashed'));

        $this->assertDatabaseHas('tickets', ['id' => $ticket->id]);
    }
}
