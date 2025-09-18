<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Auth;

class Equipment extends Model
{
    use SoftDeletes;

    protected $fillable = [
        "name",
        "model",
        "serial_number",
        "inventory_number",
        "category_id",
        "status_id",
        "room_id",
        "has_warranty",
        "warranty_end_date",
        "last_service_date",
        "service_comment",
        "known_issues",
        "initial_room_id",
    ];

    protected $casts = [
        "last_service_date" => "date",
        "warranty_end_date" => "date",
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
