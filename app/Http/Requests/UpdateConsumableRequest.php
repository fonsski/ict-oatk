<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConsumableRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    public function rules(): array
    {
        return [
            "name" => "required|string|min:2|max:255",
            "category" => "nullable|string|max:255",
            "unit" => "nullable|string|max:20",
            "min_quantity" => "nullable|integer|min:0",
            "room_id" => "nullable|exists:rooms,id",
            "responsible_user_id" => "nullable|exists:users,id",
            "notes" => "nullable|string|max:1000",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "Пожалуйста, укажите название расходника",
            "name.min" => "Название должно содержать не менее 2 символов",
            "min_quantity.integer" => "Минимальный остаток должен быть целым числом",
            "room_id.exists" => "Выбранный кабинет не существует в системе",
            "responsible_user_id.exists" => "Выбранный ответственный не существует в системе",
        ];
    }

    public function attributes(): array
    {
        return [
            "name" => "название",
            "category" => "категория",
            "unit" => "единица измерения",
            "min_quantity" => "минимальный остаток",
            "room_id" => "кабинет (место хранения)",
            "responsible_user_id" => "ответственный",
            "notes" => "примечания",
        ];
    }
}
