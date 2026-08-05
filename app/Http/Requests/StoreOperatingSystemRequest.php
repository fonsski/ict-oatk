<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOperatingSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    public function rules(): array
    {
        $operatingSystem = $this->route("operating_system");

        return [
            "name" => [
                "required",
                "string",
                "min:2",
                "max:255",
                Rule::unique("operating_systems", "name")->ignore($operatingSystem),
            ],
            "family" => "nullable|string|max:100",
            "description" => "nullable|string|max:1000",
            "sort_order" => "nullable|integer|min:0",
            "is_active" => "boolean",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "Укажите название операционной системы",
            "name.min" => "Название должно содержать не менее 2 символов",
            "name.unique" => "Такая операционная система уже есть в справочнике",
            "family.max" => "Семейство не должно превышать 100 символов",
            "sort_order.integer" => "Порядок сортировки должен быть целым числом",
        ];
    }

    public function attributes(): array
    {
        return [
            "name" => "название",
            "family" => "семейство",
            "description" => "описание",
            "sort_order" => "порядок сортировки",
            "is_active" => "активность",
        ];
    }
}
