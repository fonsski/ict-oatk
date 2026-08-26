<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Отклонение одного экземпляра повторяющегося события: отмена конкретной
 * даты серии либо её перенос/переименование.
 */
class CalendarEventException extends Model
{
    protected $fillable = [
        "event_id",
        "occurrence_date",
        "is_cancelled",
        "starts_at",
        "ends_at",
        "title",
    ];

    protected $casts = [
        "occurrence_date" => "date",
        "is_cancelled" => "boolean",
        "starts_at" => "datetime",
        "ends_at" => "datetime",
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, "event_id");
    }
}
