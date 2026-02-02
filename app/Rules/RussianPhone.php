<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;


 * Правило валидации для российского номера телефона
 * Проверяет формат +7 (XXX) XXX-XX-XX

class RussianPhone implements Rule
{
    
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool

    public function passes($attribute, $value)
    {
        
        return preg_match('/^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/', $value);
    }
    
    
     * Get the validation error message.
     *
     * @return string

    public function message()
    {
        return 'Номер телефона должен быть в формате: +7 (999) 999-99-99';
    }
}
