<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomepageFAQRequest extends FormRequest
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
            'title' => 'sometimes|required|string|min:5|max:255',
            'excerpt' => 'sometimes|nullable|string|min:10|max:500',
            'content' => 'sometimes|required|string|min:20|max:5000',
            'is_active' => 'sometimes|boolean',
            'sort_order' => 'sometimes|nullable|integer|min:0|max:9999',
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
            // Заголовок
            'title.required' => 'Пожалуйста, укажите заголовок FAQ',
            'title.min' => 'Заголовок должен содержать не менее 5 символов',
            'title.max' => 'Заголовок не должен превышать 255 символов',
            
            // Краткое описание
            'excerpt.min' => 'Краткое описание должно содержать не менее 10 символов',
            'excerpt.max' => 'Краткое описание не должно превышать 500 символов',
            
            // Содержимое
            'content.required' => 'Пожалуйста, добавьте содержимое FAQ',
            'content.min' => 'Содержимое должно содержать не менее 20 символов',
            'content.max' => 'Содержимое не должно превышать 5000 символов',
            
            // Активность
            'is_active.boolean' => 'Поле активности должно быть логическим значением',
            
            // Порядок сортировки
            'sort_order.integer' => 'Порядок сортировки должен быть числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
            'sort_order.max' => 'Порядок сортировки не должен превышать 9999',
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
            'title' => 'заголовок FAQ',
            'excerpt' => 'краткое описание',
            'content' => 'содержимое FAQ',
            'is_active' => 'активность',
            'sort_order' => 'порядок сортировки',
        ];
    }
}
