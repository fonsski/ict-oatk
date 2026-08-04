<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class WriteOff extends Model
{
    use HasDocuments;

    public const STATUS_DRAFT = "draft";
    public const STATUS_POSTED = "posted";

    public const STATUSES = [
        self::STATUS_DRAFT => "Черновик",
        self::STATUS_POSTED => "Проведён",
    ];

    protected $fillable = [
        "number",
        "date",
        "reason",
        "basis",
        "status",
        "created_by_user_id",
        "comment",
        "posted_at",
    ];

    protected $casts = [
        "date" => "date",
        "posted_at" => "datetime",
    ];

    public function items(): HasMany
    {
        return $this->hasMany(WriteOffItem::class);
    }

    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, "write_off_items")
            ->withPivot("note")
            ->withTimestamps();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, "created_by_user_id");
    }

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    /**
     * Провести акт: каждая единица из позиций получает статус «Списано»,
     * дату списания и ссылку на этот акт. Всё — в одной транзакции.
     */
    public function post(): void
    {
        if (!$this->isDraft()) {
            throw new \RuntimeException("Провести можно только акт в статусе «Черновик»");
        }

        DB::transaction(function () {
            $decommissionedStatusId = EquipmentStatus::where("slug", "decommissioned")->value("id");

            if (!$decommissionedStatusId) {
                throw new \RuntimeException("В справочнике нет статуса «Списано»");
            }

            $equipmentIds = $this->items()->pluck("equipment_id");

            Equipment::whereIn("id", $equipmentIds)->update([
                "status_id" => $decommissionedStatusId,
                "written_off_at" => $this->date,
                "write_off_id" => $this->id,
            ]);

            $this->update([
                "status" => self::STATUS_POSTED,
                "posted_at" => now(),
            ]);
        });
    }
}
