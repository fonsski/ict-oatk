/**
 * Smart Updates - Умная система обновления данных
 * Обеспечивает обновление только измененных элементов без перерисовки всей страницы
 */

class SmartUpdates {
    constructor(options = {}) {
        this.options = {
            containerSelector: options.containerSelector,
            itemSelector: options.itemSelector,
            itemIdAttribute: options.itemIdAttribute || 'data-id',
            createItemHTML: options.createItemHTML,
            fieldsToCheck: options.fieldsToCheck || [],
            onItemUpdate: options.onItemUpdate || null,
            onItemAdd: options.onItemAdd || null,
            onItemRemove: options.onItemRemove || null,
            preserveState: options.preserveState || false,
            ...options
        };
        
        this.currentData = new Map();
        this.currentOrder = [];
        this.preservedStates = new Map();
        
        this.init();
    }
    
    init() {
        if (!this.options.containerSelector || !this.options.createItemHTML) {
            console.error('SmartUpdates: Необходимы containerSelector и createItemHTML');
            return;
        }
        
        this.container = document.querySelector(this.options.containerSelector);
        if (!this.container) {
            console.error('SmartUpdates: Контейнер не найден:', this.options.containerSelector);
            return;
        }
    }
    
    // Обновление данных с умным механизмом
    updateData(newData) {
        if (!Array.isArray(newData)) {
            console.error('SmartUpdates: newData должен быть массивом');
            return;
        }
        
        // Создаем карту новых данных
        const newDataMap = new Map();
        newData.forEach(item => {
            newDataMap.set(item.id, item);
        });
        
        // Определяем изменения
        const changes = this.detectChanges(newDataMap, newData);
        
        // Применяем изменения
        this.applyChanges(changes);
        
        // Обновляем хранилище
        this.currentData = newDataMap;
        this.currentOrder = newData.map(item => item.id);
    }
    
    // Определение изменений
    detectChanges(newDataMap, newOrder) {
        const changes = {
            added: [],
            updated: [],
            removed: [],
            reordered: false
        };
        
        // Проверяем добавленные элементы
        for (const [id, item] of newDataMap) {
            if (!this.currentData.has(id)) {
                changes.added.push(item);
            } else {
                // Проверяем изменения в существующих элементах
                const oldItem = this.currentData.get(id);
                if (this.hasItemChanged(oldItem, item)) {
                    changes.updated.push(item);
                }
            }
        }
        
        // Проверяем удаленные элементы
        for (const [id, item] of this.currentData) {
            if (!newDataMap.has(id)) {
                changes.removed.push(item);
            }
        }
        
        // Проверяем изменение порядка
        const newOrderIds = newOrder.map(item => item.id);
        changes.reordered = !this.arraysEqual(this.currentOrder, newOrderIds);
        
        return changes;
    }
    
    // Проверка изменений в элементе
    hasItemChanged(oldItem, newItem) {
        const fieldsToCheck = this.options.fieldsToCheck.length > 0 
            ? this.options.fieldsToCheck 
            : Object.keys(newItem);
        
        for (const field of fieldsToCheck) {
            if (oldItem[field] !== newItem[field]) {
                return true;
            }
        }
        
        return false;
    }
    
    // Проверка равенства массивов
    arraysEqual(a, b) {
        if (a.length !== b.length) return false;
        for (let i = 0; i < a.length; i++) {
            if (a[i] !== b[i]) return false;
        }
        return true;
    }
    
    // Применение изменений
    applyChanges(changes) {
        // Удаляем удаленные элементы
        changes.removed.forEach(item => {
            this.removeItem(item);
        });
        
        // Обновляем измененные элементы
        changes.updated.forEach(item => {
            this.updateItem(item);
        });
        
        // Добавляем новые элементы
        changes.added.forEach(item => {
            this.addItem(item);
        });
        
        // Если порядок изменился, пересортируем
        if (changes.reordered) {
            this.reorderItems();
        }
    }
    
    // Удаление элемента
    removeItem(item) {
        const element = this.container.querySelector(`[${this.options.itemIdAttribute}="${item.id}"]`);
        if (element) {
            element.remove();
        }
        
        if (this.options.onItemRemove) {
            this.options.onItemRemove(item);
        }
    }
    
    // Обновление элемента
    updateItem(item) {
        const element = this.container.querySelector(`[${this.options.itemIdAttribute}="${item.id}"]`);
        if (element) {
            // Сохраняем состояние если нужно
            if (this.options.preserveState) {
                this.preserveElementState(element);
            }
            
            // Обновляем элемент
            element.outerHTML = this.options.createItemHTML(item);
            
            // Восстанавливаем состояние если нужно
            if (this.options.preserveState) {
                this.restoreElementState(item.id);
            }
        }
        
        if (this.options.onItemUpdate) {
            this.options.onItemUpdate(item);
        }
    }
    
    // Добавление элемента
    addItem(item) {
        const newHTML = this.options.createItemHTML(item);
        this.container.insertAdjacentHTML('beforeend', newHTML);
        
        if (this.options.onItemAdd) {
            this.options.onItemAdd(item);
        }
    }
    
    // Пересортировка элементов
    reorderItems() {
        const elements = Array.from(this.container.querySelectorAll(`[${this.options.itemIdAttribute}]`));
        const sortedElements = elements.sort((a, b) => {
            const aId = parseInt(a.getAttribute(this.options.itemIdAttribute));
            const bId = parseInt(b.getAttribute(this.options.itemIdAttribute));
            const aIndex = this.currentOrder.indexOf(aId);
            const bIndex = this.currentOrder.indexOf(bId);
            return aIndex - bIndex;
        });
        
        // Перемещаем элементы в правильном порядке
        sortedElements.forEach(element => {
            this.container.appendChild(element);
        });
    }
    
    // Сохранение состояния элемента
    preserveElementState(element) {
        const id = element.getAttribute(this.options.itemIdAttribute);
        const state = {
            scrollTop: element.scrollTop,
            classes: Array.from(element.classList),
            // Добавляем другие состояния по необходимости
        };
        
        // Сохраняем состояние открытых меню
        const openMenus = element.querySelectorAll('.actions-menu:not(.hidden)');
        if (openMenus.length > 0) {
            state.openMenus = Array.from(openMenus).map(menu => ({
                selector: this.getMenuSelector(menu),
                isOpen: true
            }));
        }
        
        this.preservedStates.set(id, state);
    }
    
    // Восстановление состояния элемента
    restoreElementState(itemId) {
        const state = this.preservedStates.get(itemId.toString());
        if (!state) return;
        
        const element = this.container.querySelector(`[${this.options.itemIdAttribute}="${itemId}"]`);
        if (!element) return;
        
        // Восстанавливаем классы
        element.className = state.classes.join(' ');
        
        // Восстанавливаем состояние меню
        if (state.openMenus) {
            setTimeout(() => {
                state.openMenus.forEach(menuState => {
                    const menu = element.querySelector(menuState.selector);
                    const button = element.querySelector(menuState.selector.replace('.actions-menu', '.actions-btn'));
                    if (menu && button && menuState.isOpen) {
                        menu.classList.remove('hidden');
                        button.classList.add('bg-slate-100');
                    }
                });
            }, 50);
        }
        
        // Очищаем сохраненное состояние
        this.preservedStates.delete(itemId.toString());
    }
    
    // Получение селектора меню
    getMenuSelector(menu) {
        const ticketId = menu.getAttribute('data-ticket-id');
        return `.actions-menu[data-ticket-id="${ticketId}"]`;
    }
    
    // Очистка данных
    clear() {
        this.currentData.clear();
        this.currentOrder = [];
        this.preservedStates.clear();
    }
}

// Делаем SmartUpdates доступным глобально
window.SmartUpdates = SmartUpdates;
