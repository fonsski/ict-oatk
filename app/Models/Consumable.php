<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Consumable extends Model
{
    use SoftDeletes, HasDocuments, HasFactory;

    protected $fillable = [
        "name",
        "category",
        "unit",
        "quantity",
        "min_quantity",
        "room_id",
        "responsible_user_id",
        "notes",
    ];

    protected $casts = [
        "quantity" => "integer",
        "min_quantity" => "integer",
    ];

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "responsible_user_id");
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function scopeLowStock(Builder $query): void
    {
        $query
            ->whereNotNull("min_quantity")
            ->whereColumn("quantity", "<=", "min_quantity");
    }

    public function isLowStock(): bool
    {
        return $this->min_quantity !== null && $this->quantity <= $this->min_quantity;
    }

    /**
     * Зафиксировать приход (например, по закупке) и увеличить остаток.
     * Строка блокируется на время операции, чтобы параллельные приходы/
     * расходы не потеряли друг друга.
     */
    public function recordIncome(int $quantity, array $attributes = []): StockMovement
    {
        return DB::transaction(function () use ($quantity, $attributes) {
            $consumable = self::whereKey($this->id)->lockForUpdate()->firstOrFail();
            $consumable->increment("quantity", $quantity);
            $this->quantity = $consumable->quantity;

            return $consumable->movements()->create(array_merge(
                [
                    "type" => StockMovement::TYPE_INCOME,
                    "quantity" => $quantity,
                    "moved_at" => now(),
                ],
                $attributes,
            ));
        });
    }

    /**
     * Зафиксировать расход/списание и уменьшить остаток.
     *
     * @throws \RuntimeException если запрошенное количество больше остатка
     */
    public function recordOutcome(int $quantity, array $attributes = []): StockMovement
    {
        return DB::transaction(function () use ($quantity, $attributes) {
            $consumable = self::whereKey($this->id)->lockForUpdate()->firstOrFail();

            if ($quantity > $consumable->quantity) {
                throw new \RuntimeException(
                    "На складе недостаточно «{$consumable->name}» (в наличии: {$consumable->quantity})",
                );
            }

            $consumable->decrement("quantity", $quantity);
            $this->quantity = $consumable->quantity;

            return $consumable->movements()->create(array_merge(
                [
                    "type" => StockMovement::TYPE_OUTCOME,
                    "quantity" => $quantity,
                    "moved_at" => now(),
                ],
                $attributes,
            ));
        });
    }
}
