/**
 * Улучшения доступности
 * Обеспечивает лучшую доступность для пользователей с ограниченными возможностями
 */

class AccessibilityManager {
    constructor() {
        this.init();
    }
    
    init() {
        this.setupKeyboardNavigation();
        this.setupFocusManagement();
        this.setupScreenReaderSupport();
        this.setupHighContrastMode();
        this.setupReducedMotion();
    }
    
    setupKeyboardNavigation() {
        // Обработка навигации с клавиатуры
        document.addEventListener('keydown', (e) => {
            // Tab навигация
            if (e.key === 'Tab') {
                this.handleTabNavigation(e);
            }
            
            // Enter и Space для активации элементов
            if (e.key === 'Enter' || e.key === ' ') {
                this.handleActivation(e);
            }
            
            // Arrow keys для навигации в списках
            if (['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                this.handleArrowNavigation(e);
            }
        });
    }
    
    setupFocusManagement() {
        // Управление фокусом при открытии модальных окон
        document.addEventListener('modal:open', (e) => {
            this.trapFocus(e.target);
        });
        
        // Возврат фокуса при закрытии модальных окон
        document.addEventListener('modal:close', (e) => {
            this.restoreFocus(e.detail.previousFocus);
        });
    }
    
    setupScreenReaderSupport() {
        // Добавление ARIA атрибутов для динамического контента
        this.observeDynamicContent();
        
        // Уведомления для скрин-ридеров
        this.setupScreenReaderNotifications();
    }
    
    setupHighContrastMode() {
        // Проверка предпочтений пользователя
        if (window.matchMedia('(prefers-contrast: high)').matches) {
            document.body.classList.add('high-contrast');
        }
        
        // Обработка изменений предпочтений
        window.matchMedia('(prefers-contrast: high)').addEventListener('change', (e) => {
            if (e.matches) {
                document.body.classList.add('high-contrast');
            } else {
                document.body.classList.remove('high-contrast');
            }
        });
    }
    
    setupReducedMotion() {
        // Проверка предпочтений пользователя
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            document.body.classList.add('reduced-motion');
        }
        
        // Обработка изменений предпочтений
        window.matchMedia('(prefers-reduced-motion: reduce)').addEventListener('change', (e) => {
            if (e.matches) {
                document.body.classList.add('reduced-motion');
            } else {
                document.body.classList.remove('reduced-motion');
            }
        });
    }
    
    handleTabNavigation(e) {
        const focusableElements = this.getFocusableElements();
        const currentIndex = focusableElements.indexOf(document.activeElement);
        
        if (e.shiftKey) {
            // Shift + Tab - навигация назад
            if (currentIndex <= 0) {
                e.preventDefault();
                focusableElements[focusableElements.length - 1].focus();
            }
        } else {
            // Tab - навигация вперед
            if (currentIndex >= focusableElements.length - 1) {
                e.preventDefault();
                focusableElements[0].focus();
            }
        }
    }
    
    handleActivation(e) {
        const target = e.target;
        
        // Активация кнопок и ссылок
        if (target.matches('button, a, [role="button"]')) {
            if (target.disabled || target.getAttribute('aria-disabled') === 'true') {
                e.preventDefault();
                return;
            }
            
            // Программный клик для элементов, которые не являются кнопками
            if (target.getAttribute('role') === 'button') {
                e.preventDefault();
                target.click();
            }
        }
    }
    
    handleArrowNavigation(e) {
        const target = e.target;
        const container = target.closest('[role="menu"], [role="listbox"], [role="grid"]');
        
        if (!container) return;
        
        const items = container.querySelectorAll('[role="menuitem"], [role="option"], [role="gridcell"]');
        const currentIndex = Array.from(items).indexOf(target);
        
        let nextIndex = currentIndex;
        
        switch (e.key) {
            case 'ArrowUp':
                nextIndex = Math.max(0, currentIndex - 1);
                break;
            case 'ArrowDown':
                nextIndex = Math.min(items.length - 1, currentIndex + 1);
                break;
            case 'ArrowLeft':
                nextIndex = Math.max(0, currentIndex - 1);
                break;
            case 'ArrowRight':
                nextIndex = Math.min(items.length - 1, currentIndex + 1);
                break;
        }
        
        if (nextIndex !== currentIndex) {
            e.preventDefault();
            items[nextIndex].focus();
        }
    }
    
    getFocusableElements() {
        const focusableSelectors = [
            'button:not([disabled])',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            'a[href]',
            '[tabindex]:not([tabindex="-1"])',
            '[role="button"]:not([aria-disabled="true"])',
            '[role="menuitem"]',
            '[role="option"]',
            '[role="gridcell"]'
        ];
        
        return Array.from(document.querySelectorAll(focusableSelectors.join(', ')));
    }
    
    trapFocus(container) {
        const focusableElements = this.getFocusableElements().filter(el => 
            container.contains(el)
        );
        
        if (focusableElements.length === 0) return;
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        const handleKeyDown = (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        e.preventDefault();
                        lastElement.focus();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        e.preventDefault();
                        firstElement.focus();
                    }
                }
            }
        };
        
        container.addEventListener('keydown', handleKeyDown);
        firstElement.focus();
        
        // Сохраняем обработчик для последующего удаления
        container._focusTrapHandler = handleKeyDown;
    }
    
    restoreFocus(previousElement) {
        if (previousElement && previousElement.focus) {
            previousElement.focus();
        }
    }
    
    observeDynamicContent() {
        const observer = new MutationObserver((mutations) => {
            mutations.forEach((mutation) => {
                mutation.addedNodes.forEach((node) => {
                    if (node.nodeType === Node.ELEMENT_NODE) {
                        this.enhanceAccessibility(node);
                    }
                });
            });
        });
        
        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    }
    
    enhanceAccessibility(element) {
        // Добавление ARIA атрибутов для динамического контента
        if (element.matches('.notification')) {
            element.setAttribute('role', 'alert');
            element.setAttribute('aria-live', 'assertive');
        }
        
        if (element.matches('.modal')) {
            element.setAttribute('role', 'dialog');
            element.setAttribute('aria-modal', 'true');
        }
        
        if (element.matches('.dropdown-menu')) {
            element.setAttribute('role', 'menu');
        }
        
        if (element.matches('.table')) {
            element.setAttribute('role', 'table');
        }
        
        if (element.matches('.form-input')) {
            const label = element.closest('.form-group')?.querySelector('label');
            if (label) {
                const labelId = label.id || this.generateId();
                label.id = labelId;
                element.setAttribute('aria-labelledby', labelId);
            }
        }
    }
    
    setupScreenReaderNotifications() {
        // Создание скрытого элемента для уведомлений скрин-ридера
        const announcer = document.createElement('div');
        announcer.id = 'screen-reader-announcer';
        announcer.className = 'sr-only';
        announcer.setAttribute('aria-live', 'polite');
        announcer.setAttribute('aria-atomic', 'true');
        document.body.appendChild(announcer);
        
        // Функция для объявления сообщений
        window.announceToScreenReader = (message) => {
            announcer.textContent = message;
            setTimeout(() => {
                announcer.textContent = '';
            }, 1000);
        };
    }
    
    generateId() {
        return 'accessibility-' + Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    // Методы для улучшения доступности форм
    enhanceFormAccessibility(form) {
        const inputs = form.querySelectorAll('input, select, textarea');
        
        inputs.forEach(input => {
            // Добавление ARIA атрибутов для полей с ошибками
            if (input.classList.contains('error') || input.getAttribute('aria-invalid') === 'true') {
                input.setAttribute('aria-invalid', 'true');
                input.setAttribute('aria-describedby', input.id + '-error');
            }
            
            // Добавление описаний для полей
            const helpText = input.closest('.form-group')?.querySelector('.form-help');
            if (helpText) {
                const helpId = helpText.id || input.id + '-help';
                helpText.id = helpId;
                input.setAttribute('aria-describedby', helpId);
            }
        });
    }
    
    // Методы для улучшения доступности таблиц
    enhanceTableAccessibility(table) {
        // Добавление заголовков для таблиц
        const headers = table.querySelectorAll('th');
        const rows = table.querySelectorAll('tbody tr');
        
        headers.forEach((header, index) => {
            const headerId = header.id || `header-${index}`;
            header.id = headerId;
            
            rows.forEach(row => {
                const cell = row.children[index];
                if (cell) {
                    cell.setAttribute('headers', headerId);
                }
            });
        });
    }
}

// Создаем глобальный экземпляр
const accessibilityManager = new AccessibilityManager();

// Экспортируем для использования в других модулях
if (typeof window !== 'undefined') {
    window.AccessibilityManager = AccessibilityManager;
    window.accessibilityManager = accessibilityManager;
}

