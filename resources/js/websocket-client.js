/**
 * WebSocket Client для real-time обновлений заявок
 * Использует HTTP polling вместо WebSocket для совместимости
 */
class WebSocketClient {
    constructor(options = {}) {
        this.options = {
            url: options.url || 'ws://localhost:8080',
            reconnectInterval: options.reconnectInterval || 5000,
            maxReconnectAttempts: options.maxReconnectAttempts || 10,
            onMessage: options.onMessage || this.defaultMessageHandler,
            onOpen: options.onOpen || this.defaultOpenHandler,
            onClose: options.onClose || this.defaultCloseHandler,
            onError: options.onError || this.defaultErrorHandler,
            ...options
        };
        
        this.isConnected = false;
        this.reconnectAttempts = 0;
        this.isConnecting = false;
        this.shouldReconnect = true;
        this.pollingInterval = null;
        
        this.connect();
    }
    
    connect() {
        if (this.isConnecting || this.isConnected) {
            return;
        }
        
        this.isConnecting = true;
        console.log('WebSocket: Подключение к', this.options.url);
        
        // Проверяем доступность сервера
        this.testConnection();
    }
    
    async testConnection() {
        try {
            const httpUrl = this.options.url.replace('ws://', 'http://').replace('wss://', 'https://');
            const response = await fetch(`${httpUrl}/test`);
            
            if (response.ok) {
                console.log('WebSocket: Подключение установлено');
                this.isConnecting = false;
                this.isConnected = true;
                this.reconnectAttempts = 0;
                this.options.onOpen({ type: 'connected' });
                this.startPolling();
            } else {
                throw new Error(`HTTP ${response.status}`);
            }
        } catch (error) {
            console.error('WebSocket: Ошибка подключения:', error);
            this.isConnecting = false;
            this.options.onError(error);
            this.scheduleReconnect();
        }
    }
    
    startPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }
        
        // Polling каждые 2 секунды для проверки новых сообщений
        this.pollingInterval = setInterval(() => {
            this.checkForMessages();
        }, 2000);
    }
    
    async checkForMessages() {
        try {
            const httpUrl = this.options.url.replace('ws://', 'http://').replace('wss://', 'https://');
            const response = await fetch(`${httpUrl}/messages`);
            
            if (response.ok) {
                const data = await response.json();
                if (data.messages && data.messages.length > 0) {
                    data.messages.forEach(message => {
                        this.options.onMessage(message);
                    });
                }
            }
        } catch (error) {
            console.error('WebSocket: Ошибка получения сообщений:', error);
            this.handleConnectionError();
        }
    }
    
    handleConnectionError() {
        this.isConnected = false;
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
        this.options.onClose({ type: 'disconnected' });
        
        if (this.shouldReconnect && this.reconnectAttempts < this.options.maxReconnectAttempts) {
            this.scheduleReconnect();
        }
    }
    
    scheduleReconnect() {
        this.reconnectAttempts++;
        const delay = this.options.reconnectInterval * Math.pow(1.5, this.reconnectAttempts - 1);
        
        console.log(`WebSocket: Попытка переподключения ${this.reconnectAttempts}/${this.options.maxReconnectAttempts} через ${delay}мс`);
        
        setTimeout(() => {
            if (this.shouldReconnect) {
                this.connect();
            }
        }, delay);
    }
    
    send(message) {
        if (this.isConnected) {
            // Для HTTP polling отправка сообщений не нужна
            console.log('WebSocket: Сообщение отправлено (polling mode):', message);
            return true;
        }
        console.warn('WebSocket: Соединение не установлено, сообщение не отправлено');
        return false;
    }
    
    disconnect() {
        this.shouldReconnect = false;
        this.isConnected = false;
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
        }
    }
    
    getStatus() {
        if (this.isConnecting) return 'connecting';
        if (this.isConnected) return 'connected';
        return 'disconnected';
    }
    
    defaultMessageHandler(data) {
        console.log('WebSocket: Получено сообщение:', data);
    }
    
    defaultOpenHandler(event) {
        console.log('WebSocket: Соединение открыто');
    }
    
    defaultCloseHandler(event) {
        console.log('WebSocket: Соединение закрыто');
    }
    
    defaultErrorHandler(error) {
        console.error('WebSocket: Ошибка:', error);
    }
}

// Экспорт для использования в других модулях
if (typeof window !== 'undefined') {
    window.WebSocketClient = WebSocketClient;
}
