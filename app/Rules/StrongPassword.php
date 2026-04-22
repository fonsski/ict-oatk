<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

/**
 * Правило валидации для сильного пароля
 * Проверяет наличие строчных, заглавных букв и цифр
 */
class StrongPassword implements Rule
{
    /**
     * Минимальная длина пароля
     */
    protected $minLength;
    
    /**
     * Требовать специальные символы
     */
    protected $requireSpecialChars;
    
    /**
     * Create a new rule instance.
     */
    public function __construct(int $minLength = 8, bool $requireSpecialChars = false)
    {
        $this->minLength = $minLength;
        $this->requireSpecialChars = $requireSpecialChars;
    }
    
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (strlen($value) < $this->minLength) {
            return false;
        }
        
        // Проверяем наличие строчных букв
        if (!preg_match('/[a-z]/', $value)) {
            return false;
        }
        
        // Проверяем наличие заглавных букв
        if (!preg_match('/[A-Z]/', $value)) {
            return false;
        }
        
        // Проверяем наличие цифр
        if (!preg_match('/\d/', $value)) {
            return false;
        }
        
        // Проверяем наличие специальных символов (если требуется)
        if ($this->requireSpecialChars && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $value)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        $message = "Пароль должен содержать минимум {$this->minLength} символов, включая строчные и заглавные буквы, а также цифры";
        
        if ($this->requireSpecialChars) {
            $message .= " и специальные символы";
        }
        
        return $message;
    }
}
