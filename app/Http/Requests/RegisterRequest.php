<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\StrongPassword;
use App\Rules\RussianPhone;

class RegisterRequest extends FormRequest
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
            'phone' => ['required', 'string', 'max:20', new RussianPhone()],
            'password' => ['required', 'string', 'confirmed', new StrongPassword()],
        ];
    }

    
     * Get custom messages for validator errors.
     *
     * @return array<string, string>

    public function messages(): array
    {
        return [
            
            'name.required' => 'Пожалуйста, укажите ваше имя',
            'name.min' => 'Имя должно содержать не менее 2 символов',
            'name.max' => 'Имя не должно превышать 255 символов',
            
            
            'email.required' => 'Пожалуйста, укажите email адрес',
            'email.email' => 'Введите корректный email адрес',
            'email.max' => 'Email не должен превышать 255 символов',
            'email.unique' => 'Пользователь с таким email уже существует',
            
            
            'phone.required' => 'Пожалуйста, укажите номер телефона',
            'phone.min' => 'Номер телефона должен содержать не менее 10 символов',
            'phone.max' => 'Номер телефона не должен превышать 20 символов',
            
            'password.required' => 'Пожалуйста, введите пароль',
            'password.confirmed' => 'Пароли не совпадают',
        ];
    }

    
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>

    public function attributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email адрес',
            'phone' => 'номер телефона',
            'password' => 'пароль',
        ];
    }
}
