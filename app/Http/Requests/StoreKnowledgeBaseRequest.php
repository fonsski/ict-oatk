<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreKnowledgeBaseRequest extends FormRequest
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
            'title' => 'required|string|min:5|max:255',
            'category_id' => 'required|exists:knowledge_categories,id',
            'description' => 'nullable|string|min:10|max:1000',
            'content' => 'required|string|min:20|max:10000',
            'tags' => 'nullable|string|min:2|max:255',
        ];
    }

    
     * Get custom messages for validator errors.
     *
     * @return array<string, string>

    public function messages(): array
    {
        return [
            
            'title.required' => 'Пожалуйста, укажите заголовок статьи',
            'title.min' => 'Заголовок должен содержать не менее 5 символов',
            'title.max' => 'Заголовок не должен превышать 255 символов',
            
            
            'category_id.required' => 'Пожалуйста, выберите категорию статьи',
            'category_id.exists' => 'Выбранная категория не существует в системе',
            
            
            'description.min' => 'Описание должно содержать не менее 10 символов',
            'description.max' => 'Описание не должно превышать 1000 символов',
            
            
            'content.required' => 'Пожалуйста, добавьте содержимое статьи',
            'content.min' => 'Содержимое статьи должно содержать не менее 20 символов',
            'content.max' => 'Содержимое статьи не должно превышать 10000 символов',
            
            
            'tags.min' => 'Теги должны содержать не менее 2 символов',
            'tags.max' => 'Теги не должны превышать 255 символов',
        ];
    }

    
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>

    public function attributes(): array
    {
        return [
            'title' => 'заголовок статьи',
            'category_id' => 'категория статьи',
            'description' => 'описание статьи',
            'content' => 'содержимое статьи',
            'tags' => 'теги',
        ];
    }
}
