<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
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
            'name' => 'required|string|min:2|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:20|unique:users|regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/',
            'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:8|confirmed|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/',
            'is_active' => 'boolean',
        ];
    }

    
     * Get custom messages for validator errors.
     *
     * @return array<string, string>

    public function messages(): array
    {
        return [
            'name.required' => 'Пожалуйста, укажите имя пользователя',
            'name.min' => 'Имя пользователя должно содержать не менее 2 символов',
            'name.max' => 'Имя пользователя не должно превышать 255 символов',
            'email.required' => 'Пожалуйста, укажите email адрес',
            'email.email' => 'Введите корректный email адрес',
            'email.max' => 'Email не должен превышать 255 символов',
            'email.unique' => 'Пользователь с таким email уже существует',
            'phone.required' => 'Пожалуйста, укажите номер телефона',
            'phone.max' => 'Номер телефона не должен превышать 20 символов',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует',
            'phone.regex' => 'Номер телефона должен быть в формате: +7 (999) 999-99-99',
            'role_id.required' => 'Пожалуйста, выберите роль пользователя',
            'role_id.exists' => 'Выбранная роль не существует в системе',
            'password.required' => 'Пожалуйста, укажите пароль',
            'password.min' => 'Пароль должен содержать не менее 8 символов',
            'password.regex' => 'Пароль должен содержать минимум одну строчную букву, одну заглавную букву и одну цифру',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }

    
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>

    public function attributes(): array
    {
        return [
            'name' => 'имя пользователя',
            'email' => 'email адрес',
            'phone' => 'номер телефона',
            'role_id' => 'роль',
            'password' => 'пароль',
            'is_active' => 'статус активности',
        ];
    }

}
