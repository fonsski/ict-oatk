<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\Equipment;
use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use App\Models\Purchase;
use App\Models\PurchaseItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PurchasePostingTest extends TestCase
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

    public function test_creating_purchase_computes_line_and_total_sums(): void
    {
        $consumable = Consumable::factory()->create();

        $this->actingAs($this->master())
            ->post(route('purchases.store'), [
                'number' => 'ЗАК-100',
                'date' => now()->format('Y-m-d'),
                'supplier' => 'ООО «Поставщик»',
                'items' => [
                    [
                        'item_type' => 'consumable',
                        'consumable_id' => $consumable->id,
                        'name' => $consumable->name,
                        'quantity' => 10,
                        'unit_price' => 250.50,
                    ],
                ],
            ])
            ->assertRedirect();

        $purchase = Purchase::first();
        $this->assertSame('2505.00', $purchase->total_sum);
        $this->assertSame('2505.00', $purchase->items()->first()->sum);
        $this->assertTrue($purchase->isDraft());
    }

    public function test_posting_credits_consumable_stock_with_an_income_movement(): void
    {
        $consumable = Consumable::factory()->create(['quantity' => 4]);
        $purchase = Purchase::factory()->create();
        $purchase->items()->create([
            'item_type' => PurchaseItem::TYPE_CONSUMABLE,
            'consumable_id' => $consumable->id,
            'name' => $consumable->name,
            'quantity' => 6,
            'unit_price' => 100,
            'sum' => 600,
        ]);

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase))
            ->assertRedirect();

        $this->assertSame(10, $consumable->refresh()->quantity);

        $movement = StockMovement::first();
        $this->assertSame(StockMovement::TYPE_INCOME, $movement->type);
        $this->assertSame(6, $movement->quantity);
        $this->assertSame($purchase->id, $movement->purchase_id);
        $this->assertTrue($purchase->refresh()->isPosted());
    }

    /**
     * Позиция с оборудованием и её id — для передачи введённых номеров.
     */
    private function equipmentPurchase(int $quantity = 3): Purchase
    {
        $category = EquipmentCategory::firstOrCreate(
            ['name' => 'Монитор'],
            ['slug' => 'monitor'],
        );
        $purchase = Purchase::factory()->create();
        $purchase->items()->create([
            'item_type' => PurchaseItem::TYPE_EQUIPMENT,
            'equipment_category_id' => $category->id,
            'name' => 'Монитор Dell 24"',
            'quantity' => $quantity,
            'unit_price' => 15000,
            'sum' => 15000 * $quantity,
        ]);

        return $purchase;
    }

    public function test_posting_creates_units_with_the_inventory_numbers_entered_by_hand(): void
    {
        $purchase = $this->equipmentPurchase();
        $item = $purchase->items()->first();

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase), [
                'inventory_numbers' => [
                    $item->id => ['1001', '1002', '1003'],
                ],
            ])
            ->assertRedirect();

        $created = Equipment::where('name', 'Монитор Dell 24"')->get();
        $this->assertCount(3, $created);
        $this->assertEqualsCanonicalizing(
            ['1001', '1002', '1003'],
            $created->pluck('inventory_number')->all(),
        );
        // Каждая единица помнит, по какой закупке пришла.
        $this->assertTrue($created->every(fn ($unit) => $unit->purchase_id === $purchase->id));
    }

    public function test_posting_requires_a_number_for_every_unit(): void
    {
        $purchase = $this->equipmentPurchase();
        $item = $purchase->items()->first();

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase), [
                'inventory_numbers' => [$item->id => ['1001', '1002']],
            ])
            ->assertSessionHasErrors("inventory_numbers.{$item->id}");

        $this->assertSame(0, Equipment::count());
        $this->assertTrue($purchase->refresh()->isDraft());
    }

    public function test_posting_rejects_non_numeric_inventory_number(): void
    {
        $purchase = $this->equipmentPurchase(1);
        $item = $purchase->items()->first();

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase), [
                'inventory_numbers' => [$item->id => ['ABC-1']],
            ])
            ->assertSessionHasErrors("inventory_numbers.{$item->id}.0");

        $this->assertSame(0, Equipment::count());
    }

    public function test_posting_rejects_number_already_used_in_inventory(): void
    {
        $existing = Equipment::factory()->create(['inventory_number' => '5005']);
        $purchase = $this->equipmentPurchase(1);
        $item = $purchase->items()->first();

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase), [
                'inventory_numbers' => [$item->id => ['5005']],
            ])
            ->assertSessionHasErrors("inventory_numbers.{$item->id}.0");

        $this->assertSame(1, Equipment::count());
        $this->assertSame($existing->id, Equipment::first()->id);
    }

    public function test_posting_rejects_duplicate_numbers_within_one_purchase(): void
    {
        $purchase = $this->equipmentPurchase(2);
        $item = $purchase->items()->first();

        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase), [
                'inventory_numbers' => [$item->id => ['7007', '7007']],
            ])
            ->assertSessionHasErrors('inventory_numbers');

        $this->assertSame(0, Equipment::count());
    }

    public function test_posting_form_lists_a_field_per_unit(): void
    {
        $purchase = $this->equipmentPurchase(2);
        $item = $purchase->items()->first();

        $response = $this->actingAs($this->master())
            ->get(route('purchases.post.form', $purchase))
            ->assertOk()
            ->assertSee('Монитор Dell 24&quot;', false);

        $this->assertSame(
            2,
            substr_count($response->getContent(), "inventory_numbers[{$item->id}][]"),
        );
    }

    public function test_purchase_cannot_be_posted_twice(): void
    {
        $consumable = Consumable::factory()->create(['quantity' => 0]);
        $purchase = Purchase::factory()->create();
        $purchase->items()->create([
            'item_type' => PurchaseItem::TYPE_CONSUMABLE,
            'consumable_id' => $consumable->id,
            'name' => $consumable->name,
            'quantity' => 5,
            'unit_price' => 10,
            'sum' => 50,
        ]);

        $this->actingAs($this->master())->post(route('purchases.post', $purchase));
        $this->actingAs($this->master())
            ->post(route('purchases.post', $purchase))
            ->assertSessionHasErrors('purchase');

        // Остаток не должен пополниться дважды.
        $this->assertSame(5, $consumable->refresh()->quantity);
    }

    public function test_posted_purchase_cannot_be_edited(): void
    {
        $purchase = Purchase::factory()->posted()->create();

        $this->actingAs($this->master())
            ->get(route('purchases.edit', $purchase))
            ->assertForbidden();
    }

    public function test_technician_cannot_create_purchase(): void
    {
        $this->actingAs(User::factory()->withRole('technician')->create())
            ->post(route('purchases.store'), [
                'number' => 'ЗАК-999',
                'date' => now()->format('Y-m-d'),
                'supplier' => 'ООО «Поставщик»',
                'items' => [[
                    'item_type' => 'equipment',
                    'name' => 'Ноутбук',
                    'quantity' => 1,
                    'unit_price' => 1000,
                ]],
            ])
            ->assertForbidden();

        $this->assertSame(0, Purchase::count());
    }
}
