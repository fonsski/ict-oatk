<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        "number",
        "name",
        "description",
        "floor",
        "building",
        "capacity",
        "type",
        "is_active",
        "status",
        "equipment_list",
        "schedule",
        "responsible_person",
        "responsible_user_id",
        "phone",
        "notes",
    ];

    protected $casts = [
        "is_active" => "boolean",
        "capacity" => "integer",
        "equipment_list" => "array",
        "schedule" => "array",
    ];

    
     * Типы кабинетов

    const TYPES = [
        "classroom" => "Учебный класс",
        "laboratory" => "Лаборатория",
        "computer_lab" => "Компьютерный класс",
        "office" => "Офис",
        "conference" => "Конференц-зал",
        "auditorium" => "Аудитория",
        "workshop" => "Мастерская",
        "library" => "Библиотека",
        "gym" => "Спортивный зал",
        "other" => "Другое",
    ];

    
     * Статусы кабинетов

    const STATUSES = [
        "available" => "Доступен",
        "maintenance" => "На обслуживании",
        "occupied" => "Занят",
        "reserved" => "Забронирован",
        "closed" => "Закрыт",
    ];

    
     * Отношение к оборудованию в кабинете

    public function equipment()
    {
        return $this->hasMany(Equipment::class);
    }

    
     * Отношение к ответственному пользователю

    public function responsibleUser()
    {
        return $this->belongsTo(User::class, "responsible_user_id");
    }

    
     * Отношение к заявкам, связанным с кабинетом

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    
     * Scope для активных кабинетов

    public function scopeActive(Builder $query): void
    {
        $query->where("is_active", true);
    }

    
     * Scope для доступных кабинетов

    public function scopeAvailable(Builder $query): void
    {
        $query->where("status", "available");
    }

    
     * Scope для поиска по номеру или названию

    public function scopeSearch(Builder $query, string $search): void
    {
        $query->where(function ($q) use ($search) {
            $q->where("number", "like", "%{$search}%")
                ->orWhere("name", "like", "%{$search}%")
                ->orWhere("description", "like", "%{$search}%");
        });
    }

    
     * Scope для фильтрации по типу

    public function scopeOfType(Builder $query, string $type): void
    {
        $query->where("type", $type);
    }

    
     * Scope для фильтрации по статусу

    public function scopeWithStatus(Builder $query, string $status): void
    {
        $query->where("status", $status);
    }

    
     * Scope для фильтрации по зданию

    public function scopeInBuilding(Builder $query, string $building): void
    {
        $query->where("building", $building);
    }

    
     * Scope для фильтрации по этажу

    public function scopeOnFloor(Builder $query, string $floor): void
    {
        $query->where("floor", $floor);
    }

    
     * Получение названия типа кабинета

    public function getTypeNameAttribute(): string
    {
        return self::TYPES[$this->type] ?? "Неизвестно";
    }

    
     * Получение названия статуса

    public function getStatusNameAttribute(): string
    {
        return self::STATUSES[$this->status] ?? "Неизвестно";
    }

    
     * Получение статуса в виде badge

    public function getStatusBadgeAttribute(): string
    {
        $classes = [
            "available" => "bg-green-100 text-green-800",
            "maintenance" => "bg-yellow-100 text-yellow-800",
            "occupied" => "bg-red-100 text-red-800",
            "reserved" => "bg-blue-100 text-blue-800",
            "closed" => "bg-gray-100 text-gray-800",
        ];

        $class = $classes[$this->status] ?? "bg-gray-100 text-gray-800";

        return "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}'>{$this->status_name}</span>";
    }

    
     * Получение типа в виде badge

    public function getTypeBadgeAttribute(): string
    {
        $classes = [
            "classroom" => "bg-blue-100 text-blue-800",
            "laboratory" => "bg-purple-100 text-purple-800",
            "computer_lab" => "bg-indigo-100 text-indigo-800",
            "office" => "bg-green-100 text-green-800",
            "conference" => "bg-yellow-100 text-yellow-800",
            "auditorium" => "bg-red-100 text-red-800",
            "workshop" => "bg-orange-100 text-orange-800",
            "library" => "bg-teal-100 text-teal-800",
            "gym" => "bg-pink-100 text-pink-800",
            "other" => "bg-gray-100 text-gray-800",
        ];

        $class = $classes[$this->type] ?? "bg-gray-100 text-gray-800";

        return "<span class='inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {$class}'>{$this->type_name}</span>";
    }

    
     * Получение полного адреса кабинета

    public function getFullAddressAttribute(): string
    {
        $parts = [];

        if ($this->building) {
            $parts[] = $this->building;
        }

        if ($this->floor) {
            $parts[] = $this->floor . " этаж";
        }

        $parts[] = "каб. " . $this->number;

        return implode(", ", $parts);
    }

    
     * Проверка, доступен ли кабинет

    public function isAvailable(): bool
    {
        return $this->is_active && $this->status === "available";
    }

    
     * Проверка, можно ли забронировать кабинет

    public function canBeReserved(): bool
    {
        return $this->is_active &&
            in_array($this->status, ["available", "occupied"]);
    }

    
     * Активация кабинета

    public function activate(): void
    {
        $this->update(["is_active" => true]);
    }

    
     * Деактивация кабинета

    public function deactivate(): void
    {
        $this->update(["is_active" => false]);
    }

    
     * Изменение статуса кабинета

    public function changeStatus(string $status): void
    {
        if (array_key_exists($status, self::STATUSES)) {
            $this->update(["status" => $status]);
        }
    }

    
     * Получение количества активного оборудования

    public function getActiveEquipmentCountAttribute(): int
    {
        return $this->equipment()->where("status", "active")->count();
    }

    
     * Получение количества заявок за последние 30 дней

    public function getRecentTicketsCountAttribute(): int
    {
        return $this->tickets()
            ->where("created_at", ">=", now()->subDays(30))
            ->count();
    }
}
