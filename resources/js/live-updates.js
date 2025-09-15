/**
 * Live Updates для заявок
 * Обеспечивает автоматическое обновление заявок без перезагрузки страницы
 * Поддерживает как HTTP polling, так и WebSocket
 */

class LiveUpdates {
    constructor(options = {}) {
        this.options = {
            refreshInterval: options.refreshInterval || 1000, // 1 секунда
            apiEndpoint: options.apiEndpoint,
            csrfToken: options.csrfToken,
            useWebSocket: options.useWebSocket || false,
            websocketUrl: options.websocketUrl || 'ws://localhost:8080',
            onError: options.onError || this.defaultErrorHandler,
            onSuccess: options.onSuccess || this.defaultSuccessHandler,
            ...options
        };
        
        this.refreshInterval = null;
        this.isRefreshing = false;
        this.retryCount = 0;
        this.maxRetries = 3;
        this.websocketClient = null;
        
        this.init();
    }
    
    init() {
        console.log('LiveUpdates: Инициализация');
        
        // Проверяем наличие необходимых элементов
        if (!this.options.apiEndpoint) {
            console.error('LiveUpdates: API endpoint не указан');
            return;
        }
        
        // Выбираем метод обновления
        if (this.options.useWebSocket && typeof WebSocketClient !== 'undefined') {
            console.log('LiveUpdates: Используем WebSocket');
            this.initWebSocket();
        } else {
            console.log('LiveUpdates: Используем HTTP polling');
            this.startAutoRefresh();
        }
        
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
            if (this.websocketClient) {
                this.websocketClient.disconnect();
            }
        });
    }
    
    initWebSocket() {
        this.websocketClient = new WebSocketClient({
            url: this.options.websocketUrl,
            onMessage: (data) => {
                console.log('LiveUpdates: Получены данные через WebSocket', data);
                
                // Обрабатываем разные типы сообщений
                if (data.type) {
                    this.handleWebSocketMessage(data);
                } else {
                    // Fallback для старых сообщений
                    this.options.onSuccess(data);
                }
                
                this.updateStatusIndicator('success');
                this.updateLastUpdated(data.timestamp || data.last_updated);
            },
            onOpen: () => {
                console.log('LiveUpdates: WebSocket подключен');
                this.updateStatusIndicator('success');
                // Запрашиваем первоначальные данные через HTTP
                this.refresh();
            },
            onClose: () => {
                console.log('LiveUpdates: WebSocket отключен');
                this.updateStatusIndicator('error');
                // Fallback к HTTP polling
                this.startAutoRefresh();
            },
            onError: (error) => {
                console.error('LiveUpdates: Ошибка WebSocket:', error);
                this.updateStatusIndicator('error');
                // Fallback к HTTP polling
                this.startAutoRefresh();
            }
        });
    }
    
    handleWebSocketMessage(data) {
        switch (data.type) {
            case 'ticket_created':
                this.handleTicketCreated(data.data);
                break;
            case 'ticket_status_changed':
                this.handleTicketStatusChanged(data.data);
                break;
            case 'ticket_assigned':
                this.handleTicketAssigned(data.data);
                break;
            case 'user_created':
                this.handleUserCreated(data.data);
                break;
            case 'user_status_changed':
                this.handleUserStatusChanged(data.data);
                break;
            case 'ticket_comment_created':
                this.handleTicketCommentCreated(data.data);
                break;
            case 'equipment_status_changed':
                this.handleEquipmentStatusChanged(data.data);
                break;
            case 'equipment_location_changed':
                this.handleEquipmentLocationChanged(data.data);
                break;
            case 'knowledge_article_created':
                this.handleKnowledgeArticleCreated(data.data);
                break;
            case 'knowledge_article_updated':
                this.handleKnowledgeArticleUpdated(data.data);
                break;
            case 'system_notification_created':
                this.handleSystemNotificationCreated(data.data);
                break;
            default:
                console.log('LiveUpdates: Неизвестный тип сообщения:', data.type);
        }
    }
    
    handleTicketCreated(ticketData) {
        console.log('LiveUpdates: Новая заявка создана:', ticketData);
        // Показываем уведомление
        this.showNotification(ticketData.message, 'success');
        // Обновляем данные
        this.refresh();
    }
    
    handleTicketStatusChanged(ticketData) {
        console.log('LiveUpdates: Статус заявки изменен:', ticketData);
        // Показываем уведомление
        this.showNotification(ticketData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleTicketAssigned(ticketData) {
        console.log('LiveUpdates: Заявка назначена:', ticketData);
        // Показываем уведомление
        this.showNotification(ticketData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleUserCreated(userData) {
        console.log('LiveUpdates: Новый пользователь создан:', userData);
        // Показываем уведомление
        this.showNotification(userData.message, 'success');
        // Обновляем данные
        this.refresh();
    }
    
    handleUserStatusChanged(userData) {
        console.log('LiveUpdates: Статус пользователя изменен:', userData);
        // Показываем уведомление
        this.showNotification(userData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleTicketCommentCreated(commentData) {
        console.log('LiveUpdates: Комментарий к заявке создан:', commentData);
        // Показываем уведомление
        this.showNotification(commentData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleEquipmentStatusChanged(equipmentData) {
        console.log('LiveUpdates: Статус оборудования изменен:', equipmentData);
        // Показываем уведомление
        this.showNotification(equipmentData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleEquipmentLocationChanged(equipmentData) {
        console.log('LiveUpdates: Местоположение оборудования изменено:', equipmentData);
        // Показываем уведомление
        this.showNotification(equipmentData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleKnowledgeArticleCreated(articleData) {
        console.log('LiveUpdates: Создана статья в базе знаний:', articleData);
        // Показываем уведомление
        this.showNotification(articleData.message, 'success');
        // Обновляем данные
        this.refresh();
    }
    
    handleKnowledgeArticleUpdated(articleData) {
        console.log('LiveUpdates: Обновлена статья в базе знаний:', articleData);
        // Показываем уведомление
        this.showNotification(articleData.message, 'info');
        // Обновляем данные
        this.refresh();
    }
    
    handleSystemNotificationCreated(notificationData) {
        console.log('LiveUpdates: Создано системное уведомление:', notificationData);
        // Показываем уведомление с соответствующим цветом
        const color = notificationData.color || 'info';
        this.showNotification(notificationData.message, color);
        // Обновляем данные
        this.refresh();
    }
    
    showNotification(message, type = 'info') {
        // Создаем уведомление
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 max-w-sm ${
            type === 'success' ? 'bg-green-500 text-white' :
            type === 'error' ? 'bg-red-500 text-white' :
            'bg-blue-500 text-white'
        }`;
        notification.textContent = message;
        
        document.body.appendChild(notification);
        
        // Автоматически удаляем через 5 секунд
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
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
        
        // Убираем мигание - индикатор всегда зеленый, кроме ошибок
        const statusClasses = {
            loading: 'w-2 h-2 bg-green-500 rounded-full',
            success: 'w-2 h-2 bg-green-500 rounded-full',
            error: 'w-2 h-2 bg-red-500 rounded-full'
        };
        
        indicator.className = statusClasses[status] || statusClasses.success;
        
        // Если статус error, запускаем таймер для возврата к зеленому через 30 секунд
        if (status === 'error') {
            setTimeout(() => {
                if (indicator) {
                    indicator.className = 'w-2 h-2 bg-green-500 rounded-full';
                }
            }, 30000); // 30 секунд
        }
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
