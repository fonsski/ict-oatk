<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTicketRequest extends FormRequest
{
    
     * Determine if the user is authorized to make this request.

    public function authorize(): bool
    {
        return true; 
    }

    
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:5|max:60',
            'category' => 'required|string|in:hardware,software,network,account,other',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'description' => 'required|string|min:10|max:5000',
            'reporter_name' => 'nullable|string|max:255',
            'reporter_id' => 'nullable|string|max:50',
            'reporter_phone' => [
                'nullable',
                'string',
                'max:20',
                'regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/',
            ],
            'location_id' => 'nullable|exists:locations,id',
            'room_id' => 'nullable|exists:rooms,id',
            'equipment_id' => 'nullable|exists:equipment,id',
            'status' => 'nullable|string|in:open,in_progress,resolved,closed',
        ];
    }

    
     * Get custom messages for validator errors.
     *
     * @return array<string, string>

    public function messages(): array
    {
        return [
            'title.required' => 'Пожалуйста, укажите заголовок заявки',
            'title.min' => 'Заголовок должен содержать не менее 5 символов',
            'title.max' => 'Заголовок не должен превышать 60 символов',
            'category.required' => 'Пожалуйста, выберите категорию заявки',
            'category.in' => 'Выбрана недопустимая категория заявки',
            'priority.required' => 'Пожалуйста, выберите приоритет заявки',
            'priority.in' => 'Выбран недопустимый приоритет заявки',
            'description.required' => 'Пожалуйста, добавьте описание проблемы',
            'description.min' => 'Описание должно содержать не менее 10 символов',
            'description.max' => 'Описание не должно превышать 5000 символов',
            'reporter_name.max' => 'Имя заявителя не должно превышать 255 символов',
            'reporter_phone.max' => 'Номер телефона не должен превышать 20 символов',
            'reporter_phone.regex' => 'Номер телефона должен быть в формате: +7 (999) 999-99-99',
            'location_id.exists' => 'Выбранное местоположение не существует',
            'room_id.exists' => 'Выбранный кабинет не существует',
            'equipment_id.exists' => 'Выбранное оборудование не существует',
            'status.required' => 'Пожалуйста, укажите статус заявки',
            'status.in' => 'Выбран недопустимый статус заявки',
            'reporter_id.max' => 'Идентификатор заявителя не должен превышать 50 символов',
        ];
    }

    
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>

    public function attributes(): array
    {
        return [
            'title' => 'заголовок заявки',
            'category' => 'категория',
            'priority' => 'приоритет',
            'description' => 'описание',
            'reporter_name' => 'имя заявителя',
            'reporter_phone' => 'телефон заявителя',
            'location_id' => 'местоположение',
            'room_id' => 'кабинет',
            'equipment_id' => 'оборудование',
            'status' => 'статус',
            'reporter_id' => 'идентификатор заявителя',
        ];
    }

}
