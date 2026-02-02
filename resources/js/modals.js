

class ModalSystem {
    constructor() {
        this.activeModals = new Map();
        this.init();
    }
    
    init() {
        this.setupEventListeners();
    }
    
    setupEventListeners() {
        // Обработка клавиши Escape для закрытия модальных окон
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeAll();
            }
        });
        
        // Обработка клика по overlay для закрытия модальных окон
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-overlay')) {
                this.closeAll();
            }
        });
    }
    
    show(content, options = {}) {
        const id = this.generateId();
        const modal = this.createModal(id, content, options);
        
        document.body.appendChild(modal);
        this.activeModals.set(id, modal);
        
        // Анимация появления
        requestAnimationFrame(() => {
            modal.classList.add('animate-fade-in');
        });
        
        // Блокируем скролл страницы
        document.body.style.overflow = 'hidden';
        
        return id;
    }
    
    createModal(id, content, options) {
        const modal = document.createElement('div');
        modal.id = `modal-${id}`;
        modal.className = 'modal-overlay';
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        modal.setAttribute('aria-labelledby', `modal-title-${id}`);
        
        const size = options.size || 'md';
        const sizeClasses = {
            sm: 'max-w-sm',
            md: 'max-w-md',
            lg: 'max-w-lg',
            xl: 'max-w-xl',
            '2xl': 'max-w-2xl',
            full: 'max-w-full mx-4'
        };
        
        modal.innerHTML = `
            <div class="modal ${sizeClasses[size]}">
                <div class="modal-header">
                    <h3 id="modal-title-${id}" class="text-lg font-semibold text-gray-900">
                        ${options.title || 'Модальное окно'}
                    </h3>
                    <button class="modal-close" aria-label="Закрыть модальное окно">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    ${content}
                </div>
                ${options.footer ? `
                    <div class="modal-footer">
                        ${options.footer}
                    </div>
                ` : ''}
            </div>
        `;
        
        // Обработчик закрытия
        const closeButton = modal.querySelector('.modal-close');
        closeButton.addEventListener('click', () => {
            this.close(id);
        });
        
        return modal;
    }
    
    close(id) {
        const modal = this.activeModals.get(id);
        if (modal) {
            modal.classList.add('animate-fade-out');
            setTimeout(() => {
                if (modal.parentNode) {
                    modal.parentNode.removeChild(modal);
                }
                this.activeModals.delete(id);
                
                // Разблокируем скролл страницы, если нет других модальных окон
                if (this.activeModals.size === 0) {
                    document.body.style.overflow = '';
                }
            }, 300);
        }
    }
    
    closeAll() {
        this.activeModals.forEach((modal, id) => {
            this.close(id);
        });
    }
    
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    // Методы для создания различных типов модальных окон
    confirm(message, options = {}) {
        const content = `
            <p class="text-gray-600 mb-4">${message}</p>
        `;
        
        const footer = `
            <button class="btn-secondary modal-cancel" data-action="cancel">Отмена</button>
            <button class="btn-primary modal-confirm" data-action="confirm">Подтвердить</button>
        `;
        
        const modalId = this.show(content, {
            title: options.title || 'Подтверждение',
            footer: footer,
            ...options
        });
        
        return new Promise((resolve) => {
            const modal = this.activeModals.get(modalId);
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target.classList.contains('modal-cancel')) {
                        this.close(modalId);
                        resolve(false);
                    } else if (e.target.classList.contains('modal-confirm')) {
                        this.close(modalId);
                        resolve(true);
                    }
                });
            }
        });
    }
    
    alert(message, options = {}) {
        const content = `
            <p class="text-gray-600 mb-4">${message}</p>
        `;
        
        const footer = `
            <button class="btn-primary modal-ok" data-action="ok">ОК</button>
        `;
        
        const modalId = this.show(content, {
            title: options.title || 'Уведомление',
            footer: footer,
            ...options
        });
        
        return new Promise((resolve) => {
            const modal = this.activeModals.get(modalId);
            if (modal) {
                modal.addEventListener('click', (e) => {
                    if (e.target.classList.contains('modal-ok')) {
                        this.close(modalId);
                        resolve();
                    }
                });
            }
        });
    }
    
    prompt(message, defaultValue = '', options = {}) {
        const content = `
            <p class="text-gray-600 mb-4">${message}</p>
            <input type="text" class="form-input w-full" value="${defaultValue}" placeholder="${options.placeholder || ''}">
        `;
        
        const footer = `
            <button class="btn-secondary modal-cancel" data-action="cancel">Отмена</button>
            <button class="btn-primary modal-confirm" data-action="confirm">Подтвердить</button>
        `;
        
        const modalId = this.show(content, {
            title: options.title || 'Ввод данных',
            footer: footer,
            ...options
        });
        
        return new Promise((resolve) => {
            const modal = this.activeModals.get(modalId);
            if (modal) {
                const input = modal.querySelector('input');
                input.focus();
                input.select();
                
                modal.addEventListener('click', (e) => {
                    if (e.target.classList.contains('modal-cancel')) {
                        this.close(modalId);
                        resolve(null);
                    } else if (e.target.classList.contains('modal-confirm')) {
                        this.close(modalId);
                        resolve(input.value);
                    }
                });
                
                input.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter') {
                        this.close(modalId);
                        resolve(input.value);
                    }
                });
            }
        });
    }
}

// Создаем глобальный экземпляр
const modalSystem = new ModalSystem();

// Экспортируем для использования в других модулях
if (typeof window !== 'undefined') {
    window.ModalSystem = ModalSystem;
    window.modalSystem = modalSystem;
    
    // Глобальные функции для удобства
    window.showModal = (content, options = {}) => {
        return modalSystem.show(content, options);
    };
    
    window.showConfirm = (message, options = {}) => {
        return modalSystem.confirm(message, options);
    };
    
    window.showAlert = (message, options = {}) => {
        return modalSystem.alert(message, options);
    };
    
    window.showPrompt = (message, defaultValue = '', options = {}) => {
        return modalSystem.prompt(message, defaultValue, options);
    };
}

