

class CharCounter {
    constructor(inputElement, options = {}) {
        this.input = inputElement;
        this.options = {
            maxLength: 60,
            minLength: 5,
            warningThreshold: 50,
            counterId: null,
            helpText: null,
            ...options
        };
        
        this.init();
    }
    
    init() {
        // Создаем контейнер для счетчика, если его нет
        this.createCounterContainer();
        
        // Добавляем обработчики событий
        this.bindEvents();
        
        // Инициализируем счетчик
        this.updateCounter();
    }
    
    createCounterContainer() {
        // Находим родительский контейнер поля
        const fieldContainer = this.input.closest('div');
        
        // Создаем контейнер для счетчика и подсказки
        let counterContainer = fieldContainer.querySelector('.char-counter-container');
        
        if (!counterContainer) {
            counterContainer = document.createElement('div');
            counterContainer.className = 'char-counter-container flex justify-between mt-1';
            
            // Создаем подсказку
            const helpText = document.createElement('p');
            helpText.className = 'text-sm text-gray-500';
            helpText.textContent = this.options.helpText || `Минимум ${this.options.minLength}, максимум ${this.options.maxLength} символов`;
            
            // Создаем счетчик
            const counter = document.createElement('div');
            counter.className = 'text-xs text-gray-500 font-medium';
            counter.id = this.options.counterId || `${this.input.id}CharCounter`;
            
            counterContainer.appendChild(helpText);
            counterContainer.appendChild(counter);
            
            // Вставляем контейнер после поля ввода
            fieldContainer.appendChild(counterContainer);
        }
        
        this.counterElement = counterContainer.querySelector('div:last-child');
    }
    
    bindEvents() {
        // Обработчик изменения текста
        this.input.addEventListener('input', () => this.updateCounter());
        this.input.addEventListener('keyup', () => this.updateCounter());
        this.input.addEventListener('paste', () => {
            // Небольшая задержка для обработки вставленного текста
            setTimeout(() => this.updateCounter(), 10);
        });
    }
    
    updateCounter() {
        const currentLength = this.input.value.length;
        const maxLength = this.options.maxLength;
        
        // Обновляем текст счетчика
        this.counterElement.textContent = `${currentLength}/${maxLength} символов`;
        
        // Устанавливаем цвет в зависимости от количества символов
        this.counterElement.classList.remove('text-gray-500', 'text-orange-500', 'text-red-500');
        
        if (currentLength >= maxLength) {
            this.counterElement.classList.add('text-red-500');
        } else if (currentLength > this.options.warningThreshold) {
            this.counterElement.classList.add('text-orange-500');
        } else {
            this.counterElement.classList.add('text-gray-500');
        }
        
        // Добавляем визуальную индикацию при превышении лимита
        if (currentLength > maxLength) {
            this.input.classList.add('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            this.input.classList.remove('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
        } else {
            this.input.classList.remove('border-red-300', 'focus:border-red-500', 'focus:ring-red-500');
            this.input.classList.add('border-gray-300', 'focus:border-blue-500', 'focus:ring-blue-500');
        }
    }
    
    // Публичные методы для управления
    getCurrentLength() {
        return this.input.value.length;
    }
    
    getRemainingChars() {
        return this.options.maxLength - this.getCurrentLength();
    }
    
    isValid() {
        const length = this.getCurrentLength();
        return length >= this.options.minLength && length <= this.options.maxLength;
    }
}

// Автоматическая инициализация для всех полей с атрибутом data-char-counter
document.addEventListener('DOMContentLoaded', function() {
    const charCounterFields = document.querySelectorAll('[data-char-counter]');
    
    charCounterFields.forEach(field => {
        const options = {
            maxLength: parseInt(field.getAttribute('data-max-length')) || 60,
            minLength: parseInt(field.getAttribute('data-min-length')) || 5,
            warningThreshold: parseInt(field.getAttribute('data-warning-threshold')) || 50,
            counterId: field.getAttribute('data-counter-id'),
            helpText: field.getAttribute('data-help-text')
        };
        
        new CharCounter(field, options);
    });
});

// Экспорт для использования в других модулях
window.CharCounter = CharCounter;
