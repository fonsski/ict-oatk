<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\OperatingSystem;
use App\Models\Room;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentOperatingSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
        EquipmentStatus::firstOrCreate(['slug' => 'working'], ['name' => 'Исправно']);
    }

    private function master(): User
    {
        return User::factory()->withRole('master')->create();
    }

    private function pcCategory(): EquipmentCategory
    {
        return EquipmentCategory::firstOrCreate(
            ['name' => 'Компьютер'],
            ['slug' => 'kompyuter', 'has_operating_system' => true],
        );
    }

    private function monitorCategory(): EquipmentCategory
    {
        return EquipmentCategory::firstOrCreate(
            ['name' => 'Монитор'],
            ['slug' => 'monitor', 'has_operating_system' => false],
        );
    }

    private function os(string $name = 'Windows 11 Pro', string $family = 'Windows'): OperatingSystem
    {
        return OperatingSystem::create([
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name),
            'family' => $family,
            'is_active' => true,
        ]);
    }

    public function test_master_can_add_an_operating_system_to_the_catalogue(): void
    {
        $this->actingAs($this->master())
            ->post(route('operating-systems.store'), [
                'name' => 'Astra Linux SE',
                'family' => 'Linux',
                'is_active' => 1,
            ])
            ->assertRedirect(route('operating-systems.index'));

        $this->assertDatabaseHas('operating_systems', [
            'name' => 'Astra Linux SE',
            'family' => 'Linux',
        ]);
    }

    public function test_cyrillic_name_still_gets_a_usable_slug(): void
    {
        $this->actingAs($this->master())->post(route('operating-systems.store'), [
            'name' => 'РЕД ОС',
            'is_active' => 1,
        ]);

        $slug = OperatingSystem::where('name', 'РЕД ОС')->value('slug');
        $this->assertNotSame('', $slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $slug);
    }

    public function test_duplicate_operating_system_name_is_rejected(): void
    {
        $this->os('Windows 10 Pro');

        $this->actingAs($this->master())
            ->post(route('operating-systems.store'), ['name' => 'Windows 10 Pro'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, OperatingSystem::count());
    }

    public function test_technician_cannot_manage_the_catalogue(): void
    {
        $this->actingAs(User::factory()->withRole('technician')->create())
            ->post(route('operating-systems.store'), ['name' => 'Ubuntu 24.04'])
            ->assertForbidden();

        $this->assertSame(0, OperatingSystem::count());
    }

    public function test_operating_system_is_saved_for_a_computer(): void
    {
        $os = $this->os();

        $this->actingAs($this->master())
            ->post(route('equipment.store'), [
                'inventory_number' => '900001',
                'category_id' => $this->pcCategory()->id,
                'operating_system_id' => $os->id,
                'status_id' => EquipmentStatus::where('slug', 'working')->value('id'),
            ])
            ->assertRedirect();

        $this->assertSame($os->id, Equipment::first()->operating_system_id);
    }

    public function test_operating_system_is_dropped_for_a_category_that_does_not_use_one(): void
    {
        $os = $this->os();

        $this->actingAs($this->master())->post(route('equipment.store'), [
            'inventory_number' => '900002',
            'category_id' => $this->monitorCategory()->id,
            // Монитору ОС не положена — даже если значение подсунули напрямую.
            'operating_system_id' => $os->id,
            'status_id' => EquipmentStatus::where('slug', 'working')->value('id'),
        ]);

        $this->assertNull(Equipment::first()->operating_system_id);
    }

    public function test_changing_category_to_one_without_os_clears_the_link(): void
    {
        $os = $this->os();
        $equipment = Equipment::factory()->create([
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => $os->id,
        ]);

        $this->actingAs($this->master())->put(route('equipment.update', $equipment), [
            'inventory_number' => $equipment->inventory_number,
            'category_id' => $this->monitorCategory()->id,
            'status_id' => $equipment->status_id,
        ]);

        $this->assertNull($equipment->refresh()->operating_system_id);
    }

    public function test_deleting_an_operating_system_keeps_equipment_but_clears_the_link(): void
    {
        $os = $this->os();
        $equipment = Equipment::factory()->create([
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => $os->id,
        ]);

        $this->actingAs($this->master())
            ->delete(route('operating-systems.destroy', $os))
            ->assertRedirect();

        $this->assertNotNull($equipment->refresh());
        $this->assertNull($equipment->operating_system_id);
    }

    public function test_list_can_be_filtered_by_operating_system(): void
    {
        $windows = $this->os('Windows 11 Pro');
        $linux = $this->os('Astra Linux', 'Linux');

        $withWindows = Equipment::factory()->create([
            'inventory_number' => '111111',
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => $windows->id,
        ]);
        Equipment::factory()->create([
            'inventory_number' => '222222',
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => $linux->id,
        ]);

        $this->actingAs($this->master())
            ->get(route('equipment.index', ['operating_system_id' => $windows->id]))
            ->assertOk()
            ->assertSee($withWindows->inventory_number)
            ->assertDontSee('222222');
    }

    public function test_list_can_be_filtered_by_missing_operating_system(): void
    {
        $os = $this->os();
        Equipment::factory()->create([
            'inventory_number' => '333333',
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => $os->id,
        ]);
        Equipment::factory()->create([
            'inventory_number' => '444444',
            'category_id' => $this->pcCategory()->id,
            'operating_system_id' => null,
        ]);

        $this->actingAs($this->master())
            ->get(route('equipment.index', ['operating_system_id' => 'none']))
            ->assertOk()
            ->assertSee('444444')
            ->assertDontSee('333333');
    }

    public function test_sorting_by_room_groups_units_and_orders_them_inside_a_room(): void
    {
        $first = Room::create(['number' => '101', 'name' => 'Кабинет 101', 'is_active' => true]);
        $second = Room::create(['number' => '202', 'name' => 'Кабинет 202', 'is_active' => true]);

        Equipment::factory()->create(['inventory_number' => '500', 'room_id' => $second->id]);
        Equipment::factory()->create(['inventory_number' => '300', 'room_id' => $first->id]);
        Equipment::factory()->create(['inventory_number' => '200', 'room_id' => $first->id]);
        $noRoom = Equipment::factory()->create(['inventory_number' => '100', 'room_id' => null]);

        $response = $this->actingAs($this->master())
            ->get(route('equipment.index', ['sort' => 'room']))
            ->assertOk();

        $order = $response->viewData('equipment')->pluck('inventory_number')->all();

        // Кабинет 101 (внутри — по инв. номеру), затем 202, без кабинета — в конце.
        $this->assertSame(['200', '300', '500', '100'], $order);
        $this->assertSame($noRoom->inventory_number, end($order));
    }

    public function test_sorting_by_operating_system_puts_units_without_one_last(): void
    {
        $windows = $this->os('Windows 11 Pro');
        $linux = $this->os('Astra Linux', 'Linux');

        Equipment::factory()->create(['inventory_number' => '700', 'operating_system_id' => $windows->id]);
        Equipment::factory()->create(['inventory_number' => '600', 'operating_system_id' => $linux->id]);
        Equipment::factory()->create(['inventory_number' => '800', 'operating_system_id' => null]);

        $order = $this->actingAs($this->master())
            ->get(route('equipment.index', ['sort' => 'operating_system']))
            ->assertOk()
            ->viewData('equipment')
            ->pluck('inventory_number')
            ->all();

        // Linux идёт раньше Windows по семейству, техника без ОС — последней.
        $this->assertSame(['600', '700', '800'], $order);
    }

    public function test_live_search_respects_the_selected_sort(): void
    {
        $room = Room::create(['number' => '101', 'name' => 'Кабинет 101', 'is_active' => true]);
        // Номера намеренно «неразметочные»: короткие вроде 800/900 совпадают
        // с классами Tailwind (text-gray-900) и ломают поиск по подстроке.
        Equipment::factory()->create(['inventory_number' => '7770002', 'room_id' => $room->id]);
        Equipment::factory()->create(['inventory_number' => '7770001', 'room_id' => $room->id]);

        $html = $this->actingAs($this->master())
            ->getJson(route('equipment.search', ['sort' => 'room']))
            ->assertOk()
            ->json('html');

        $this->assertLessThan(
            strpos($html, '7770002'),
            strpos($html, '7770001'),
            'При сортировке по кабинетам внутри кабинета порядок должен быть по инв. номеру',
        );
    }
}
