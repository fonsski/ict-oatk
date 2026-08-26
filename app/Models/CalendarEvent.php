<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Событие календаря — встреча, мероприятие, бронь кабинета.
 *
 * Повторяющееся событие хранится одной строкой: экземпляры серии не
 * материализуются, а разворачиваются на лету по recurrence-полям (см.
 * occurrencesBetween). Отклонения отдельных дат серии — в CalendarEventException.
 */
class CalendarEvent extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = "confirmed";
    public const STATUS_CANCELLED = "cancelled";

    public const FREQ_DAILY = "daily";
    public const FREQ_WEEKLY = "weekly";
    public const FREQ_WEEKDAYS = "weekdays";
    public const FREQ_MONTHLY = "monthly";

    public const FREQUENCIES = [
        self::FREQ_DAILY => "Каждый день",
        self::FREQ_WEEKLY => "Каждую неделю",
        self::FREQ_WEEKDAYS => "По будням",
        self::FREQ_MONTHLY => "Каждый месяц",
    ];

    /** Ключи цветовой палитры — произвольный CSS сюда не попадает. */
    public const COLORS = [
        "blue",
        "green",
        "red",
        "amber",
        "purple",
        "slate",
    ];

    protected $fillable = [
        "title",
        "description",
        "organizer_id",
        "starts_at",
        "ends_at",
        "all_day",
        "location",
        "room_id",
        "color",
        "status",
        "recurrence_freq",
        "recurrence_interval",
        "recurrence_byday",
        "recurrence_until",
        "recurrence_count",
    ];

    protected $casts = [
        "starts_at" => "datetime",
        "ends_at" => "datetime",
        "all_day" => "boolean",
        "recurrence_until" => "date",
        "recurrence_interval" => "integer",
        "recurrence_count" => "integer",
    ];

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, "organizer_id");
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(CalendarEventParticipant::class, "event_id");
    }

    public function links(): HasMany
    {
        return $this->hasMany(CalendarEventLink::class, "event_id");
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(CalendarEventException::class, "event_id");
    }

    public function isRecurring(): bool
    {
        return $this->recurrence_freq !== null;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * События, которые пересекаются с периодом [$from, $to].
     *
     * Для повторяющихся событий сравнение по одному starts_at недостаточно —
     * серия могла начаться раньше окна и всё ещё идти. Такие берём целиком по
     * дате старта серии и границе повтора, а разворачивает их уже прикладной
     * код (occurrencesBetween).
     */
    public function scopeOverlapping(Builder $query, $from, $to): Builder
    {
        return $query->where(function (Builder $q) use ($from, $to) {
            // Одиночные события: обычное пересечение интервалов.
            $q->where(function (Builder $single) use ($from, $to) {
                $single
                    ->whereNull("recurrence_freq")
                    ->where("starts_at", "<=", $to)
                    ->where("ends_at", ">=", $from);
            })
                // Повторяющиеся: серия началась не позже конца окна и не
                // закончилась до его начала (по recurrence_until, если задан).
                ->orWhere(function (Builder $rec) use ($from, $to) {
                    $rec
                        ->whereNotNull("recurrence_freq")
                        ->where("starts_at", "<=", $to)
                        ->where(function (Builder $until) use ($from) {
                            $until
                                ->whereNull("recurrence_until")
                                ->orWhere("recurrence_until", ">=", $from);
                        });
                });
        });
    }
}
