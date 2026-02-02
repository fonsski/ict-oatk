

class NotificationSystem {
    constructor() {
        this.container = null;
        this.notifications = new Map();
        this.init();
    }
    
    init() {
        this.createContainer();
        this.setupEventListeners();
    }
    
    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'notification-container';
        this.container.className = 'fixed top-4 right-4 z-50 space-y-2 max-w-sm w-full';
        this.container.setAttribute('aria-live', 'polite');
        this.container.setAttribute('aria-label', 'Уведомления');
        document.body.appendChild(this.container);
    }
    
    setupEventListeners() {
        // Автоматическое удаление уведомлений при клике вне их
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.notification')) {
                this.clearAll();
            }
        });
        
        // Обработка клавиши Escape для закрытия уведомлений
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.clearAll();
            }
        });
    }
    
    show(message, type = 'info', options = {}) {
        const id = this.generateId();
        const notification = this.createNotification(id, message, type, options);
        
        this.container.appendChild(notification);
        this.notifications.set(id, notification);
        
        // Анимация появления
        requestAnimationFrame(() => {
            notification.classList.add('animate-slide-up');
        });
        
        // Автоматическое удаление
        if (options.duration !== 0) {
            const duration = options.duration || this.getDefaultDuration(type);
            setTimeout(() => {
                this.hide(id);
            }, duration);
        }
        
        return id;
    }
    
    createNotification(id, message, type, options) {
        const notification = document.createElement('div');
        notification.id = `notification-${id}`;
        notification.className = `notification notification-${type} animate-fade-in`;
        notification.setAttribute('role', 'alert');
        notification.setAttribute('aria-live', 'assertive');
        
        const icon = this.getIcon(type);
        const title = options.title || this.getDefaultTitle(type);
        
        notification.innerHTML = `
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    ${icon}
                </div>
                <div class="ml-3 flex-1">
                    <h4 class="text-sm font-medium text-gray-900">${title}</h4>
                    <p class="text-sm text-gray-600 mt-1">${message}</p>
                </div>
                <div class="ml-4 flex-shrink-0">
                    <button class="notification-close inline-flex text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 rounded-md p-1" aria-label="Закрыть уведомление">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                    </button>
                </div>
            </div>
        `;
        
        // Обработчик закрытия
        const closeButton = notification.querySelector('.notification-close');
        closeButton.addEventListener('click', () => {
            this.hide(id);
        });
        
        return notification;
    }
    
    getIcon(type) {
        const icons = {
            success: '<svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>',
            error: '<svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>',
            warning: '<svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>',
            info: '<svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg>'
        };
        
        return icons[type] || icons.info;
    }
    
    getDefaultTitle(type) {
        const titles = {
            success: 'Успешно',
            error: 'Ошибка',
            warning: 'Предупреждение',
            info: 'Информация'
        };
        
        return titles[type] || titles.info;
    }
    
    getDefaultDuration(type) {
        const durations = {
            success: 5000,
            error: 8000,
            warning: 6000,
            info: 4000
        };
        
        return durations[type] || durations.info;
    }
    
    hide(id) {
        const notification = this.notifications.get(id);
        if (notification) {
            notification.classList.add('animate-fade-out');
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.parentNode.removeChild(notification);
                }
                this.notifications.delete(id);
            }, 300);
        }
    }
    
    clearAll() {
        this.notifications.forEach((notification, id) => {
            this.hide(id);
        });
    }
    
    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }
    
    // Методы для удобства
    success(message, options = {}) {
        return this.show(message, 'success', options);
    }
    
    error(message, options = {}) {
        return this.show(message, 'error', options);
    }
    
    warning(message, options = {}) {
        return this.show(message, 'warning', options);
    }
    
    info(message, options = {}) {
        return this.show(message, 'info', options);
    }
}

// Создаем глобальный экземпляр
const notificationSystem = new NotificationSystem();

// Экспортируем для использования в других модулях
if (typeof window !== 'undefined') {
    window.NotificationSystem = NotificationSystem;
    window.notificationSystem = notificationSystem;
    
    // Глобальные функции для удобства
    window.showNotification = (message, type = 'info', options = {}) => {
        return notificationSystem.show(message, type, options);
    };
    
    window.showSuccess = (message, options = {}) => {
        return notificationSystem.success(message, options);
    };
    
    window.showError = (message, options = {}) => {
        return notificationSystem.error(message, options);
    };
    
    window.showWarning = (message, options = {}) => {
        return notificationSystem.warning(message, options);
    };
    
    window.showInfo = (message, options = {}) => {
        return notificationSystem.info(message, options);
    };
}

