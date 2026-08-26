<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Route;

class Document extends Model
{
    public const TYPE_WRITE_OFF_ACT = "write_off_act";
    public const TYPE_CONTRACT = "contract";
    public const TYPE_INVOICE = "invoice";
    public const TYPE_DELIVERY_NOTE = "delivery_note";
    public const TYPE_OTHER = "other";

    public const TYPES = [
        self::TYPE_WRITE_OFF_ACT => "Акт списания",
        self::TYPE_CONTRACT => "Договор",
        self::TYPE_INVOICE => "Счёт",
        self::TYPE_DELIVERY_NOTE => "Накладная",
        self::TYPE_OTHER => "Прочее",
    ];

    public const ALLOWED_MIMES = "pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg,gif";
    public const MAX_SIZE_KB = 10240; // 10 МБ

    protected $fillable = [
        "documentable_type",
        "documentable_id",
        "type",
        "path",
        "original_name",
        "mime_type",
        "size",
        "description",
        "is_private",
        "uploaded_by_user_id",
    ];

    protected $casts = [
        "size" => "integer",
        "is_private" => "boolean",
    ];

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "uploaded_by_user_id");
    }

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? self::TYPES[self::TYPE_OTHER];
    }

    public function getHumanSizeAttribute(): string
    {
        return self::formatBytes($this->size);
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

    /**
     * Человекочитаемое название сущности, к которой привязан документ.
     */
    public function getSubjectLabelAttribute(): string
    {
        if (!$this->documentable) {
            return class_basename($this->documentable_type ?? "") . " (удалено)";
        }

        return match ($this->documentable_type) {
            Equipment::class => "Оборудование " . $this->documentable->inventory_number,
            Consumable::class => "Расходник «" . $this->documentable->name . "»",
            Purchase::class => "Закупка " . $this->documentable->number,
            WriteOff::class => "Акт списания " . $this->documentable->number,
            ConsumableWriteOff::class => "Списание расходников " . $this->documentable->number,
            default => class_basename($this->documentable_type) . " #" . $this->documentable_id,
        };
    }

    /**
     * Ссылка на карточку сущности, к которой привязан документ (если маршрут уже существует).
     */
    public function getSubjectUrlAttribute(): ?string
    {
        if (!$this->documentable) {
            return null;
        }

        return match ($this->documentable_type) {
            Equipment::class => route("equipment.show", $this->documentable),
            Consumable::class => route("consumables.show", $this->documentable),
            Purchase::class => Route::has("purchases.show")
                ? route("purchases.show", $this->documentable)
                : null,
            WriteOff::class => Route::has("write-offs.show")
                ? route("write-offs.show", $this->documentable)
                : null,
            ConsumableWriteOff::class => Route::has("consumable-write-offs.show")
                ? route("consumable-write-offs.show", $this->documentable)
                : null,
            default => null,
        };
    }
}
