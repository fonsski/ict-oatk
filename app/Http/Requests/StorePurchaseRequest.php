<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    public function rules(): array
    {
        return [
            "number" => "required|string|max:50|unique:purchases,number",
            "date" => "required|date",
            "supplier" => "required|string|max:255",
            "comment" => "nullable|string|max:1000",
            "items" => "required|array|min:1",
            "items.*.item_type" => "required|in:equipment,consumable",
            "items.*.consumable_id" => "nullable|required_if:items.*.item_type,consumable|exists:consumables,id",
            "items.*.equipment_category_id" => "nullable|exists:equipment_categories,id",
            "items.*.name" => "required|string|max:255",
            "items.*.quantity" => "required|integer|min:1",
            "items.*.unit_price" => "required|numeric|min:0",
        ];
    }

    public function messages(): array
    {
        return [
            "number.required" => "Укажите номер закупки",
            "number.unique" => "Закупка с таким номером уже существует",
            "date.required" => "Укажите дату закупки",
            "supplier.required" => "Укажите поставщика",
            "items.required" => "Добавьте хотя бы одну позицию",
            "items.min" => "Добавьте хотя бы одну позицию",
            "items.*.item_type.required" => "Укажите тип позиции",
            "items.*.consumable_id.required_if" => "Выберите расходник для позиции",
            "items.*.name.required" => "Укажите наименование позиции",
            "items.*.quantity.required" => "Укажите количество",
            "items.*.quantity.min" => "Количество должно быть не менее 1",
            "items.*.unit_price.required" => "Укажите цену за единицу",
        ];
    }
}
