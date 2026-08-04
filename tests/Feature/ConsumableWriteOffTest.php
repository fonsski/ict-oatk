<?php

namespace Tests\Feature;

use App\Models\Consumable;
use App\Models\ConsumableWriteOff;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ConsumableWriteOffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();
    }

    private function master(): User
    {
        return User::factory()->withRole('master')->create();
    }

    public function test_bulk_write_off_decreases_stock_of_every_line(): void
    {
        $first = Consumable::factory()->create(['quantity' => 20]);
        $second = Consumable::factory()->create(['quantity' => 8]);

        $this->actingAs($this->master())
            ->post(route('consumable-write-offs.store'), [
                'written_off_at' => now()->format('Y-m-d'),
                'reason' => 'Израсходовано при обслуживании',
                'items' => [
                    ['consumable_id' => $first->id, 'quantity' => 5],
                    ['consumable_id' => $second->id, 'quantity' => 3],
                ],
            ])
            ->assertRedirect();

        $this->assertSame(15, $first->refresh()->quantity);
        $this->assertSame(5, $second->refresh()->quantity);

        $writeOff = ConsumableWriteOff::first();
        $this->assertSame(2, $writeOff->items()->count());
        $this->assertSame(8, $writeOff->total_quantity);
    }

    public function test_write_off_records_reason_date_and_author(): void
    {
        $master = $this->master();
        $consumable = Consumable::factory()->create(['quantity' => 10]);

        $this->actingAs($master)->post(route('consumable-write-offs.store'), [
            'written_off_at' => '2026-07-01',
            'reason' => 'Плановая замена',
            'items' => [['consumable_id' => $consumable->id, 'quantity' => 2]],
        ]);

        $writeOff = ConsumableWriteOff::first();
        $this->assertSame('Плановая замена', $writeOff->reason);
        $this->assertSame('2026-07-01', $writeOff->written_off_at->format('Y-m-d'));
        $this->assertSame($master->id, $writeOff->written_off_by_user_id);

        $movement = $writeOff->items()->first();
        $this->assertSame(StockMovement::TYPE_OUTCOME, $movement->type);
        $this->assertSame($master->id, $movement->moved_by_user_id);
    }

    public function test_write_off_beyond_stock_is_rejected_and_rolls_back(): void
    {
        $first = Consumable::factory()->create(['quantity' => 10]);
        $second = Consumable::factory()->create(['quantity' => 1]);

        $this->actingAs($this->master())
            ->post(route('consumable-write-offs.store'), [
                'written_off_at' => now()->format('Y-m-d'),
                'items' => [
                    ['consumable_id' => $first->id, 'quantity' => 4],
                    // вторая позиция превышает остаток — весь акт должен откатиться
                    ['consumable_id' => $second->id, 'quantity' => 99],
                ],
            ])
            ->assertSessionHasErrors('items');

        $this->assertSame(10, $first->refresh()->quantity, 'Первая позиция не должна была списаться');
        $this->assertSame(1, $second->refresh()->quantity);
        $this->assertSame(0, ConsumableWriteOff::count());
        $this->assertSame(0, StockMovement::count());
    }

    public function test_issuing_consumable_to_equipment_decreases_stock(): void
    {
        $consumable = Consumable::factory()->create(['quantity' => 10]);
        $equipment = \App\Models\Equipment::factory()->create();

        $this->actingAs($this->master())
            ->post(route('consumables.issue', $consumable), [
                'equipment_id' => $equipment->id,
                'quantity' => 4,
            ])
            ->assertRedirect();

        $this->assertSame(6, $consumable->refresh()->quantity);
        $this->assertSame(
            $equipment->id,
            StockMovement::where('type', StockMovement::TYPE_OUTCOME)->first()->equipment_id,
        );
    }

    public function test_cancelling_an_issue_returns_quantity_to_stock(): void
    {
        $consumable = Consumable::factory()->create(['quantity' => 10]);
        $equipment = \App\Models\Equipment::factory()->create();

        $this->actingAs($this->master())->post(route('consumables.issue', $consumable), [
            'equipment_id' => $equipment->id,
            'quantity' => 4,
        ]);
        $movement = StockMovement::where('type', StockMovement::TYPE_OUTCOME)->first();

        $this->actingAs($this->master())
            ->delete(route('consumables.movements.destroy', [$consumable, $movement]))
            ->assertRedirect();

        $this->assertSame(10, $consumable->refresh()->quantity);
    }

    public function test_low_stock_scope_finds_consumables_at_or_below_threshold(): void
    {
        Consumable::factory()->lowStock()->create();
        Consumable::factory()->create(['quantity' => 50, 'min_quantity' => 5]);
        Consumable::factory()->create(['quantity' => 1, 'min_quantity' => null]);

        $this->assertSame(1, Consumable::lowStock()->count());
    }
}
