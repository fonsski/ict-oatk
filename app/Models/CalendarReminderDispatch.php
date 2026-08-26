<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Отметка о разосланном напоминании по конкретному экземпляру события —
 * защита от повторной рассылки, в том числе по датам повторяющейся серии.
 */
class CalendarReminderDispatch extends Model
{
    // В таблице нет created_at/updated_at — отметка времени одна: sent_at.
    public $timestamps = false;

    protected $fillable = [
        "event_id",
        "occurrence_starts_at",
        "sent_at",
    ];

    protected $casts = [
        "occurrence_starts_at" => "datetime",
        "sent_at" => "datetime",
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, "event_id");
    }
}
