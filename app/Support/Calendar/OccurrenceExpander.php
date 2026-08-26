<?php

namespace App\Support\Calendar;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Разворачивает события в экземпляры на заданном отрезке дат.
 *
 * Повторяющееся событие хранится одной строкой; здесь оно превращается в
 * набор экземпляров по своему правилу. Одиночное событие даёт ровно один
 * экземпляр, если пересекает отрезок. Отклонения серии (отменённые и
 * перенесённые даты) учитываются из calendar_event_exceptions.
 */
class OccurrenceExpander
{
    /** Защита от бесконечной генерации, если правило задано без границы. */
    private const MAX_OCCURRENCES = 366;

    /**
     * @param  Collection<int, CalendarEvent>  $events
     * @return Collection<int, EventOccurrence>
     */
    public function expand(Collection $events, CarbonImmutable $from, CarbonImmutable $to): Collection
    {
        $occurrences = collect();

        foreach ($events as $event) {
            if (!$event->isRecurring()) {
                $this->addSingle($occurrences, $event, $from, $to);
                continue;
            }

            $this->addRecurring($occurrences, $event, $from, $to);
        }

        return $occurrences->sortBy(fn (EventOccurrence $o) => $o->startsAt->getTimestamp())->values();
    }

    private function addSingle(Collection $out, CalendarEvent $event, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $start = CarbonImmutable::parse($event->starts_at);
        $end = CarbonImmutable::parse($event->ends_at);

        if ($start->gt($to) || $end->lt($from)) {
            return;
        }

        $out->push(new EventOccurrence($event, $start, $end, $event->title));
    }

    private function addRecurring(Collection $out, CalendarEvent $event, CarbonImmutable $from, CarbonImmutable $to): void
    {
        $seriesStart = CarbonImmutable::parse($event->starts_at);
        $duration = $seriesStart->diffInSeconds(CarbonImmutable::parse($event->ends_at));

        // Верхняя граница генерации — конец окна, но не позже recurrence_until.
        $hardEnd = $to;
        if ($event->recurrence_until) {
            $until = CarbonImmutable::parse($event->recurrence_until)->endOfDay();
            $hardEnd = $until->lt($hardEnd) ? $until : $hardEnd;
        }

        // Отклонения серии по дате исходного экземпляра.
        $exceptions = $event->exceptions->keyBy(
            fn ($ex) => CarbonImmutable::parse($ex->occurrence_date)->toDateString(),
        );

        $interval = max(1, (int) $event->recurrence_interval);
        $count = 0; // сколько экземпляров серии уже породили (для recurrence_count)
        $cursor = $seriesStart;

        for ($guard = 0; $guard < self::MAX_OCCURRENCES; $guard++) {
            if ($cursor->gt($hardEnd)) {
                break;
            }
            if ($event->recurrence_count !== null && $count >= $event->recurrence_count) {
                break;
            }

            if ($this->matchesRule($event, $cursor, $seriesStart, $interval)) {
                $count++;

                $occStart = $cursor->setTime(
                    (int) $seriesStart->format("H"),
                    (int) $seriesStart->format("i"),
                );
                $occEnd = $occStart->addSeconds($duration);

                $key = $occStart->toDateString();
                $exception = $exceptions->get($key);

                $skip = $exception && $exception->is_cancelled;
                $withinWindow = !($occStart->gt($to) || $occEnd->lt($from));

                if (!$skip && $withinWindow) {
                    if ($exception && $exception->starts_at) {
                        $occStart = CarbonImmutable::parse($exception->starts_at);
                        $occEnd = $exception->ends_at
                            ? CarbonImmutable::parse($exception->ends_at)
                            : $occStart->addSeconds($duration);
                    }

                    $out->push(new EventOccurrence(
                        $event,
                        $occStart,
                        $occEnd,
                        $exception && $exception->title ? $exception->title : $event->title,
                    ));
                }
            }

            $cursor = $cursor->addDay();
        }
    }

    /**
     * Приходится ли на эту дату экземпляр серии по её правилу.
     */
    private function matchesRule(CalendarEvent $event, CarbonImmutable $day, CarbonImmutable $seriesStart, int $interval): bool
    {
        if ($day->lt($seriesStart->startOfDay())) {
            return false;
        }

        return match ($event->recurrence_freq) {
            CalendarEvent::FREQ_DAILY => $seriesStart->startOfDay()->diffInDays($day->startOfDay()) % $interval === 0,

            CalendarEvent::FREQ_WEEKDAYS => !$day->isWeekend(),

            CalendarEvent::FREQ_WEEKLY => $this->matchesWeekly($event, $day, $seriesStart, $interval),

            CalendarEvent::FREQ_MONTHLY => (int) $day->format("d") === (int) $seriesStart->format("d")
                && $seriesStart->diffInMonths($day) % $interval === 0,

            default => false,
        };
    }

    private function matchesWeekly(CalendarEvent $event, CarbonImmutable $day, CarbonImmutable $seriesStart, int $interval): bool
    {
        // Каждые N недель считаем от недели старта серии.
        if ($seriesStart->startOfWeek()->diffInWeeks($day->startOfWeek()) % $interval !== 0) {
            return false;
        }

        $days = $this->weekdaysOf($event, $seriesStart);

        return in_array($this->twoLetter($day), $days, true);
    }

    /** @return array<int, string> двухбуквенные коды дней недели (MO,TU,…) */
    private function weekdaysOf(CalendarEvent $event, CarbonImmutable $seriesStart): array
    {
        if (!$event->recurrence_byday) {
            return [$this->twoLetter($seriesStart)];
        }

        return collect(explode(",", $event->recurrence_byday))
            ->map(fn ($d) => strtoupper(trim($d)))
            ->filter()
            ->all();
    }

    private function twoLetter(CarbonImmutable $day): string
    {
        return ["SU", "MO", "TU", "WE", "TH", "FR", "SA"][$day->dayOfWeek];
    }
}
