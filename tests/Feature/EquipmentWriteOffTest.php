<?php

namespace Tests\Feature;

use App\Models\Equipment;
use App\Models\EquipmentStatus;
use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EquipmentWriteOffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Http::fake();

        EquipmentStatus::firstOrCreate(['slug' => 'working'], ['name' => 'Исправно']);
        EquipmentStatus::firstOrCreate(['slug' => 'decommissioned'], ['name' => 'Списано']);
    }

    private function master(): User
    {
        return User::factory()->withRole('master')->create();
    }

    public function test_master_can_create_act_covering_many_units(): void
    {
        $equipment = Equipment::factory()->count(3)->create();

        $this->actingAs($this->master())
            ->post(route('write-offs.store'), [
                'date' => now()->format('Y-m-d'),
                'reason' => 'Физический износ',
                'basis' => 'Приказ № 7',
                'equipment_ids' => $equipment->pluck('id')->all(),
            ])
            ->assertRedirect();

        $writeOff = WriteOff::first();
        $this->assertNotNull($writeOff);
        $this->assertSame(3, $writeOff->items()->count());
        $this->assertTrue($writeOff->isDraft());
    }

    public function test_posting_act_marks_every_unit_as_written_off(): void
    {
        $equipment = Equipment::factory()->count(3)->create();
        $writeOff = WriteOff::factory()->create();
        foreach ($equipment as $item) {
            $writeOff->items()->create(['equipment_id' => $item->id]);
        }

        $this->actingAs($this->master())
            ->post(route('write-offs.post', $writeOff))
            ->assertRedirect();

        $decommissionedId = EquipmentStatus::where('slug', 'decommissioned')->value('id');

        foreach ($equipment as $item) {
            $item->refresh();
            $this->assertSame($decommissionedId, $item->status_id);
            $this->assertSame($writeOff->id, $item->write_off_id);
            $this->assertSame(
                $writeOff->date->format('Y-m-d'),
                $item->written_off_at->format('Y-m-d'),
            );
        }

        $this->assertTrue($writeOff->refresh()->isPosted());
    }

    public function test_draft_act_leaves_equipment_statuses_untouched(): void
    {
        $equipment = Equipment::factory()->create();
        $originalStatus = $equipment->status_id;

        $writeOff = WriteOff::factory()->create();
        $writeOff->items()->create(['equipment_id' => $equipment->id]);

        $this->assertSame($originalStatus, $equipment->refresh()->status_id);
        $this->assertNull($equipment->write_off_id);
    }

    public function test_act_cannot_be_posted_twice(): void
    {
        $equipment = Equipment::factory()->create();
        $writeOff = WriteOff::factory()->create();
        $writeOff->items()->create(['equipment_id' => $equipment->id]);

        $this->actingAs($this->master())->post(route('write-offs.post', $writeOff));

        $this->actingAs($this->master())
            ->post(route('write-offs.post', $writeOff))
            ->assertSessionHasErrors('write_off');
    }

    public function test_technician_cannot_create_write_off_act(): void
    {
        $equipment = Equipment::factory()->create();

        $this->actingAs(User::factory()->withRole('technician')->create())
            ->post(route('write-offs.store'), [
                'date' => now()->format('Y-m-d'),
                'reason' => 'Износ',
                'equipment_ids' => [$equipment->id],
            ])
            ->assertForbidden();

        $this->assertSame(0, WriteOff::count());
    }

    public function test_act_requires_at_least_one_unit(): void
    {
        $this->actingAs($this->master())
            ->post(route('write-offs.store'), [
                'date' => now()->format('Y-m-d'),
                'reason' => 'Износ',
                'equipment_ids' => [],
            ])
            ->assertSessionHasErrors('equipment_ids');
    }
}
