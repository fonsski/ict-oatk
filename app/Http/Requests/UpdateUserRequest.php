<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
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
    /**
     * См. StoreUserRequest: номер приводим к хранимому виду до проверок.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone')) {
            $this->merge([
                'phone' => normalize_phone($this->input('phone')) ?? $this->input('phone'),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'position' => 'nullable|string|max:255',
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users')->ignore($this->route('user')),
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^\+7[0-9]{10}$/',
                Rule::unique('users')->ignore($this->route('user')),
            ],
            'role_id' => 'required|exists:roles,id',
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
            'name.min' => 'Имя пользователя должно содержать не менее 2 символов',
            'name.max' => 'Имя пользователя не должно превышать 255 символов',
            'email.email' => 'Введите корректный email адрес',
            'email.max' => 'Email не должен превышать 255 символов',
            'email.unique' => 'Пользователь с таким email уже существует',
            'phone.required' => 'Пожалуйста, укажите номер телефона',
            'phone.regex' => 'Не похоже на российский номер. Подойдёт любая запись: +7 900 123-45-67, 8 (900) 123-45-67, 9001234567',
            'phone.unique' => 'Пользователь с таким номером телефона уже существует',
            'role_id.required' => 'Пожалуйста, выберите роль пользователя',
            'role_id.exists' => 'Выбранная роль не существует в системе',
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
            'position' => 'должность',
            'email' => 'email адрес',
            'phone' => 'номер телефона',
            'role_id' => 'роль',
            'is_active' => 'статус активности',
        ];
    }

}
