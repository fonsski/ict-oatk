<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateKnowledgeCategoryRequest extends FormRequest
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
            'name' => [
                'required',
                'string',
                'min:2',
                'max:255',
                Rule::unique('knowledge_categories', 'name')->ignore($this->route('category'))
            ],
            'description' => 'nullable|string|min:5|max:500',
            'icon' => 'nullable|string|min:1|max:100',
            'color' => 'required|string|regex:/^
            'sort_order' => 'nullable|integer|min:0|max:9999',
            'is_active' => 'boolean',
        ];
    }

    
     * Get custom messages for validator errors.
     *
     * @return array<string, string>

    public function messages(): array
    {
        return [
            
            'name.required' => 'Пожалуйста, укажите название категории',
            'name.min' => 'Название категории должно содержать не менее 2 символов',
            'name.max' => 'Название категории не должно превышать 255 символов',
            'name.unique' => 'Категория с таким названием уже существует в системе',
            
            
            'description.min' => 'Описание категории должно содержать не менее 5 символов',
            'description.max' => 'Описание категории не должно превышать 500 символов',
            
            
            'icon.min' => 'Название иконки должно содержать не менее 1 символа',
            'icon.max' => 'Название иконки не должно превышать 100 символов',
            
            
            'color.required' => 'Пожалуйста, выберите цвет категории',
            'color.regex' => 'Цвет должен быть в формате HEX (
            
            
            'sort_order.integer' => 'Порядок сортировки должен быть числом',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным',
            'sort_order.max' => 'Порядок сортировки не должен превышать 9999',
            
            
            'is_active.boolean' => 'Поле активности должно быть логическим значением',
        ];
    }

    
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>

    public function attributes(): array
    {
        return [
            'name' => 'название категории',
            'description' => 'описание категории',
            'icon' => 'иконка',
            'color' => 'цвет категории',
            'sort_order' => 'порядок сортировки',
            'is_active' => 'активность',
        ];
    }
}
