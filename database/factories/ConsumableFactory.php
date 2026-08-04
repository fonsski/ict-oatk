<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Consumable>
 */
class ConsumableFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'Картридж HP CF226A', 'Тонер Kyocera TK-1170', 'Патч-корд UTP 2 м',
                'Батарейка AA', 'Термопаста', 'Кабель HDMI 3 м',
            ]) . ' ' . fake()->unique()->numberBetween(1, 9999),
            'category' => fake()->randomElement(['Картриджи', 'Кабели', 'Комплектующие']),
            'unit' => 'шт',
            'quantity' => fake()->numberBetween(5, 100),
            'min_quantity' => null,
        ];
    }

    /**
     * Расходник с остатком на уровне порога — для проверки подсветки.
     */
    public function lowStock(): static
    {
        return $this->state(fn (array $attributes) => [
            'quantity' => 2,
            'min_quantity' => 5,
        ]);
    }
}
