<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkUserActionRequest extends FormRequest
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
            'action' => 'required|in:activate,deactivate,delete,change_role',
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id',
            'new_role_id' => 'required_if:action,change_role|exists:roles,id',
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
            'action.required' => 'Пожалуйста, выберите действие',
            'action.in' => 'Выбрано недопустимое действие',
            'user_ids.required' => 'Пожалуйста, выберите пользователей',
            'user_ids.array' => 'Некорректный формат списка пользователей',
            'user_ids.min' => 'Необходимо выбрать хотя бы одного пользователя',
            'user_ids.*.exists' => 'Один или несколько выбранных пользователей не существуют',
            'new_role_id.required_if' => 'Для изменения роли необходимо выбрать новую роль',
            'new_role_id.exists' => 'Выбранная роль не существует',
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
            'action' => 'действие',
            'user_ids' => 'пользователи',
            'new_role_id' => 'новая роль',
        ];
    }
}
