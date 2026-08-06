<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\OperatingSystem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Смена ОС прямо в карточке оборудования.
 *
 * Отдельно проверяем, что признак «указывать ОС» доживает до рабочей
 * системы: миграция, добавившая его, отрабатывает раньше сидера категорий,
 * поэтому при установке с нуля обновлять ей было нечего — поле выбора ОС
 * не появлялось нигде.
 */
class EquipmentOperatingSystemCardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        EquipmentStatus::firstOrCreate(["slug" => "working"], ["name" => "Исправно"]);
    }

    private function computer(): Equipment
    {
        $category = EquipmentCategory::firstOrCreate(
            ["name" => "Компьютер"],
            ["slug" => "kompyuter", "has_operating_system" => true],
        );

        return Equipment::factory()->create(["category_id" => $category->id]);
    }

    public function test_seeder_marks_computers_and_laptops_as_needing_an_os(): void
    {
        $this->seed(\Database\Seeders\EquipmentCategorySeeder::class);

        foreach (["Компьютер", "Ноутбук"] as $name) {
            $this->assertTrue(
                EquipmentCategory::where("name", $name)->value("has_operating_system"),
                "Категория «{$name}» должна требовать указания ОС",
            );
        }

        $this->assertFalse(
            (bool) EquipmentCategory::where("name", "Монитор")->value("has_operating_system"),
        );
    }

    public function test_seeder_can_run_twice_without_duplicating_categories(): void
    {
        $this->seed(\Database\Seeders\EquipmentCategorySeeder::class);
        $firstRun = EquipmentCategory::count();

        $this->seed(\Database\Seeders\EquipmentCategorySeeder::class);

        $this->assertSame($firstRun, EquipmentCategory::count());
    }

    /**
     * Повторный прогон сидера чинит флаг у категорий, заведённых до того,
     * как признак появился.
     */
    public function test_seeder_backfills_the_flag_on_existing_categories(): void
    {
        EquipmentCategory::create([
            "name" => "Компьютер",
            "slug" => "kompyuter",
            "has_operating_system" => false,
        ]);

        $this->seed(\Database\Seeders\EquipmentCategorySeeder::class);

        $this->assertTrue(
            EquipmentCategory::where("name", "Компьютер")->value("has_operating_system"),
        );
    }

    public function test_os_can_be_set_from_the_equipment_card(): void
    {
        $equipment = $this->computer();
        $os = OperatingSystem::create([
            "name" => "Windows 11 Pro",
            "slug" => "windows-11-pro",
            "family" => "Windows",
            "is_active" => true,
        ]);

        $this->actingAs(User::factory()->withRole("master")->create())
            ->put(route("equipment.operating-system.update", $equipment), [
                "operating_system_id" => $os->id,
            ])
            ->assertRedirect();

        $this->assertSame($os->id, $equipment->refresh()->operating_system_id);
    }

    public function test_os_can_be_cleared_from_the_card(): void
    {
        $os = OperatingSystem::create([
            "name" => "Ubuntu 24.04 LTS",
            "slug" => "ubuntu-2404",
            "family" => "Linux",
            "is_active" => true,
        ]);
        $equipment = $this->computer();
        $equipment->update(["operating_system_id" => $os->id]);

        $this->actingAs(User::factory()->withRole("master")->create())
            ->put(route("equipment.operating-system.update", $equipment), [
                "operating_system_id" => "",
            ])
            ->assertRedirect();

        $this->assertNull($equipment->refresh()->operating_system_id);
    }

    public function test_category_without_os_is_rejected(): void
    {
        $monitor = EquipmentCategory::firstOrCreate(
            ["name" => "Монитор"],
            ["slug" => "monitor", "has_operating_system" => false],
        );
        $equipment = Equipment::factory()->create(["category_id" => $monitor->id]);
        $os = OperatingSystem::create([
            "name" => "Windows 10 Pro",
            "slug" => "windows-10-pro",
            "is_active" => true,
        ]);

        $this->actingAs(User::factory()->withRole("master")->create())
            ->put(route("equipment.operating-system.update", $equipment), [
                "operating_system_id" => $os->id,
            ])
            ->assertSessionHasErrors("operating_system_id");

        $this->assertNull($equipment->refresh()->operating_system_id);
    }

    public function test_card_shows_the_picker_to_staff(): void
    {
        $equipment = $this->computer();
        OperatingSystem::create([
            "name" => "Windows 11 Pro",
            "slug" => "windows-11-pro",
            "is_active" => true,
        ]);

        $this->actingAs(User::factory()->withRole("technician")->create())
            ->get(route("equipment.show", $equipment))
            ->assertOk()
            ->assertSee("Операционная система")
            ->assertSee('name="operating_system_id"', false);
    }
}
