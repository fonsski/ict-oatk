<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEquipmentCategoryRequest extends FormRequest
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
                Rule::unique('equipment_categories', 'name')->ignore($this->route('equipmentCategory')->id)
            ],
            'description' => 'nullable|string|min:5|max:1000',
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
            'description.max' => 'Описание категории не должно превышать 1000 символов',
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
        ];
    }
}
