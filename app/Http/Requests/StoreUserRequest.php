<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8|confirmed',
            'is_active' => 'boolean',
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
            'name.required' => 'Пожалуйста, укажите имя пользователя',
            'name.max' => 'Имя пользователя не должно превышать 255 символов',
            'phone.required' => 'Пожалуйста, укажите номер телефона',
            'phone.max' => 'Номер телефона не должен превышать 20 символов',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует',
            'role_id.required' => 'Пожалуйста, выберите роль пользователя',
            'role_id.exists' => 'Выбранная роль не существует в системе',
            'password.required' => 'Пожалуйста, укажите пароль',
            'password.min' => 'Пароль должен содержать не менее 8 символов',
            'password.confirmed' => 'Пароли не совпадают',
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
            'name' => 'имя пользователя',
            'phone' => 'номер телефона',
            'role_id' => 'роль',
            'password' => 'пароль',
            'is_active' => 'статус активности',
        ];
    }

}
