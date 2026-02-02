<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentLocationHistory extends Model
{
    use HasFactory;

    
     * Атрибуты, которые можно массово назначать.
     *
     * @var array<int, string>

    protected $fillable = [
        'equipment_id',
        'from_room_id',
        'to_room_id',
        'moved_by_user_id',
        'move_date',
        'comment',
        'is_initial_location',
    ];

    
     * Атрибуты, которые должны быть приведены к типам.
     *
     * @var array<string, string>

    protected $casts = [
        'move_date' => 'date',
        'is_initial_location' => 'boolean',
    ];

    
     * Связь с оборудованием

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    
     * Связь с кабинетом, откуда было перемещено оборудование

    public function fromRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'from_room_id');
    }

    
     * Связь с кабинетом, куда было перемещено оборудование

    public function toRoom(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'to_room_id');
    }

    
     * Связь с пользователем, который выполнил перемещение

    public function movedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moved_by_user_id');
    }

    
     * Проверка, является ли запись начальным размещением

    public function isInitialPlacement(): bool
    {
        return $this->is_initial_location;
    }

    
     * Получение типа перемещения в читаемом виде

    public function getMoveTypeTextAttribute(): string
    {
        if ($this->is_initial_location) {
            return 'Первоначальное размещение';
        }

        if (empty($this->from_room_id) && !empty($this->to_room_id)) {
            return 'Поступление в кабинет';
        }

        if (!empty($this->from_room_id) && empty($this->to_room_id)) {
            return 'Изъятие из кабинета';
        }

        return 'Перемещение между кабинетами';
    }
}
