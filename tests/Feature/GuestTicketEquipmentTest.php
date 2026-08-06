<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Форма подачи заявки публичная, поэтому и список оборудования кабинета
 * должен открываться без входа — иначе гость выбирает кабинет и получает
 * пустой список вместо техники.
 */
class GuestTicketEquipmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        EquipmentStatus::firstOrCreate(["slug" => "working"], ["name" => "Исправно"]);
    }

    private function roomWithEquipment(): Room
    {
        $room = Room::create([
            "number" => "101",
            "name" => "Кабинет 101",
            "is_active" => true,
        ]);

        Equipment::factory()->create([
            "inventory_number" => "5550001",
            "name" => "Системный блок",
            "room_id" => $room->id,
        ]);

        return $room;
    }

    public function test_guest_can_load_equipment_for_a_room(): void
    {
        $room = $this->roomWithEquipment();

        $response = $this->getJson(
            route("api.equipment.by-room", ["room_id" => $room->id]),
        )->assertOk();

        $this->assertTrue($response->json("success"));
        $this->assertCount(1, $response->json("data"));
        $this->assertSame("5550001", $response->json("data.0.inventory_number"));
    }

    public function test_endpoint_returns_json_rather_than_a_login_page(): void
    {
        $room = $this->roomWithEquipment();

        // Раньше маршрут лежал за middleware auth и отдавал гостю HTML
        // страницы входа — форма падала на разборе JSON.
        $this->get(route("api.equipment.by-room", ["room_id" => $room->id]))
            ->assertOk()
            ->assertHeader("content-type", "application/json");
    }

    public function test_endpoint_exposes_only_the_fields_the_form_needs(): void
    {
        $room = $this->roomWithEquipment();

        $item = $this->getJson(
            route("api.equipment.by-room", ["room_id" => $room->id]),
        )->json("data.0");

        // Гостю незачем видеть учётный номер, серийник или статус.
        $this->assertSame(
            ["id", "name", "inventory_number"],
            array_keys($item),
        );
    }

    public function test_missing_room_id_is_rejected(): void
    {
        $this->getJson(route("api.equipment.by-room"))
            ->assertStatus(400)
            ->assertJson(["success" => false]);
    }

    public function test_room_without_equipment_returns_an_empty_list(): void
    {
        $room = Room::create([
            "number" => "202",
            "name" => "Кабинет 202",
            "is_active" => true,
        ]);

        $this->getJson(route("api.equipment.by-room", ["room_id" => $room->id]))
            ->assertOk()
            ->assertJson(["success" => true, "data" => []]);
    }

    public function test_signed_in_staff_still_get_the_list(): void
    {
        $room = $this->roomWithEquipment();

        $this->actingAs(User::factory()->withRole("technician")->create())
            ->getJson(route("api.equipment.by-room", ["room_id" => $room->id]))
            ->assertOk()
            ->assertJsonCount(1, "data");
    }

    public function test_guest_can_open_the_ticket_form_with_the_equipment_block(): void
    {
        $this->roomWithEquipment();

        $this->get(route("tickets.create"))
            ->assertOk()
            ->assertSee('id="equipment_id"', false)
            ->assertSee("/api/equipment/by-room", false);
    }
}
