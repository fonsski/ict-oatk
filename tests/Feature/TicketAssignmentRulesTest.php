<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Кто кому может назначать заявку.
 *
 * Распоряжаться чужой работой — дело руководителя: администратор и
 * заведующий назначают кого угодно. Техник разбирает заявки сам: берёт
 * свободную себе и может от своей отказаться, но перекидывать заявки
 * коллегам не может.
 */
class TicketAssignmentRulesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function ticket(array $attributes = []): Ticket
    {
        return Ticket::create(array_merge([
            "title" => "Не включается монитор",
            "description" => "Описание",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => "Иванов Иван",
            "reporter_phone" => "+79001234567",
        ], $attributes));
    }

    public function test_technician_can_take_a_free_ticket(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket();

        $this->actingAs($technician)
            ->post(route("tickets.assign", $ticket), [
                "assigned_to_id" => $technician->id,
            ])
            ->assertRedirect();

        $this->assertSame($technician->id, $ticket->refresh()->assigned_to_id);
    }

    public function test_technician_can_release_their_own_ticket(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $technician->id]);

        $this->actingAs($technician)
            ->post(route("tickets.assign", $ticket), ["assigned_to_id" => null])
            ->assertRedirect();

        $this->assertNull($ticket->refresh()->assigned_to_id);
    }

    public function test_technician_cannot_hand_a_ticket_to_a_colleague(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $colleague = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $technician->id]);

        $this->actingAs($technician)->post(route("tickets.assign", $ticket), [
            "assigned_to_id" => $colleague->id,
        ]);

        $this->assertSame(
            $technician->id,
            $ticket->refresh()->assigned_to_id,
            "Заявка не должна была уйти коллеге",
        );
    }

    public function test_technician_cannot_take_a_ticket_from_someone_else(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $busy = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $busy->id]);

        $this->actingAs($technician)->post(route("tickets.assign", $ticket), [
            "assigned_to_id" => $technician->id,
        ]);

        $this->assertSame($busy->id, $ticket->refresh()->assigned_to_id);
    }

    public function test_master_can_reassign_between_people(): void
    {
        $master = User::factory()->withRole("master")->create();
        $from = User::factory()->withRole("technician")->create();
        $to = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $from->id]);

        $this->actingAs($master)
            ->post(route("tickets.assign", $ticket), [
                "assigned_to_id" => $to->id,
            ])
            ->assertRedirect();

        $this->assertSame($to->id, $ticket->refresh()->assigned_to_id);
    }

    public function test_admin_can_reassign_between_people(): void
    {
        $admin = User::factory()->withRole("admin")->create();
        $from = User::factory()->withRole("technician")->create();
        $to = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $from->id]);

        $this->actingAs($admin)->post(route("tickets.assign", $ticket), [
            "assigned_to_id" => $to->id,
        ]);

        $this->assertSame($to->id, $ticket->refresh()->assigned_to_id);
    }

    /**
     * Быстрое назначение из списка всех заявок подчиняется тем же правилам —
     * иначе ограничение обходилось бы через другую кнопку.
     */
    public function test_quick_assign_applies_the_same_rule(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $colleague = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket(["assigned_to_id" => $technician->id]);

        $this->actingAs($technician)
            ->postJson(route("all-tickets.quick-assign", $ticket), [
                "assigned_to_id" => $colleague->id,
            ])
            ->assertForbidden();

        $this->assertSame($technician->id, $ticket->refresh()->assigned_to_id);
    }

    public function test_quick_assign_still_lets_a_technician_take_free_work(): void
    {
        $technician = User::factory()->withRole("technician")->create();
        $ticket = $this->ticket();

        $this->actingAs($technician)
            ->postJson(route("all-tickets.quick-assign", $ticket), [
                "assigned_to_id" => $technician->id,
            ])
            ->assertOk();

        $this->assertSame($technician->id, $ticket->refresh()->assigned_to_id);
    }
}
