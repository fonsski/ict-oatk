<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;
use App\Traits\HasDocuments;

class Equipment extends Model
{
    use SoftDeletes, HasDocuments, HasFactory;

    protected $fillable = [
        "name",
        "model",
        "serial_number",
        "inventory_number",
        "accounting_number",
        "category_id",
        "operating_system_id",
        "status_id",
        "room_id",
        "has_warranty",
        "warranty_end_date",
        "last_service_date",
        "service_comment",
        "known_issues",
        "initial_room_id",
        "written_off_at",
        "write_off_id",
        "purchase_id",
    ];

    protected $casts = [
        "last_service_date" => "date",
        "warranty_end_date" => "date",
        "written_off_at" => "date",
        "has_warranty" => "boolean",
    ];

    public function status(): BelongsTo
    {
        return $this->belongsTo(EquipmentStatus::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EquipmentCategory::class);
    }

    public function operatingSystem(): BelongsTo
    {
        return $this->belongsTo(OperatingSystem::class);
    }

    /**
     * Нужно ли для этой единицы указывать ОС — определяется категорией.
     */
    public function supportsOperatingSystem(): bool
    {
        return (bool) $this->category?->has_operating_system;
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function initialRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, "initial_room_id");
    }

    public function serviceHistory(): HasMany
    {
        return $this->hasMany(EquipmentServiceHistory::class);
    }

    public function locationHistory(): HasMany
    {
        return $this->hasMany(EquipmentLocationHistory::class);
    }

    /**
     * Движения расходников, выданных/установленных в это оборудование
     * (StockMovement с типом "outcome" и заполненным equipment_id).
     */
    public function consumableMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Акт, по которому единица была списана (заполняется при проведении акта).
     */
    public function writeOff(): BelongsTo
    {
        return $this->belongsTo(WriteOff::class);
    }

    public function isWrittenOff(): bool
    {
        return $this->write_off_id !== null;
    }

    /**
     * Закупка, по которой единица поступила (если заведена через закупку).
     */
    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

    /**
     * Статьи базы знаний (инструкции, регламенты), привязанные к оборудованию.
     */
    public function knowledgeArticles(): BelongsToMany
    {
        return $this->belongsToMany(
            KnowledgeBase::class,
            "equipment_knowledge_base",
            "equipment_id",
            "knowledge_base_id",
        )->withTimestamps();
    }

    /**
     * Записывает перемещение оборудования при смене кабинета
     *
     * @param int|null $fromRoomId
     * @param int|null $toRoomId
     * @param string|null $comment
     * @param bool $isInitial
     * @return EquipmentLocationHistory
     */
    public function recordLocationChange(
        ?int $fromRoomId,
        ?int $toRoomId,
        ?string $comment = null,
        bool $isInitial = false,
    ): EquipmentLocationHistory {
        return $this->locationHistory()->create([
            "from_room_id" => $fromRoomId,
            "to_room_id" => $toRoomId,
            "moved_by_user_id" => Auth::id(),
            "move_date" => now(),
            "comment" => $comment,
            "is_initial_location" => $isInitial,
        ]);
    }

    /**
     * Записывает начальное размещение оборудования
     *
     * @param int|null $roomId
     * @param string|null $comment
     * @return EquipmentLocationHistory
     */
    public function recordInitialLocation(
        ?int $roomId,
        ?string $comment = null,
    ): EquipmentLocationHistory {
        return $this->recordLocationChange(null, $roomId, $comment, true);
    }

    /**
     * Записывает перемещение оборудования в новый кабинет
     *
     * @param int|null $newRoomId
     * @param string|null $comment
     * @return EquipmentLocationHistory
     */
    public function moveToRoom(
        ?int $newRoomId,
        ?string $comment = null,
    ): EquipmentLocationHistory {
        $oldRoomId = $this->room_id;

        // Обновляем текущий кабинет
        $this->update(["room_id" => $newRoomId]);

        // Записываем историю перемещения
        return $this->recordLocationChange($oldRoomId, $newRoomId, $comment);
    }
}
