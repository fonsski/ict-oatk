<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WriteOffItem extends Model
{
    protected $fillable = [
        "write_off_id",
        "equipment_id",
        "note",
    ];

    public function writeOff(): BelongsTo
    {
        return $this->belongsTo(WriteOff::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }
}
