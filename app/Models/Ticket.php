<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\QueryOptimizer;

class Ticket extends Model
{
    use QueryOptimizer, SoftDeletes;

    protected $fillable = [
        "title",
        "category",
        "priority",
        "description",
        "reporter_name",
        "reporter_email",
        "reporter_phone",
        "reporter_id",
        "status",
        "user_id",
        "location_id",
        "room_id",
        "assigned_to_id",
        "equipment_id",
        // guest_token намеренно не здесь: метку владельца выставляет только
        // сервер. Попади она в fillable — её можно было бы подменить обычным
        // полем формы и присвоить себе чужую заявку.
    ];

    /**
     * Метка гостя — секрет: кто её предъявил, тот и видит заявку.
     * Прячем, чтобы не утекла ни в JSON, ни в отладочный вывод.
     */
    protected $hidden = ["guest_token"];

    /**
     * Заявки, поданные гостем с этой меткой.
     */
    public function scopeOwnedByGuest($query, ?string $token)
    {
        // Без метки не должно находиться ничего: пустой токен не может
        // совпасть с «любой заявкой без владельца».
        if (!$token) {
            return $query->whereRaw("1 = 0");
        }

        return $query->where("guest_token", $token);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(TicketComment::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, "assigned_to_id");
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /**
     * События календаря, привязанные к этой заявке (обратная сторона
     * calendar_event_links).
     */
    public function calendarEvents(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->morphToMany(
            CalendarEvent::class,
            "linkable",
            "calendar_event_links",
            "linkable_id",
            "event_id",
        )->withTimestamps();
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
