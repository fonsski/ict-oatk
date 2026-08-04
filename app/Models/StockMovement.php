<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockMovement extends Model
{
    public const TYPE_INCOME = "income";
    public const TYPE_OUTCOME = "outcome";

    protected $fillable = [
        "consumable_id",
        "type",
        "quantity",
        "reason",
        "equipment_id",
        "purchase_id",
        "consumable_write_off_id",
        "moved_by_user_id",
        "moved_at",
    ];

    protected $casts = [
        "quantity" => "integer",
        "moved_at" => "date",
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function consumableWriteOff(): BelongsTo
    {
        return $this->belongsTo(ConsumableWriteOff::class);
    }

    public function movedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "moved_by_user_id");
    }

    public function isIncome(): bool
    {
        return $this->type === self::TYPE_INCOME;
    }

    public function isOutcome(): bool
    {
        return $this->type === self::TYPE_OUTCOME;
    }
}
