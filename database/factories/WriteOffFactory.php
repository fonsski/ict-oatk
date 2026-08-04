<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\WriteOff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WriteOff>
 */
class WriteOffFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'АКТ-' . fake()->unique()->numerify('####'),
            'date' => fake()->dateTimeBetween('-3 months', 'now'),
            'reason' => fake()->randomElement([
                'Физический износ', 'Неремонтопригодность', 'Моральное устаревание',
            ]),
            'basis' => 'Приказ № ' . fake()->numberBetween(1, 99),
            'status' => WriteOff::STATUS_DRAFT,
            'created_by_user_id' => fn () => User::factory()->withRole('master'),
        ];
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => WriteOff::STATUS_POSTED,
            'posted_at' => now(),
        ]);
    }
}
