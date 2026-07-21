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
