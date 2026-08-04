<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consumable extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "name",
        "category",
        "unit",
        "quantity_total",
        "responsible_user_id",
        "notes",
        "purchase_document_path",
        "purchase_document_original_name",
        "purchase_document_mime_type",
        "purchase_document_size",
    ];

    protected $casts = [
        "quantity_total" => "integer",
        "purchase_document_size" => "integer",
    ];

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "responsible_user_id");
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(ConsumableAllocation::class);
    }

    public function installedAllocations(): HasMany
    {
        return $this->allocations()->where(
            "status",
            ConsumableAllocation::STATUS_INSTALLED,
        );
    }

    public function writtenOffAllocations(): HasMany
    {
        return $this->allocations()->where(
            "status",
            ConsumableAllocation::STATUS_WRITTEN_OFF,
        );
    }

    public function getQuantityInstalledAttribute(): int
    {
        return (int) $this->allocations
            ->where("status", ConsumableAllocation::STATUS_INSTALLED)
            ->sum("quantity");
    }

    public function getQuantityWrittenOffAttribute(): int
    {
        return (int) $this->allocations
            ->where("status", ConsumableAllocation::STATUS_WRITTEN_OFF)
            ->sum("quantity");
    }

    public function getQuantityInStockAttribute(): int
    {
        return $this->quantity_total -
            $this->quantity_installed -
            $this->quantity_written_off;
    }

    public function hasPurchaseDocument(): bool
    {
        return !empty($this->purchase_document_path);
    }

    public function getPurchaseDocumentHumanSizeAttribute(): string
    {
        return self::formatBytes($this->purchase_document_size);
    }

    public static function formatBytes(int $bytes): string
    {
        $units = ["Б", "КБ", "МБ", "ГБ"];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, $i === 0 ? 0 : 1) . " " . $units[$i];
    }
}
