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

        $this->normalizeRecurrence();
    }

    /**
     * Приводит поля повтора к тому виду, в котором их ждёт модель.
     *
     * Форма присылает дни недели массивом и режим окончания отдельным
     * переключателем; здесь дни склеиваются в строку «MO,WE», а «до даты»
     * и «N раз» очищают друг друга. Без частоты весь блок обнуляется.
     */
    private function normalizeRecurrence(): void
    {
        $freq = $this->input("recurrence_freq");

        if (!$freq) {
            $this->merge([
                "recurrence_freq" => null,
                "recurrence_interval" => 1,
                "recurrence_byday" => null,
                "recurrence_until" => null,
                "recurrence_count" => null,
            ]);
            return;
        }

        // Дни недели важны только для еженедельного повтора.
        $byday = null;
        if ($freq === CalendarEvent::FREQ_WEEKLY) {
            $days = (array) $this->input("recurrence_byday", []);
            $valid = ["MO", "TU", "WE", "TH", "FR", "SA", "SU"];
            $byday = collect($days)
                ->map(fn ($d) => strtoupper((string) $d))
                ->filter(fn ($d) => in_array($d, $valid, true))
                ->unique()
                ->implode(",");
            $byday = $byday !== "" ? $byday : null;
        }

        // Режим окончания: never | until | count.
        $mode = $this->input("recurrence_end_mode", "never");
        $until = $mode === "until" ? $this->input("recurrence_until") : null;
        $count = $mode === "count" ? $this->input("recurrence_count") : null;

        $this->merge([
            "recurrence_freq" => $freq,
            "recurrence_interval" => 1,
            "recurrence_byday" => $byday,
            "recurrence_until" => $until,
            "recurrence_count" => $count,
        ]);
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
            "participant_ids" => "nullable|array",
            "participant_ids.*" => "integer|exists:users,id",
            "recurrence_freq" => ["nullable", Rule::in(array_keys(CalendarEvent::FREQUENCIES))],
            "recurrence_byday" => "nullable|string|max:20",
            "recurrence_until" => "nullable|date|after_or_equal:starts_at",
            "recurrence_count" => "nullable|integer|min:1|max:365",
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
