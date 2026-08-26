<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Support\Calendar\OccurrenceExpander;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Разворот повторяющихся событий в экземпляры. Серия хранится одной
 * строкой — здесь проверяем, что по правилу получаются верные даты.
 */
class OccurrenceExpanderTest extends TestCase
{
    use RefreshDatabase;

    private function expand(CalendarEvent $event, string $from, string $to)
    {
        return app(OccurrenceExpander::class)->expand(
            collect([$event->load("exceptions")]),
            CarbonImmutable::parse($from)->startOfDay(),
            CarbonImmutable::parse($to)->endOfDay(),
        );
    }

    public function test_single_event_yields_one_occurrence(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 10:00",
            "ends_at" => "2026-08-10 11:00",
        ]);

        $occ = $this->expand($event, "2026-08-01", "2026-08-31");

        $this->assertCount(1, $occ);
        $this->assertSame("2026-08-10", $occ->first()->occurrenceDate());
    }

    public function test_daily_recurrence_fills_each_day(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 09:30",
            "recurrence_freq" => CalendarEvent::FREQ_DAILY,
            "recurrence_interval" => 1,
        ]);

        $occ = $this->expand($event, "2026-08-10", "2026-08-14");

        $this->assertSame(
            ["2026-08-10", "2026-08-11", "2026-08-12", "2026-08-13", "2026-08-14"],
            $occ->map->occurrenceDate()->all(),
        );
    }

    public function test_weekdays_recurrence_skips_weekend(): void
    {
        // 2026-08-10 — понедельник.
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 10:00",
            "recurrence_freq" => CalendarEvent::FREQ_WEEKDAYS,
        ]);

        $occ = $this->expand($event, "2026-08-10", "2026-08-16");

        // Пн–Пт есть, Сб (15) и Вс (16) — нет.
        $this->assertSame(
            ["2026-08-10", "2026-08-11", "2026-08-12", "2026-08-13", "2026-08-14"],
            $occ->map->occurrenceDate()->all(),
        );
    }

    public function test_weekly_recurrence_on_given_days(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 10:00",
            "recurrence_freq" => CalendarEvent::FREQ_WEEKLY,
            "recurrence_byday" => "MO,WE",
        ]);

        $occ = $this->expand($event, "2026-08-10", "2026-08-23");

        // Пн 10, Ср 12, Пн 17, Ср 19.
        $this->assertSame(
            ["2026-08-10", "2026-08-12", "2026-08-17", "2026-08-19"],
            $occ->map->occurrenceDate()->all(),
        );
    }

    public function test_recurrence_count_limits_occurrences(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 10:00",
            "recurrence_freq" => CalendarEvent::FREQ_DAILY,
            "recurrence_count" => 3,
        ]);

        $occ = $this->expand($event, "2026-08-01", "2026-08-31");

        $this->assertCount(3, $occ);
    }

    public function test_recurrence_until_stops_the_series(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 10:00",
            "recurrence_freq" => CalendarEvent::FREQ_DAILY,
            "recurrence_until" => "2026-08-12",
        ]);

        $occ = $this->expand($event, "2026-08-01", "2026-08-31");

        $this->assertSame(
            ["2026-08-10", "2026-08-11", "2026-08-12"],
            $occ->map->occurrenceDate()->all(),
        );
    }

    public function test_cancelled_occurrence_is_skipped(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 09:00",
            "ends_at" => "2026-08-10 10:00",
            "recurrence_freq" => CalendarEvent::FREQ_DAILY,
        ]);
        $event->exceptions()->create([
            "occurrence_date" => "2026-08-11",
            "is_cancelled" => true,
        ]);

        $occ = $this->expand($event, "2026-08-10", "2026-08-12");

        $this->assertSame(["2026-08-10", "2026-08-12"], $occ->map->occurrenceDate()->all());
    }

    public function test_occurrence_keeps_time_and_duration(): void
    {
        $event = CalendarEvent::factory()->create([
            "starts_at" => "2026-08-10 14:15",
            "ends_at" => "2026-08-10 15:45",
            "recurrence_freq" => CalendarEvent::FREQ_DAILY,
        ]);

        $second = $this->expand($event, "2026-08-11", "2026-08-11")->first();

        $this->assertSame("14:15", $second->startsAt->format("H:i"));
        $this->assertSame("15:45", $second->endsAt->format("H:i"));
    }
}
