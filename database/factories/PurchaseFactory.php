<?php

namespace Database\Factories;

use App\Models\Purchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Purchase>
 */
class PurchaseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'number' => 'ЗАК-' . fake()->unique()->numerify('####'),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'supplier' => fake()->randomElement([
                'ООО «Комус»', 'ООО «Ситилинк»', 'АО «Меркурий»', 'ИП Иванов И.И.',
            ]),
            'status' => Purchase::STATUS_DRAFT,
            'total_sum' => 0,
            'created_by_user_id' => fn () => User::factory()->withRole('master'),
        ];
    }

    public function posted(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => Purchase::STATUS_POSTED,
            'posted_at' => now(),
        ]);
    }
}
