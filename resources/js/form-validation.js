/**
 * Клиентская валидация форм
 * Обеспечивает валидацию на стороне клиента для улучшения UX
 */

class FormValidator {
    constructor(formSelector, options = {}) {
        this.form = document.querySelector(formSelector);
        this.options = {
            showErrorsInline: true,
            validateOnBlur: true,
            validateOnInput: false,
            customRules: {},
            ...options
        };
        
        this.errors = {};
        this.init();
    }
    
    init() {
        if (!this.form) {
            console.error('FormValidator: Форма не найдена:', this.formSelector);
            return;
        }
        
        this.setupEventListeners();
        this.setupCustomValidation();
    }
    
    setupEventListeners() {
        // Валидация при потере фокуса
        if (this.options.validateOnBlur) {
            this.form.addEventListener('blur', (e) => {
                if (e.target.matches('input, textarea, select')) {
                    this.validateField(e.target);
                }
            }, true);
        }
        
        // Валидация при вводе
        if (this.options.validateOnInput) {
            this.form.addEventListener('input', (e) => {
                if (e.target.matches('input, textarea, select')) {
                    this.validateField(e.target);
                }
            });
        }
        
        // Валидация при отправке формы
        this.form.addEventListener('submit', (e) => {
            if (!this.validateForm()) {
                e.preventDefault();
                this.showFormErrors();
            }
        });
    }
    
    setupCustomValidation() {
        // Валидация пароля
        this.addCustomRule('password', (value) => {
            const minLength = 8;
            const hasLower = /[a-z]/.test(value);
            const hasUpper = /[A-Z]/.test(value);
            const hasNumber = /\d/.test(value);
            
            if (value.length < minLength) {
                return `Пароль должен содержать не менее ${minLength} символов`;
            }
            if (!hasLower) {
                return 'Пароль должен содержать минимум одну строчную букву';
            }
            if (!hasUpper) {
                return 'Пароль должен содержать минимум одну заглавную букву';
            }
            if (!hasNumber) {
                return 'Пароль должен содержать минимум одну цифру';
            }
            return true;
        });
        
        // Валидация телефона
        this.addCustomRule('phone', (value) => {
            const phoneRegex = /^\+7 \([0-9]{3}\) [0-9]{3}-[0-9]{2}-[0-9]{2}$/;
            if (!phoneRegex.test(value)) {
                return 'Номер телефона должен быть в формате: +7 (999) 999-99-99';
            }
            return true;
        });
        
        // Валидация email
        this.addCustomRule('email', (value) => {
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(value)) {
                return 'Введите корректный email адрес';
            }
            return true;
        });
        
        // Валидация подтверждения пароля
        this.addCustomRule('password_confirmation', (value) => {
            const passwordField = this.form.querySelector('input[name="password"]');
            if (passwordField && value !== passwordField.value) {
                return 'Пароли не совпадают';
            }
            return true;
        });
    }
    
    addCustomRule(name, validator) {
        this.options.customRules[name] = validator;
    }
    
    validateField(field) {
        const fieldName = field.name;
        const value = field.value.trim();
        
        // Очищаем предыдущие ошибки для поля
        this.clearFieldError(field);
        
        // Получаем правила валидации для поля
        const rules = this.getFieldRules(field);
        
        // Проверяем каждое правило
        for (const rule of rules) {
            const error = this.validateRule(value, rule, field);
            if (error) {
                this.setFieldError(field, error);
                return false;
            }
        }
        
        return true;
    }
    
    getFieldRules(field) {
        const rules = [];
        
        // Обязательные поля
        if (field.hasAttribute('required')) {
            rules.push({ type: 'required' });
        }
        
        // Минимальная длина
        if (field.hasAttribute('minlength')) {
            rules.push({ 
                type: 'minlength', 
                value: parseInt(field.getAttribute('minlength')) 
            });
        }
        
        // Максимальная длина
        if (field.hasAttribute('maxlength')) {
            rules.push({ 
                type: 'maxlength', 
                value: parseInt(field.getAttribute('maxlength')) 
            });
        }
        
        // Паттерн
        if (field.hasAttribute('pattern')) {
            rules.push({ 
                type: 'pattern', 
                value: field.getAttribute('pattern') 
            });
        }
        
        // Email поля
        if (field.type === 'email') {
            rules.push({ type: 'email' });
        }
        
        // Поля пароля
        if (field.type === 'password') {
            if (field.name === 'password') {
                rules.push({ type: 'password' });
            } else if (field.name === 'password_confirmation') {
                rules.push({ type: 'password_confirmation' });
            }
        }
        
        // Поля телефона
        if (field.type === 'tel' || field.name.includes('phone')) {
            rules.push({ type: 'phone' });
        }
        
        return rules;
    }
    
    validateRule(value, rule, field) {
        switch (rule.type) {
            case 'required':
                if (!value) {
                    return 'Это поле обязательно для заполнения';
                }
                break;
                
            case 'minlength':
                if (value.length < rule.value) {
                    return `Минимальная длина: ${rule.value} символов`;
                }
                break;
                
            case 'maxlength':
                if (value.length > rule.value) {
                    return `Максимальная длина: ${rule.value} символов`;
                }
                break;
                
            case 'pattern':
                const regex = new RegExp(rule.value);
                if (!regex.test(value)) {
                    return 'Неверный формат данных';
                }
                break;
                
            case 'email':
                return this.options.customRules.email(value);
                
            case 'password':
                return this.options.customRules.password(value);
                
            case 'password_confirmation':
                return this.options.customRules.password_confirmation(value);
                
            case 'phone':
                return this.options.customRules.phone(value);
        }
        
        return null;
    }
    
    setFieldError(field, message) {
        this.errors[field.name] = message;
        
        if (this.options.showErrorsInline) {
            this.showInlineError(field, message);
        }
        
        // Добавляем класс ошибки
        field.classList.add('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        field.classList.remove('border-green-500', 'focus:border-green-500', 'focus:ring-green-500');
    }
    
    clearFieldError(field) {
        delete this.errors[field.name];
        
        if (this.options.showErrorsInline) {
            this.clearInlineError(field);
        }
        
        // Убираем класс ошибки
        field.classList.remove('border-red-500', 'focus:border-red-500', 'focus:ring-red-500');
        
        // Если поле валидно, добавляем зеленый цвет
        if (field.value.trim()) {
            field.classList.add('border-green-500', 'focus:border-green-500', 'focus:ring-green-500');
        }
    }
    
    showInlineError(field, message) {
        // Удаляем предыдущую ошибку
        this.clearInlineError(field);
        
        // Создаем элемент ошибки
        const errorElement = document.createElement('div');
        errorElement.className = 'text-red-500 text-sm mt-1';
        errorElement.textContent = message;
        errorElement.setAttribute('data-field-error', field.name);
        
        // Вставляем после поля
        field.parentNode.insertBefore(errorElement, field.nextSibling);
    }
    
    clearInlineError(field) {
        const existingError = field.parentNode.querySelector(`[data-field-error="${field.name}"]`);
        if (existingError) {
            existingError.remove();
        }
    }
    
    validateForm() {
        const fields = this.form.querySelectorAll('input, textarea, select');
        let isValid = true;
        
        this.errors = {};
        
        fields.forEach(field => {
            if (!this.validateField(field)) {
                isValid = false;
            }
        });
        
        return isValid;
    }
    
    showFormErrors() {
        // Показываем общее сообщение об ошибках
        const errorSummary = document.createElement('div');
        errorSummary.className = 'bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4';
        errorSummary.innerHTML = `
            <strong>Ошибки валидации:</strong>
            <ul class="mt-2 list-disc list-inside">
                ${Object.values(this.errors).map(error => `<li>${error}</li>`).join('')}
            </ul>
        `;
        
        // Вставляем в начало формы
        this.form.insertBefore(errorSummary, this.form.firstChild);
        
        // Автоматически удаляем через 5 секунд
        setTimeout(() => {
            if (errorSummary.parentNode) {
                errorSummary.remove();
            }
        }, 5000);
    }
    
    // Публичные методы для внешнего использования
    isValid() {
        return Object.keys(this.errors).length === 0;
    }
    
    getErrors() {
        return this.errors;
    }
    
    clearErrors() {
        this.errors = {};
        const fields = this.form.querySelectorAll('input, textarea, select');
        fields.forEach(field => this.clearFieldError(field));
    }
}

// Инициализация валидации для всех форм на странице
document.addEventListener('DOMContentLoaded', function() {
    // Инициализируем валидацию для всех форм с классом 'needs-validation'
    const forms = document.querySelectorAll('form.needs-validation');
    forms.forEach(form => {
        new FormValidator(`#${form.id}`, {
            validateOnBlur: true,
            validateOnInput: true,
            showErrorsInline: true
        });
    });
});

// Экспорт для использования в других модулях
if (typeof window !== 'undefined') {
    window.FormValidator = FormValidator;
}
