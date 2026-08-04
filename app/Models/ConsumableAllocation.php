<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsumableAllocation extends Model
{
    public const STATUS_INSTALLED = "installed";
    public const STATUS_WRITTEN_OFF = "written_off";

    protected $fillable = [
        "consumable_id",
        "equipment_id",
        "quantity",
        "status",
        "installed_at",
        "installed_by_user_id",
        "note",
        "written_off_at",
        "written_off_by_user_id",
        "written_off_reason",
        "write_off_document_path",
        "write_off_document_original_name",
        "write_off_document_mime_type",
        "write_off_document_size",
    ];

    protected $casts = [
        "quantity" => "integer",
        "installed_at" => "date",
        "written_off_at" => "date",
        "write_off_document_size" => "integer",
    ];

    public function consumable(): BelongsTo
    {
        return $this->belongsTo(Consumable::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function installedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "installed_by_user_id");
    }

    public function writtenOffByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, "written_off_by_user_id");
    }

    public function isInstalled(): bool
    {
        return $this->status === self::STATUS_INSTALLED;
    }

    public function isWrittenOff(): bool
    {
        return $this->status === self::STATUS_WRITTEN_OFF;
    }

    public function hasWriteOffDocument(): bool
    {
        return !empty($this->write_off_document_path);
    }

    public function getWriteOffDocumentHumanSizeAttribute(): string
    {
        return Consumable::formatBytes($this->write_off_document_size);
    }
}
