<?php

namespace App\Support\Calendar;

use App\Models\CalendarEvent;
use Carbon\CarbonImmutable;

/**
 * Один экземпляр события на конкретную дату.
 *
 * Для одиночного события экземпляр один. Для повторяющегося их много, и
 * каждый несёт свои вычисленные starts_at/ends_at, но ссылается на ту же
 * исходную модель — в базе строка по-прежнему одна.
 */
class EventOccurrence
{
    public function __construct(
        public readonly CalendarEvent $event,
        public readonly CarbonImmutable $startsAt,
        public readonly CarbonImmutable $endsAt,
        public readonly string $title,
    ) {
    }

    public function isAllDay(): bool
    {
        return $this->event->all_day;
    }

    public function color(): string
    {
        return $this->event->color ?: "blue";
    }

    /** Дата экземпляра — ключ, по которому адресуется отклонение серии. */
    public function occurrenceDate(): string
    {
        return $this->startsAt->toDateString();
    }
}
