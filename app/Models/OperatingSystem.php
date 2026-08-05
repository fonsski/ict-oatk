<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OperatingSystem extends Model
{
    use HasFactory;

    protected $fillable = [
        "name",
        "slug",
        "family",
        "description",
        "is_active",
        "sort_order",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "sort_order" => "integer",
    ];

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }

    public function scopeActive(Builder $query): void
    {
        $query->where("is_active", true);
    }

    /**
     * Порядок для выпадающих списков: сначала заданный вручную вес,
     * затем семейство и название.
     */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy("sort_order")->orderBy("family")->orderBy("name");
    }

    public function getEquipmentCountAttribute(): int
    {
        return $this->equipment()->count();
    }
}
