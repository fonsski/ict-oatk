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
        $consumable = $this->route("consumable");
        $allocated = $consumable
            ? $consumable->quantity_installed + $consumable->quantity_written_off
            : 0;

        return [
            "name" => "required|string|min:2|max:255",
            "category" => "nullable|string|max:255",
            "unit" => "nullable|string|max:20",
            "quantity_total" => ["required", "integer", "min:{$allocated}"],
            "responsible_user_id" => "nullable|exists:users,id",
            "notes" => "nullable|string|max:1000",
            "purchase_document" => "nullable|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,png,jpg,jpeg,gif",
        ];
    }

    public function messages(): array
    {
        return [
            "name.required" => "Пожалуйста, укажите название расходника",
            "name.min" => "Название должно содержать не менее 2 символов",
            "quantity_total.required" => "Пожалуйста, укажите количество",
            "quantity_total.integer" => "Количество должно быть целым числом",
            "quantity_total.min" => "Количество не может быть меньше уже установленного и списанного (:min)",
            "responsible_user_id.exists" => "Выбранный ответственный не существует в системе",
            "purchase_document.max" => "Файл документа закупки не должен превышать 10 МБ",
            "purchase_document.mimes" => "Недопустимый формат документа закупки",
        ];
    }

    public function attributes(): array
    {
        return [
            "name" => "название",
            "category" => "категория",
            "unit" => "единица измерения",
            "quantity_total" => "количество",
            "responsible_user_id" => "ответственный",
            "notes" => "примечания",
            "purchase_document" => "документ закупки",
        ];
    }
}
