

class InventoryNumberHelper {
    constructor(inputElement) {
        this.input = inputElement;
        this.suggestions = [];
        this.init();
    }
    
    init() {
        this.createSuggestionsContainer();
        this.bindEvents();
    }
    
    createSuggestionsContainer() {
        // Создаем контейнер для подсказок
        const container = document.createElement('div');
        container.className = 'inventory-suggestions absolute z-10 w-full bg-white border border-gray-300 rounded-md shadow-lg hidden';
        container.id = 'inventory-suggestions';
        
        // Вставляем контейнер после поля ввода
        this.input.parentNode.style.position = 'relative';
        this.input.parentNode.appendChild(container);
        
        this.suggestionsContainer = container;
    }
    
    bindEvents() {
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('focus', () => this.showSuggestions());
        this.input.addEventListener('blur', (e) => {
            // Небольшая задержка, чтобы пользователь мог кликнуть на подсказку
            setTimeout(() => this.hideSuggestions(), 200);
        });
        
        // Обработка клика вне поля
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.suggestionsContainer.contains(e.target)) {
                this.hideSuggestions();
            }
        });
    }
    
    handleInput(e) {
        const value = e.target.value.toLowerCase();
        
        if (value.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        // Генерируем подсказки на основе введенного текста
        this.generateSuggestions(value);
        this.showSuggestions();
    }
    
    generateSuggestions(input) {
        const suggestions = [];
        
        // Коды зданий
        const buildings = ['а', 'б'];
        // Этажи
        const floors = ['1', '2', '3', '4', '5'];
        // Группы пользователей (точные названия для валидации)
        const groups = ['студент', 'преподаватель', 'администрация', 'сотрудник'];
        
        // Если пользователь ввел только код здания
        if (input.length === 1 && buildings.includes(input)) {
            floors.forEach(floor => {
                groups.forEach(group => {
                    suggestions.push(`${input.toUpperCase()}${floor}-${group}-001`);
                });
            });
        }
        // Если пользователь ввел код здания и этаж
        else if (input.length === 2 && buildings.includes(input[0]) && floors.includes(input[1])) {
            groups.forEach(group => {
                suggestions.push(`${input.toUpperCase()}-${group}-001`);
            });
        }
        // Если пользователь ввел код здания, этаж и дефис
        else if (input.length === 3 && input.endsWith('-')) {
            groups.forEach(group => {
                suggestions.push(`${input.toUpperCase()}${group}-001`);
            });
        }
        // Если пользователь начал вводить группу
        else if (input.includes('-') && input.split('-').length === 2) {
            const [prefix, groupStart] = input.split('-');
            const matchingGroups = groups.filter(group => 
                group.startsWith(groupStart.toLowerCase())
            );
            matchingGroups.forEach(group => {
                suggestions.push(`${prefix.toUpperCase()}-${group}-001`);
            });
        }
        // Если пользователь ввел почти полный номер
        else if (input.includes('-') && input.split('-').length === 3) {
            const parts = input.split('-');
            if (parts.length === 3) {
                const [buildingFloor, group, number] = parts;
                if (buildingFloor && group && number) {
                    // Предлагаем завершенный номер
                    suggestions.push(`${buildingFloor.toUpperCase()}-${group}-${number.padStart(3, '0')}`);
                }
            }
        }
        
        this.suggestions = suggestions.slice(0, 5); // Ограничиваем количество подсказок
    }
    
    showSuggestions() {
        if (this.suggestions.length === 0) {
            this.hideSuggestions();
            return;
        }
        
        this.suggestionsContainer.innerHTML = '';
        
        this.suggestions.forEach((suggestion, index) => {
            const item = document.createElement('div');
            item.className = 'px-3 py-2 hover:bg-gray-100 cursor-pointer border-b border-gray-100 last:border-b-0';
            item.textContent = suggestion;
            item.addEventListener('click', () => {
                this.input.value = suggestion;
                this.hideSuggestions();
                this.input.focus();
            });
            this.suggestionsContainer.appendChild(item);
        });
        
        this.suggestionsContainer.classList.remove('hidden');
    }
    
    hideSuggestions() {
        this.suggestionsContainer.classList.add('hidden');
    }
}

// Автоматическая инициализация для полей учётного номера
document.addEventListener('DOMContentLoaded', function() {
    const accountingInputs = document.querySelectorAll('input[name="accounting_number"]');
    
    accountingInputs.forEach(input => {
        new InventoryNumberHelper(input);
    });
});

// Экспорт для использования в других модулях
window.InventoryNumberHelper = InventoryNumberHelper;
