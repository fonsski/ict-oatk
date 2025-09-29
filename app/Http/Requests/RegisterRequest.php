<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'phone' => 'required|string|min:10|max:20|regex:/^[0-9+\-\s\(\)]+$/',
            'password' => 'required|string|min:8|confirmed',
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
            // Имя
            'name.required' => 'Пожалуйста, укажите ваше имя',
            'name.min' => 'Имя должно содержать не менее 2 символов',
            'name.max' => 'Имя не должно превышать 255 символов',
            
            // Телефон
            'phone.required' => 'Пожалуйста, укажите номер телефона',
            'phone.min' => 'Номер телефона должен содержать не менее 10 символов',
            'phone.max' => 'Номер телефона не должен превышать 20 символов',
            'phone.regex' => 'Номер телефона может содержать только цифры, +, -, пробелы и скобки',
            
            // Пароль
            'password.required' => 'Пожалуйста, введите пароль',
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
            'name' => 'имя',
            'phone' => 'номер телефона',
            'password' => 'пароль',
        ];
    }
}
