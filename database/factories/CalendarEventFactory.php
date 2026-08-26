<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CalendarEvent>
 */
class CalendarEventFactory extends Factory
{
    protected $model = CalendarEvent::class;

    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 week', '+3 weeks');
        $end = (clone $start)->modify('+1 hour');

        return [
            'title' => fake()->randomElement([
                'Планёрка отдела',
                'Профилактика оборудования',
                'Встреча с подрядчиком',
                'Приёмка техники',
                'Инвентаризация кабинета',
            ]),
            'description' => fake()->optional()->sentence(),
            'organizer_id' => fn () => User::factory()->withRole('master'),
            'starts_at' => $start,
            'ends_at' => $end,
            'all_day' => false,
            'location' => fake()->optional()->randomElement(['Кабинет 306', 'Мастерская', 'Актовый зал']),
            'color' => fake()->randomElement(CalendarEvent::COLORS),
            'status' => CalendarEvent::STATUS_CONFIRMED,
        ];
    }

    public function allDay(): static
    {
        return $this->state(fn (array $attributes) => [
            'all_day' => true,
            'starts_at' => (clone $attributes['starts_at'])->setTime(0, 0),
            'ends_at' => (clone $attributes['starts_at'])->setTime(23, 59),
        ]);
    }

    public function weekly(string $byday = 'MO,WE,FR'): static
    {
        return $this->state(fn (array $attributes) => [
            'recurrence_freq' => CalendarEvent::FREQ_WEEKLY,
            'recurrence_interval' => 1,
            'recurrence_byday' => $byday,
        ]);
    }

    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => CalendarEvent::STATUS_CANCELLED,
        ]);
    }
}
