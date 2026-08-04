<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreWriteOffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    public function rules(): array
    {
        return [
            "date" => "required|date|before_or_equal:today",
            "reason" => "required|string|max:255",
            "basis" => "nullable|string|max:255",
            "comment" => "nullable|string|max:1000",
            "equipment_ids" => "required|array|min:1",
            "equipment_ids.*" => "required|exists:equipment,id",
        ];
    }

    public function messages(): array
    {
        return [
            "date.required" => "Укажите дату акта",
            "date.before_or_equal" => "Дата акта не может быть в будущем",
            "reason.required" => "Укажите причину списания",
            "equipment_ids.required" => "Выберите хотя бы одну единицу оборудования",
            "equipment_ids.min" => "Выберите хотя бы одну единицу оборудования",
            "equipment_ids.*.exists" => "Одна из выбранных единиц оборудования не найдена",
        ];
    }

    public function attributes(): array
    {
        return [
            "date" => "дата акта",
            "reason" => "причина списания",
            "basis" => "основание",
            "comment" => "комментарий",
            "equipment_ids" => "оборудование",
        ];
    }
}
