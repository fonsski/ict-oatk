<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Приглашённый в событие и его ответ (RSVP).
 */
class CalendarEventParticipant extends Model
{
    use HasFactory;

    public const RESPONSE_PENDING = "pending";
    public const RESPONSE_ACCEPTED = "accepted";
    public const RESPONSE_DECLINED = "declined";
    public const RESPONSE_MAYBE = "maybe";

    public const RESPONSES = [
        self::RESPONSE_PENDING => "Ожидает ответа",
        self::RESPONSE_ACCEPTED => "Придёт",
        self::RESPONSE_DECLINED => "Не придёт",
        self::RESPONSE_MAYBE => "Возможно",
    ];

    protected $fillable = [
        "event_id",
        "user_id",
        "response",
        "responded_at",
    ];

    protected $casts = [
        "responded_at" => "datetime",
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(CalendarEvent::class, "event_id");
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getResponseLabelAttribute(): string
    {
        return self::RESPONSES[$this->response] ?? $this->response;
    }
}
