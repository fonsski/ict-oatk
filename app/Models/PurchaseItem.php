<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseItem extends Model
{
    public const TYPE_EQUIPMENT = "equipment";
    public const TYPE_CONSUMABLE = "consumable";

    public const TYPES = [
        self::TYPE_EQUIPMENT => "Оборудование",
        self::TYPE_CONSUMABLE => "Расходник",
    ];

    protected $fillable = [
        "purchase_id",
        "item_type",
        "consumable_id",
        "equipment_category_id",
        "name",
        "quantity",
        "unit",
        "unit_price",
        "sum",
    ];

    protected $casts = [
        "quantity" => "integer",
        "unit_price" => "decimal:2",
        "sum" => "decimal:2",
    ];

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    public function equipmentCategory(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class);
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->item_type] ?? $this->item_type;
    }
}
