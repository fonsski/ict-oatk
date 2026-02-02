<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentServiceHistory extends Model
{
    protected $fillable = [
        "equipment_id",
        "service_date",
        "service_type",
        "description",
        "performed_by_user_id",
        "next_service_date",
        "service_result",
        "problems_found",
        "problems_fixed",
        "attachments",
    ];

    protected $casts = [
        "service_date" => "datetime",
        "next_service_date" => "datetime",
        "attachments" => "array",
    ];

    
     * Связь с оборудованием

    public function equipment()
    {
        return $this->belongsTo(Equipment::class);
    }

    
     * Связь с пользователем, выполнившим обслуживание

    public function performedBy()
    {
        return $this->belongsTo(User::class, "performed_by_user_id");
    }

    
     * Получение читаемого имени типа обслуживания

    public function getServiceTypeNameAttribute()
    {
        $types = [
            "regular" => "Плановое обслуживание",
            "repair" => "Ремонт",
            "diagnostic" => "Диагностика",
            "cleaning" => "Чистка",
            "update" => "Обновление ПО",
            "calibration" => "Калибровка",
            "other" => "Другое",
        ];

        return $types[$this->service_type] ?? $this->service_type;
    }

    
     * Получение читаемого имени результата обслуживания

    public function getServiceResultNameAttribute()
    {
        $results = [
            "success" => "Успешно",
            "partial" => "Частично выполнено",
            "failed" => "Не выполнено",
            "pending" => "Требуется дополнительное обслуживание",
        ];

        return $results[$this->service_result] ?? $this->service_result;
    }

    
     * Scope для последнего обслуживания

    public function scopeLatest($query)
    {
        return $query->orderBy("service_date", "desc");
    }
}
