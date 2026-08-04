<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsumableWriteOff extends Model
{
    use HasDocuments;

    protected $fillable = [
        "number",
        "written_off_at",
        "reason",
        "written_off_by_user_id",
        "comment",
    ];

    protected $casts = [
        "written_off_at" => "date",
    ];

    public function items(): HasMany
    {
        return $this->hasMany(StockMovement::class, "consumable_write_off_id");
    }

    public function writtenOffByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "written_off_by_user_id");
    }

    public function getTotalQuantityAttribute(): int
    {
        return (int) $this->items->sum("quantity");
    }
}
