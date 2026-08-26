<?php

namespace App\Http\Requests;

use App\Models\CalendarTask;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Доступ проверяется middleware группы маршрутов.
    }

    /**
     * Собираем срок из даты и (если задача не на весь день) времени.
     * Задача в календаре всегда привязана к дню — без даты её некуда
     * поставить в сетку.
     */
    protected function prepareForValidation(): void
    {
        $date = $this->input("due_date");
        if (!$date) {
            return;
        }

        if ($this->boolean("due_all_day")) {
            $this->merge(["due_at" => $date . " 00:00"]);
        } else {
            $time = $this->input("due_time") ?: "00:00";
            $this->merge(["due_at" => $date . " " . $time]);
        }
    }

    public function rules(): array
    {
        return [
            "title" => "required|string|min:2|max:255",
            "description" => "nullable|string|max:5000",
            "due_date" => "required|date",
            "due_all_day" => "boolean",
            "due_at" => "required|date",
            "priority" => ["nullable", Rule::in(array_keys(CalendarTask::PRIORITIES))],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required" => "Укажите название задачи",
            "due_date.required" => "Укажите дату задачи",
        ];
    }
}
