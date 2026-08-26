<?php

namespace Database\Factories;

use App\Models\CalendarTask;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalendarTask>
 */
class CalendarTaskFactory extends Factory
{
    protected $model = CalendarTask::class;

    public function definition(): array
    {
        return [
            'title' => fake()->randomElement([
                'Купить скетчбук A5',
                'Провести приёмку оборудования',
                'Обновить прошивку роутера',
                'Списать старые мониторы',
            ]),
            'description' => fake()->optional()->sentence(),
            'user_id' => fn () => User::factory()->withRole('technician'),
            'due_at' => fake()->optional()->dateTimeBetween('now', '+2 weeks'),
            'due_all_day' => true,
            'priority' => fake()->randomElement(array_keys(CalendarTask::PRIORITIES)),
        ];
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'completed_at' => now(),
        ]);
    }
}
