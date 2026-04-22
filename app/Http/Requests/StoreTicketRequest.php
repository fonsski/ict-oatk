<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'title' => 'required|string|min:5|max:60',
            'category' => 'required|string|in:hardware,software,network,account,other',
            'priority' => 'required|string|in:low,medium,high,urgent',
            'description' => 'required|string|min:10|max:5000',
            'reporter_id' => 'nullable|string|max:50',
            'location_id' => 'nullable|exists:locations,id',
            'room_id' => 'nullable|exists:rooms,id',
            'equipment_id' => 'nullable|exists:equipment,id',
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
            'location_id.exists' => 'Выбранное местоположение не существует в системе',
            'room_id.exists' => 'Выбранный кабинет не существует в системе',
            'equipment_id.exists' => 'Выбранное оборудование не существует в системе',
            'reporter_id.max' => 'Идентификатор заявителя не должен превышать 50 символов',
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
            'title' => 'заголовок заявки',
            'category' => 'категория',
            'priority' => 'приоритет',
            'description' => 'описание',
            'location_id' => 'местоположение',
            'room_id' => 'кабинет',
            'equipment_id' => 'оборудование',
            'reporter_id' => 'идентификатор заявителя',
        ];
    }

}
