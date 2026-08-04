<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use HasDocuments;

    public const STATUS_DRAFT = "draft";
    public const STATUS_POSTED = "posted";
    public const STATUS_CANCELLED = "cancelled";

    public const STATUSES = [
        self::STATUS_DRAFT => "Черновик",
        self::STATUS_POSTED => "Проведена",
        self::STATUS_CANCELLED => "Отменена",
    ];

    protected $fillable = [
        "number",
        "date",
        "supplier",
        "status",
        "total_sum",
        "created_by_user_id",
        "comment",
        "posted_at",
    ];

    protected $casts = [
        "date" => "date",
        "total_sum" => "decimal:2",
        "posted_at" => "datetime",
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
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

    public function recalculateTotal(): void
    {
        $this->update(["total_sum" => $this->items()->sum("sum")]);
    }

    /**
     * Провести закупку: оборудование заводится в инвентарь (по позициям
     * "equipment"), расходники пополняют остаток движением прихода
     * (позиции "consumable"). Всё — в одной транзакции.
     */
    public function post(): void
    {
        if (!$this->isDraft()) {
            throw new \RuntimeException("Провести можно только закупку в статусе «Черновик»");
        }

        DB::transaction(function () {
            $workingStatusId = EquipmentStatus::where("slug", "working")->value("id")
                ?? EquipmentStatus::query()->value("id");

            foreach ($this->items()->with(["consumable", "equipmentCategory"])->get() as $item) {
                if ($item->item_type === PurchaseItem::TYPE_CONSUMABLE) {
                    $item->consumable->recordIncome($item->quantity, [
                        "purchase_id" => $this->id,
                        "reason" => "Приход по закупке {$this->number}",
                        "moved_by_user_id" => Auth::id(),
                        "moved_at" => $this->date,
                    ]);
                } else {
                    for ($i = 0; $i < $item->quantity; $i++) {
                        Equipment::create([
                            "name" => $item->name,
                            "category_id" => $item->equipment_category_id,
                            "status_id" => $workingStatusId,
                            // Инвентарный номер выдаётся бухгалтерией — до его
                            // присвоения используем понятную заглушку с цифрами,
                            // которую нужно заменить перед дальнейшим редактированием.
                            "inventory_number" => $this->generatePlaceholderInventoryNumber($item->id, $i),
                        ]);
                    }
                }
            }

            $this->update([
                "status" => self::STATUS_POSTED,
                "posted_at" => now(),
            ]);
        });
    }

    /**
     * Заглушка вместо реального инвентарного номера (его выдаёт бухгалтерия).
     * Гарантированно уникальна за счёт id позиции закупки, но не проходит
     * regex "только цифры" из StoreEquipmentRequest — это осознанно: пока
     * реальный номер не проставлен, дальнейшее редактирование карточки
     * через форму заблокировано валидацией, и это подсказывает, что делать.
     */
    private function generatePlaceholderInventoryNumber(int $purchaseItemId, int $index): string
    {
        return "NEW-{$purchaseItemId}-{$index}";
    }
}
