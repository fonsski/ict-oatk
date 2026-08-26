<?php

namespace App\Http\Requests;

use App\Models\CalendarEvent;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCalendarEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Доступ проверяется middleware группы маршрутов.
    }

    /**
     * Событие на весь день не требует времени — подставляем границы суток,
     * чтобы дальше валидация и хранение работали единообразно.
     */
    protected function prepareForValidation(): void
    {
        if ($this->boolean("all_day")) {
            $date = $this->input("starts_date") ?: $this->input("starts_at");
            $end = $this->input("ends_date") ?: $date;

            if ($date) {
                $this->merge([
                    "starts_at" => $date . " 00:00",
                    "ends_at" => $end . " 23:59",
                ]);
            }
        }
    }

    public function rules(): array
    {
        return [
            "title" => "required|string|min:2|max:255",
            "description" => "nullable|string|max:5000",
            "starts_at" => "required|date",
            "ends_at" => "required|date|after_or_equal:starts_at",
            "all_day" => "boolean",
            "location" => "nullable|string|max:255",
            "room_id" => "nullable|exists:rooms,id",
            "color" => ["nullable", Rule::in(CalendarEvent::COLORS)],
        ];
    }

    public function messages(): array
    {
        return [
            "title.required" => "Укажите название события",
            "title.min" => "Название слишком короткое",
            "starts_at.required" => "Укажите начало события",
            "ends_at.required" => "Укажите окончание события",
            "ends_at.after_or_equal" => "Окончание не может быть раньше начала",
            "room_id.exists" => "Выбранный кабинет не найден",
        ];
    }
}
