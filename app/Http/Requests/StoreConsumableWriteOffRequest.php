<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsumableWriteOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    public function rules(): array
    {
        return [
            "written_off_at" => "required|date|before_or_equal:today",
            "reason" => "nullable|string|max:255",
            "comment" => "nullable|string|max:1000",
            "items" => "required|array|min:1",
            "items.*.consumable_id" => "required|exists:consumables,id",
            "items.*.quantity" => "required|integer|min:1",
        ];
    }

    public function messages(): array
    {
        return [
            "written_off_at.required" => "Укажите дату списания",
            "written_off_at.before_or_equal" => "Дата списания не может быть в будущем",
            "items.required" => "Добавьте хотя бы одну позицию",
            "items.min" => "Добавьте хотя бы одну позицию",
            "items.*.consumable_id.required" => "Выберите расходник для позиции",
            "items.*.quantity.required" => "Укажите количество для позиции",
            "items.*.quantity.min" => "Количество должно быть не менее 1",
        ];
    }
}
