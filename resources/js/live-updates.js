/**
 * Live Updates для заявок
 * Обеспечивает автоматическое обновление заявок без перезагрузки страницы
 */

class LiveUpdates {
    constructor(options = {}) {
        this.options = {
            refreshInterval: options.refreshInterval || 30000, // 30 секунд
            apiEndpoint: options.apiEndpoint,
            csrfToken: options.csrfToken,
            onError: options.onError || this.defaultErrorHandler,
            onSuccess: options.onSuccess || this.defaultSuccessHandler,
            ...options
        };
        
        this.refreshInterval = null;
        this.isRefreshing = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        
        this.init();
    }
    
    init() {
        console.log('LiveUpdates: Инициализация');
        
        // Проверяем наличие необходимых элементов
        if (!this.options.apiEndpoint) {
            console.error('LiveUpdates: API endpoint не указан');
            return;
        }
        
        // Запускаем автообновление
        this.startAutoRefresh();
        
        // Обработка видимости страницы
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                console.log('LiveUpdates: Страница стала видимой, обновляем данные');
                this.refresh();
            }
        });
        
        // Остановка при уходе со страницы
        window.addEventListener('beforeunload', () => {
            this.stopAutoRefresh();
        });
    }
    
    async refresh() {
        if (this.isRefreshing) {
            console.log('LiveUpdates: Обновление уже выполняется, пропускаем');
            return;
        }
        
        this.isRefreshing = true;
        this.updateStatusIndicator('loading');
        
        try {
            const response = await fetch(this.options.apiEndpoint, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.options.csrfToken || ''
                },
                cache: 'no-store',
                credentials: 'same-origin'
            });
            
            if (!response.ok) {
                if (response.status === 401 || response.status === 403) {
                    console.warn('LiveUpdates: Ошибка аутентификации');
                    this.handleAuthError();
                    return;
                }
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const data = await response.json();
            
            if (data.error) {
                throw new Error(data.error);
            }
            
            // Сбрасываем счетчик повторных попыток при успехе
            this.retryCount = 0;
            
            // Вызываем обработчик успеха
            this.options.onSuccess(data);
            
            this.updateStatusIndicator('success');
            this.updateLastUpdated(data.last_updated);
            
        } catch (error) {
            console.error('LiveUpdates: Ошибка при обновлении:', error);
            this.handleError(error);
        } finally {
            this.isRefreshing = false;
        }
    }
    
    handleError(error) {
        this.retryCount++;
        
        if (this.retryCount <= this.maxRetries) {
            console.log(`LiveUpdates: Повторная попытка ${this.retryCount}/${this.maxRetries}`);
            setTimeout(() => {
                this.refresh();
            }, 5000 * this.retryCount); // Экспоненциальная задержка
        } else {
            console.error('LiveUpdates: Превышено максимальное количество попыток');
            this.updateStatusIndicator('error');
        }
        
        this.options.onError(error);
    }
    
    handleAuthError() {
        console.warn('LiveUpdates: Перенаправление на страницу логина');
        setTimeout(() => {
            window.location.href = '/login';
        }, 1000);
    }
    
    updateStatusIndicator(status) {
        const indicator = document.getElementById('status-indicator');
        if (!indicator) return;
        
        const statusClasses = {
            loading: 'w-2 h-2 bg-yellow-500 rounded-full',
            success: 'w-2 h-2 bg-green-500 rounded-full',
            error: 'w-2 h-2 bg-red-500 rounded-full'
        };
        
        indicator.className = statusClasses[status] || statusClasses.error;
    }
    
    updateLastUpdated(timestamp) {
        const lastUpdated = document.getElementById('last-updated');
        if (lastUpdated && timestamp) {
            lastUpdated.textContent = `Обновлено: ${timestamp}`;
        }
    }
    
    startAutoRefresh() {
        if (this.refreshInterval) {
            this.stopAutoRefresh();
        }
        
        console.log(`LiveUpdates: Запуск автообновления каждые ${this.options.refreshInterval / 1000} секунд`);
        
        // Первоначальное обновление
        this.refresh();
        
        // Устанавливаем интервал
        this.refreshInterval = setInterval(() => {
            this.refresh();
        }, this.options.refreshInterval);
    }
    
    stopAutoRefresh() {
        if (this.refreshInterval) {
            clearInterval(this.refreshInterval);
            this.refreshInterval = null;
            console.log('LiveUpdates: Автообновление остановлено');
        }
    }
    
    defaultErrorHandler(error) {
        console.error('LiveUpdates: Ошибка по умолчанию:', error);
    }
    
    defaultSuccessHandler(data) {
        console.log('LiveUpdates: Данные обновлены успешно');
    }
}

// Экспорт для использования в других модулях
if (typeof window !== 'undefined') {
    window.LiveUpdates = LiveUpdates;
}
