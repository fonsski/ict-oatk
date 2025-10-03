<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEquipmentServiceRequest extends FormRequest
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
            'service_date' => 'required|date|before_or_equal:today',
            'service_type' => 'required|string|in:regular,repair,diagnostic,cleaning,update,calibration,other',
            'description' => 'required|string|min:10|max:1000',
            'next_service_date' => 'nullable|date|after:today',
            'service_result' => 'required|string|in:success,partial,failed,pending',
            'problems_found' => 'nullable|string|min:5|max:1000',
            'problems_fixed' => 'nullable|string|min:5|max:1000',
            'attachments' => 'nullable|array|max:5',
            'attachments.*' => 'file|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp|max:10240',
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
            // Дата обслуживания
            'service_date.required' => 'Пожалуйста, укажите дату обслуживания',
            'service_date.date' => 'Дата обслуживания должна быть корректной датой',
            'service_date.before_or_equal' => 'Дата обслуживания не может быть в будущем',
            
            // Тип обслуживания
            'service_type.required' => 'Пожалуйста, выберите тип обслуживания',
            'service_type.in' => 'Выбран недопустимый тип обслуживания',
            
            // Описание
            'description.required' => 'Пожалуйста, добавьте описание проведённого обслуживания',
            'description.min' => 'Описание должно содержать не менее 10 символов',
            'description.max' => 'Описание не должно превышать 1000 символов',
            
            // Следующее обслуживание
            'next_service_date.date' => 'Дата следующего обслуживания должна быть корректной датой',
            'next_service_date.after' => 'Дата следующего обслуживания должна быть в будущем',
            
            // Результат обслуживания
            'service_result.required' => 'Пожалуйста, укажите результат обслуживания',
            'service_result.in' => 'Выбран недопустимый результат обслуживания',
            
            // Найденные проблемы
            'problems_found.min' => 'Описание найденных проблем должно содержать не менее 5 символов',
            'problems_found.max' => 'Описание найденных проблем не должно превышать 1000 символов',
            
            // Исправленные проблемы
            'problems_fixed.min' => 'Описание исправленных проблем должно содержать не менее 5 символов',
            'problems_fixed.max' => 'Описание исправленных проблем не должно превышать 1000 символов',
            
            // Вложения
            'attachments.max' => 'Можно прикрепить не более 5 файлов',
            'attachments.*.file' => 'Прикреплённый файл должен быть корректным файлом',
            'attachments.*.mimes' => 'Поддерживаются только файлы: PDF, DOC, DOCX, XLS, XLSX, JPG, JPEG, PNG, GIF, WEBP',
            'attachments.*.max' => 'Размер файла не должен превышать 10 МБ',
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
            'service_date' => 'дата обслуживания',
            'service_type' => 'тип обслуживания',
            'description' => 'описание обслуживания',
            'next_service_date' => 'дата следующего обслуживания',
            'service_result' => 'результат обслуживания',
            'problems_found' => 'найденные проблемы',
            'problems_fixed' => 'исправленные проблемы',
            'attachments' => 'вложения',
        ];
    }
}
