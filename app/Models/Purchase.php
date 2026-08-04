<?php

namespace App\Models;

use App\Traits\HasDocuments;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class Purchase extends Model
{
    use HasDocuments, HasFactory;

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
     *
     * Инвентарные номера выдаёт бухгалтерия, поэтому их вводит человек на
     * шаге проведения: массив вида [id позиции => ['101', '102']]. На каждую
     * единицу нужен свой номер — иначе провести закупку нельзя.
     *
     * @param array<int, array<int, string>> $inventoryNumbers
     */
    public function post(array $inventoryNumbers = []): void
    {
        if (!$this->isDraft()) {
            throw new \RuntimeException("Провести можно только закупку в статусе «Черновик»");
        }

        DB::transaction(function () use ($inventoryNumbers) {
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

                    continue;
                }

                $numbers = array_values(array_filter(
                    $inventoryNumbers[$item->id] ?? [],
                    fn($number) => trim((string) $number) !== "",
                ));

                if (count($numbers) !== $item->quantity) {
                    throw new \RuntimeException(
                        "Для позиции «{$item->name}» нужно указать {$item->quantity} инвентарных номеров",
                    );
                }

                foreach ($numbers as $number) {
                    Equipment::create([
                        "name" => $item->name,
                        "category_id" => $item->equipment_category_id,
                        "status_id" => $workingStatusId,
                        "inventory_number" => trim($number),
                        "purchase_id" => $this->id,
                    ]);
                }
            }

            $this->update([
                "status" => self::STATUS_POSTED,
                "posted_at" => now(),
            ]);
        });
    }

    /**
     * Позиции с оборудованием — для них на проведении запрашиваются номера.
     */
    public function equipmentItems()
    {
        return $this->items()->where("item_type", PurchaseItem::TYPE_EQUIPMENT);
    }

    /**
     * Оборудование, заведённое по этой закупке.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class);
    }
}
