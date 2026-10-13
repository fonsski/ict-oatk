<?php

namespace App\Http\Requests;

use App\Services\EquipmentImport\EquipmentImportService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EquipmentImportPreviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Роль проверяет middleware маршрута
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            "file" => ["required", "file", "max:5120", "extensions:csv,txt,json"],
            "existing" => [
                "required",
                Rule::in([EquipmentImportService::MODE_SKIP, EquipmentImportService::MODE_UPDATE]),
            ],
            "create_missing" => ["boolean"],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            "file.required" => "Выберите файл для импорта",
            "file.file" => "Не удалось загрузить файл",
            "file.max" => "Файл больше 5 МБ",
            "file.extensions" => "Нужен файл CSV или JSON",
            "existing.required" => "Укажите, что делать с оборудованием, которое уже есть в системе",
            "existing.in" => "Неизвестный способ обработки существующего оборудования",
        ];
    }
}
