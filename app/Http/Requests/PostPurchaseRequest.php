<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class PostPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    /**
     * Правила строятся по позициям закупки: на каждую единицу оборудования
     * нужен свой инвентарный номер того же формата, что и в карточке
     * оборудования (только цифры, до 20 символов, уникальный).
     */
    public function rules(): array
    {
        $rules = [
            "inventory_numbers" => "nullable|array",
        ];

        foreach ($this->route("purchase")->equipmentItems()->get() as $item) {
            $rules["inventory_numbers.{$item->id}"] = [
                "required",
                "array",
                "size:{$item->quantity}",
            ];
            $rules["inventory_numbers.{$item->id}.*"] = [
                "required",
                "string",
                "min:1",
                "max:20",
                "regex:/^\d+$/",
                "unique:equipment,inventory_number",
            ];
        }

        return $rules;
    }

    /**
     * Номера должны не повторяться и между разными позициями закупки —
     * правило distinct проверяет только внутри одного массива.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $duplicates = collect($this->input("inventory_numbers", []))
                ->flatten()
                ->map(fn($number) => trim((string) $number))
                ->filter()
                ->duplicates()
                ->unique();

            if ($duplicates->isNotEmpty()) {
                $validator->errors()->add(
                    "inventory_numbers",
                    "Инвентарные номера не должны повторяться: " . $duplicates->implode(", "),
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            "inventory_numbers.*.required" => "Укажите инвентарные номера для каждой позиции",
            "inventory_numbers.*.size" => "Количество инвентарных номеров должно совпадать с количеством единиц в позиции",
            "inventory_numbers.*.*.required" => "Укажите инвентарный номер для каждой единицы",
            "inventory_numbers.*.*.regex" => "Инвентарный номер должен содержать только цифры",
            "inventory_numbers.*.*.max" => "Инвентарный номер не должен превышать 20 символов",
            "inventory_numbers.*.*.unique" => "Оборудование с таким инвентарным номером уже есть в системе",
        ];
    }
}
