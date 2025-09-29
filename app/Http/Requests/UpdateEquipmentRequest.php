<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Авторизация проверяется в контроллере
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'sometimes|nullable|string|min:2|max:255',
            'inventory_number' => [
                'sometimes',
                'required',
                'string',
                'min:3',
                'max:255',
                Rule::unique('equipment', 'inventory_number')->ignore($this->route('equipment')->id)
            ],
            'category_id' => 'sometimes|nullable|exists:equipment_categories,id',
            'status_id' => 'sometimes|required|exists:equipment_statuses,id',
            'room_id' => 'sometimes|nullable|exists:rooms,id',
            'initial_room_id' => 'sometimes|nullable|exists:rooms,id',
            'has_warranty' => 'sometimes|boolean',
            'warranty_end_date' => 'sometimes|nullable|date|after_or_equal:today|required_if:has_warranty,1',
            'last_service_date' => 'sometimes|nullable|date|before_or_equal:today',
            'service_comment' => 'sometimes|nullable|string|min:5|max:500',
            'known_issues' => 'sometimes|nullable|string|min:5|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Название оборудования
            'name.min' => 'Название оборудования должно содержать не менее 2 символов',
            'name.max' => 'Название оборудования не должно превышать 255 символов',
            
            // Инвентарный номер
            'inventory_number.required' => 'Пожалуйста, укажите инвентарный номер',
            'inventory_number.min' => 'Инвентарный номер должен содержать не менее 3 символов',
            'inventory_number.max' => 'Инвентарный номер не должен превышать 255 символов',
            'inventory_number.unique' => 'Оборудование с таким инвентарным номером уже существует в системе',
            
            // Категория
            'category_id.exists' => 'Выбранная категория оборудования не существует в системе',
            
            // Статус
            'status_id.required' => 'Пожалуйста, выберите статус оборудования',
            'status_id.exists' => 'Выбранный статус оборудования не существует в системе',
            
            // Кабинеты
            'room_id.exists' => 'Выбранный текущий кабинет не существует в системе',
            'initial_room_id.exists' => 'Выбранный начальный кабинет не существует в системе',
            
            // Гарантия
            'has_warranty.boolean' => 'Поле гарантии должно быть логическим значением',
            'warranty_end_date.date' => 'Дата окончания гарантии должна быть корректной датой',
            'warranty_end_date.after_or_equal' => 'Дата окончания гарантии не может быть в прошлом',
            'warranty_end_date.required_if' => 'При наличии гарантии необходимо указать дату её окончания',
            
            // Обслуживание
            'last_service_date.date' => 'Дата последнего обслуживания должна быть корректной датой',
            'last_service_date.before_or_equal' => 'Дата последнего обслуживания не может быть в будущем',
            
            // Комментарии
            'service_comment.min' => 'Комментарий о проведённом обслуживании должен содержать не менее 5 символов',
            'service_comment.max' => 'Комментарий о проведённом обслуживании не должен превышать 500 символов',
            'known_issues.min' => 'Описание известных проблем должно содержать не менее 5 символов',
            'known_issues.max' => 'Описание известных проблем не должно превышать 500 символов',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => 'название оборудования',
            'inventory_number' => 'инвентарный номер',
            'category_id' => 'категория оборудования',
            'status_id' => 'статус оборудования',
            'room_id' => 'текущий кабинет',
            'initial_room_id' => 'начальный кабинет',
            'has_warranty' => 'наличие гарантии',
            'warranty_end_date' => 'дата окончания гарантии',
            'last_service_date' => 'дата последнего обслуживания',
            'service_comment' => 'комментарий о проведённом обслуживании',
            'known_issues' => 'известные проблемы',
        ];
    }
}
