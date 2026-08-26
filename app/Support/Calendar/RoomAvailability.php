<?php

namespace App\Support\Calendar;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/**
 * Проверка занятости кабинета: не бронирует ли его другое событие на то же
 * время. Учитывает повторяющиеся события — они разворачиваются на нужный
 * отрезок, а не сравниваются одной датой начала серии.
 */
class RoomAvailability
{
    public function __construct(private OccurrenceExpander $expander)
    {
    }

    /**
     * Экземпляры чужих событий, пересекающие [$start, $end] в этом кабинете.
     *
     * @return Collection<int, EventOccurrence>
     */
    public function conflicts(int $roomId, CarbonImmutable $start, CarbonImmutable $end, ?int $exceptEventId = null): Collection
    {
        $events = CalendarEvent::query()
            ->where("room_id", $roomId)
            ->where("status", CalendarEvent::STATUS_CONFIRMED)
            ->when($exceptEventId, fn ($q) => $q->where("id", "!=", $exceptEventId))
            ->overlapping($start, $end)
            ->with("exceptions")
            ->get();

        return $this->expander
            ->expand($events, $start, $end)
            ->filter(fn (EventOccurrence $o) => $o->startsAt->lt($end) && $o->endsAt->gt($start))
            ->values();
    }

    /**
     * Короткое человекочитаемое описание конфликтов для сообщения об ошибке.
     */
    public function describe(Collection $conflicts): string
    {
        return $conflicts
            ->take(3)
            ->map(function (EventOccurrence $o) {
                $time = $o->isAllDay()
                    ? "весь день"
                    : $o->startsAt->format("H:i") . "–" . $o->endsAt->format("H:i");
                return $o->startsAt->format("d.m") . " " . $time . " — " . $o->title;
            })
            ->implode("; ");
    }
}
