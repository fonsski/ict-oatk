<?php

namespace Database\Factories;

use App\Models\EquipmentCategory;
use App\Models\EquipmentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Equipment>
 */
class EquipmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Системный блок', 'Монитор', 'Принтер', 'МФУ', 'Проектор', 'ИБП',
            ]),
            'inventory_number' => fake()->unique()->numerify('##########'),
            'status_id' => fn () => EquipmentStatus::firstOrCreate(
                ['slug' => 'working'],
                ['name' => 'Исправно'],
            )->id,
            'category_id' => fn () => EquipmentCategory::firstOrCreate(
                ['name' => 'Компьютер'],
                ['slug' => 'kompyuter'],
            )->id,
            'has_warranty' => false,
        ];
    }

    /**
     * Единица, уже списанная по акту.
     */
    public function writtenOff(): static
    {
        return $this->state(fn (array $attributes) => [
            'status_id' => EquipmentStatus::firstOrCreate(
                ['slug' => 'decommissioned'],
                ['name' => 'Списано'],
            )->id,
            'written_off_at' => now(),
        ]);
    }
}
