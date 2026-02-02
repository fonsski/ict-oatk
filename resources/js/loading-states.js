/**
 * Управление состояниями загрузки
 * Обеспечивает показ индикаторов загрузки и блокировку интерфейса
 */

class LoadingManager {
    constructor() {
        this.activeLoaders = new Set();
        this.loadingOverlay = null;
        this.init();
    }
    
    init() {
        this.createLoadingOverlay();
        this.setupGlobalHandlers();
    }
    
    createLoadingOverlay() {
        this.loadingOverlay = document.createElement('div');
        this.loadingOverlay.className = 'loading-overlay hidden';
        this.loadingOverlay.innerHTML = `
            <div class="flex flex-col items-center justify-center space-y-4">
                <div class="loading-spinner w-8 h-8"></div>
                <p class="text-sm text-gray-600">Загрузка...</p>
            </div>
        `;
        document.body.appendChild(this.loadingOverlay);
    }
    
    setupGlobalHandlers() {
        // Обработка всех форм
        document.addEventListener('submit', (e) => {
            if (e.target.matches('form[data-loading]')) {
                this.showFormLoading(e.target);
            }
        });
        
        // Обработка всех ссылок с data-loading
        document.addEventListener('click', (e) => {
            if (e.target.matches('a[data-loading]')) {
                this.showLinkLoading(e.target);
            }
        });
        
        // Обработка всех кнопок с data-loading
        document.addEventListener('click', (e) => {
            if (e.target.matches('button[data-loading]')) {
                this.showButtonLoading(e.target);
            }
        });
    }
    
    show(id = 'default', message = 'Загрузка...') {
        this.activeLoaders.add(id);
        this.updateOverlay();
    }
    
    hide(id = 'default') {
        this.activeLoaders.delete(id);
        this.updateOverlay();
    }
    
    updateOverlay() {
        if (this.activeLoaders.size > 0) {
            this.loadingOverlay.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        } else {
            this.loadingOverlay.classList.add('hidden');
            document.body.style.overflow = '';
        }
    }
    
    showFormLoading(form) {
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            this.showButtonLoading(submitButton);
        }
    }
    
    showLinkLoading(link) {
        const originalText = link.textContent;
        const originalHref = link.href;
        
        link.textContent = 'Загрузка...';
        link.style.pointerEvents = 'none';
        link.style.opacity = '0.6';
        
        // Восстанавливаем через 10 секунд на случай ошибки
        setTimeout(() => {
            link.textContent = originalText;
            link.style.pointerEvents = '';
            link.style.opacity = '';
        }, 10000);
    }
    
    showButtonLoading(button) {
        const originalText = button.innerHTML;
        const originalDisabled = button.disabled;
        
        button.disabled = true;
        button.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Загрузка...
        `;
        
        // Восстанавливаем через 10 секунд на случай ошибки
        setTimeout(() => {
            button.innerHTML = originalText;
            button.disabled = originalDisabled;
        }, 10000);
    }
    
    showTableLoading(tableContainer) {
        const tbody = tableContainer.querySelector('tbody');
        if (tbody) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="100%" class="px-6 py-12 text-center">
                        <div class="flex flex-col items-center space-y-4">
                            <div class="loading-spinner w-8 h-8"></div>
                            <p class="text-sm text-gray-600">Загрузка данных...</p>
                        </div>
                    </td>
                </tr>
            `;
        }
    }
    
    showCardLoading(card) {
        const cardBody = card.querySelector('.card-body') || card;
        cardBody.innerHTML = `
            <div class="flex flex-col items-center justify-center py-12 space-y-4">
                <div class="loading-spinner w-8 h-8"></div>
                <p class="text-sm text-gray-600">Загрузка...</p>
            </div>
        `;
    }
    
    // Методы для работы с конкретными элементами
    showElementLoading(element, message = 'Загрузка...') {
        const originalContent = element.innerHTML;
        element.innerHTML = `
            <div class="flex items-center justify-center space-x-2 text-gray-600">
                <div class="loading-spinner w-4 h-4"></div>
                <span class="text-sm">${message}</span>
            </div>
        `;
        
        // Сохраняем оригинальный контент для восстановления
        element.dataset.originalContent = originalContent;
    }
    
    hideElementLoading(element) {
        if (element.dataset.originalContent) {
            element.innerHTML = element.dataset.originalContent;
            delete element.dataset.originalContent;
        }
    }
    
    // Метод для показа загрузки при AJAX запросах
    showAjaxLoading(url, options = {}) {
        const defaultOptions = {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
            },
            ...options
        };
        
        this.show('ajax');
        
        return fetch(url, defaultOptions)
            .finally(() => {
                this.hide('ajax');
            });
    }
}

// Создаем глобальный экземпляр
const loadingManager = new LoadingManager();

// Экспортируем для использования в других модулях
if (typeof window !== 'undefined') {
    window.LoadingManager = LoadingManager;
    window.loadingManager = loadingManager;
}

// Автоматическая инициализация при загрузке DOM
document.addEventListener('DOMContentLoaded', function() {
    // Добавляем обработчики для всех форм с data-loading
    document.querySelectorAll('form[data-loading]').forEach(form => {
        form.addEventListener('submit', function() {
            loadingManager.showFormLoading(this);
        });
    });
    
    // Добавляем обработчики для всех кнопок с data-loading
    document.querySelectorAll('button[data-loading]').forEach(button => {
        button.addEventListener('click', function() {
            loadingManager.showButtonLoading(this);
        });
    });
    
    // Добавляем обработчики для всех ссылок с data-loading
    document.querySelectorAll('a[data-loading]').forEach(link => {
        link.addEventListener('click', function() {
            loadingManager.showLinkLoading(this);
        });
    });
});

