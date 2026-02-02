<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Traits\QueryOptimizer;

class Ticket extends Model
{
    use QueryOptimizer;

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
    ];

    
     * Правила валидации для модели

    public static function validationRules()
    {
        return [
            'title' => 'required|string|min:5|max:60',
            'category' => 'required|string|in:hardware,software,network,account,other',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'description' => 'required|string|min:10|max:5000',
            'reporter_name' => 'nullable|string|max:255',
            'reporter_email' => 'nullable|email|max:255',
            'reporter_phone' => 'nullable|string|max:20|regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/',
            'reporter_id' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:open,in_progress,resolved,closed',
            'location_id' => 'nullable|exists:locations,id',
            'room_id' => 'nullable|exists:rooms,id',
            'equipment_id' => 'nullable|exists:equipment,id',
        ];
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

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
}
