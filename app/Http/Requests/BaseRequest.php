<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;


 * Базовый класс для всех Request классов
 * Содержит общие методы валидации и обработки ошибок

abstract class BaseRequest extends FormRequest
{
    
     * Общие правила валидации для всех форм

    protected function getCommonRules(): array
    {
        return [
            
        ];
    }
    
    
     * Общие сообщения об ошибках

    protected function getCommonMessages(): array
    {
        return [
            'required' => 'Поле :attribute обязательно для заполнения',
            'email' => 'Поле :attribute должно содержать корректный email адрес',
            'min' => 'Поле :attribute должно содержать минимум :min символов',
            'max' => 'Поле :attribute не должно превышать :max символов',
            'unique' => 'Поле :attribute уже используется',
            'exists' => 'Выбранное значение для поля :attribute не существует',
            'confirmed' => 'Поле :attribute не совпадает с подтверждением',
            'regex' => 'Поле :attribute имеет неверный формат',
            'date' => 'Поле :attribute должно быть корректной датой',
            'before_or_equal' => 'Поле :attribute не может быть в будущем',
            'after_or_equal' => 'Поле :attribute не может быть в прошлом',
            'required_if' => 'Поле :attribute обязательно при указанных условиях',
            'boolean' => 'Поле :attribute должно быть логическим значением',
            'array' => 'Поле :attribute должно быть массивом',
            'file' => 'Поле :attribute должно быть файлом',
            'mimes' => 'Поле :attribute должно быть файлом одного из типов: :values',
        ];
    }
    
    
     * Общие атрибуты полей

    protected function getCommonAttributes(): array
    {
        return [
            'name' => 'имя',
            'email' => 'email адрес',
            'phone' => 'номер телефона',
            'password' => 'пароль',
            'password_confirmation' => 'подтверждение пароля',
            'title' => 'заголовок',
            'description' => 'описание',
            'content' => 'содержимое',
            'category' => 'категория',
            'priority' => 'приоритет',
            'status' => 'статус',
            'is_active' => 'статус активности',
        ];
    }
    
    
     * Валидация телефона в российском формате

    protected function getPhoneValidationRule(): string
    {
        return 'regex:/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/';
    }
    
    
     * Валидация пароля с требованиями безопасности

    protected function getPasswordValidationRule(): string
    {
        return 'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/';
    }
    
    
     * Валидация email

    protected function getEmailValidationRule(): string
    {
        return 'email|max:255';
    }
    
    
     * Валидация имени пользователя

    protected function getNameValidationRule(): string
    {
        return 'string|min:2|max:255';
    }
    
    
     * Валидация даты (не в будущем)

    protected function getPastDateValidationRule(): string
    {
        return 'date|before_or_equal:today';
    }
    
    
     * Валидация даты (не в прошлом)

    protected function getFutureDateValidationRule(): string
    {
        return 'date|after_or_equal:today';
    }
    
    
     * Валидация файлов изображений

    protected function getImageValidationRule(): string
    {
        return 'file|mimes:jpg,jpeg,png,gif,webp|max:10240';
    }
    
    
     * Валидация документов

    protected function getDocumentValidationRule(): string
    {
        return 'file|mimes:pdf,doc,docx,xls,xlsx|max:10240';
    }
    
    
     * Валидация всех типов файлов

    protected function getAllFilesValidationRule(): string
    {
        return 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp|max:10240';
    }
    
    
     * Объединяет правила валидации

    protected function mergeRules(array $rules): array
    {
        return array_merge($this->getCommonRules(), $rules);
    }
    
    
     * Объединяет сообщения об ошибках

    protected function mergeMessages(array $messages): array
    {
        return array_merge($this->getCommonMessages(), $messages);
    }
    
    
     * Объединяет атрибуты полей

    protected function mergeAttributes(array $attributes): array
    {
        return array_merge($this->getCommonAttributes(), $attributes);
    }
}
