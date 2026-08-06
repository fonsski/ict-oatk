<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Список исполнителей для назначения заявки.
 *
 * Запрос падал с ошибкой SQL: фильтр шёл по колонке "active", которой в
 * таблице нет (она называется is_active). Из-за этого окно назначения
 * исполнителя всегда показывало «не удалось загрузить список».
 */
class TechnicianListTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    public function test_staff_can_load_the_list_of_technicians(): void
    {
        $master = User::factory()->withRole("master")->create();
        User::factory()->withRole("technician")->create(["name" => "Техник Один"]);

        $response = $this->actingAs($master)
            ->getJson(route("api.users.technicians"))
            ->assertOk();

        $names = collect($response->json("technicians"))->pluck("name");
        $this->assertTrue($names->contains("Техник Один"));
    }

    public function test_inactive_staff_are_not_offered(): void
    {
        $master = User::factory()->withRole("master")->create();
        User::factory()->withRole("technician")->inactive()->create([
            "name" => "Уволенный Техник",
        ]);

        $names = collect(
            $this->actingAs($master)
                ->getJson(route("api.users.technicians"))
                ->json("technicians"),
        )->pluck("name");

        $this->assertFalse($names->contains("Уволенный Техник"));
    }

    /**
     * При переназначении выбирать того же человека повторно незачем.
     */
    public function test_currently_assigned_person_can_be_excluded(): void
    {
        $master = User::factory()->withRole("master")->create();
        $assigned = User::factory()->withRole("technician")->create([
            "name" => "Уже Назначенный",
        ]);
        User::factory()->withRole("technician")->create(["name" => "Свободный"]);

        $ids = collect(
            $this->actingAs($master)
                ->getJson(route("api.users.technicians", ["exclude" => $assigned->id]))
                ->json("technicians"),
        )->pluck("id");

        $this->assertFalse($ids->contains($assigned->id));
        $this->assertGreaterThan(0, $ids->count());
    }

    public function test_guest_cannot_read_the_list(): void
    {
        $this->getJson(route("api.users.technicians"))->assertUnauthorized();
    }

    /**
     * Интерфейсу нужен идентификатор исполнителя, чтобы исключить его из
     * списка и показать «Переназначить» вместо «Назначить».
     */
    public function test_ticket_feed_exposes_the_assignee_id(): void
    {
        $master = User::factory()->withRole("master")->create();
        $technician = User::factory()->withRole("technician")->create();

        $ticket = \App\Models\Ticket::create([
            "title" => "Не включается монитор",
            "description" => "Описание",
            "category" => "hardware",
            "priority" => "medium",
            "status" => "open",
            "reporter_name" => "Иванов Иван",
            "reporter_phone" => "+79001234567",
            "assigned_to_id" => $technician->id,
        ]);

        $tickets = $this->actingAs($master)
            ->getJson(route("all-tickets.api"))
            ->assertOk()
            ->json("tickets");

        $row = collect($tickets)->firstWhere("id", $ticket->id);

        $this->assertSame($technician->id, $row["assigned_to_id"]);
        $this->assertSame($technician->name, $row["assigned_to_name"]);
    }
}
