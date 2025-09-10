/**
 * WebSocket Client для real-time обновлений заявок
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
        
        this.ws = null;
        this.reconnectAttempts = 0;
        this.isConnecting = false;
        this.shouldReconnect = true;
        
        this.connect();
    }
    
    connect() {
        if (this.isConnecting || (this.ws && this.ws.readyState === WebSocket.OPEN)) {
            return;
        }
        
        this.isConnecting = true;
        console.log('WebSocket: Подключение к', this.options.url);
        
        try {
            this.ws = new WebSocket(this.options.url);
            
            this.ws.onopen = (event) => {
                console.log('WebSocket: Подключение установлено');
                this.isConnecting = false;
                this.reconnectAttempts = 0;
                this.options.onOpen(event);
            };
            
            this.ws.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.options.onMessage(data);
                } catch (error) {
                    console.error('WebSocket: Ошибка парсинга сообщения:', error);
                }
            };
            
            this.ws.onclose = (event) => {
                console.log('WebSocket: Соединение закрыто', event.code, event.reason);
                this.isConnecting = false;
                this.options.onClose(event);
                
                if (this.shouldReconnect && this.reconnectAttempts < this.options.maxReconnectAttempts) {
                    this.scheduleReconnect();
                }
            };
            
            this.ws.onerror = (error) => {
                console.error('WebSocket: Ошибка соединения:', error);
                this.isConnecting = false;
                this.options.onError(error);
            };
            
        } catch (error) {
            console.error('WebSocket: Ошибка создания соединения:', error);
            this.isConnecting = false;
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
        if (this.ws && this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(typeof message === 'string' ? message : JSON.stringify(message));
            return true;
        }
        console.warn('WebSocket: Соединение не установлено, сообщение не отправлено');
        return false;
    }
    
    disconnect() {
        this.shouldReconnect = false;
        if (this.ws) {
            this.ws.close();
        }
    }
    
    getStatus() {
        if (!this.ws) return 'disconnected';
        
        switch (this.ws.readyState) {
            case WebSocket.CONNECTING: return 'connecting';
            case WebSocket.OPEN: return 'connected';
            case WebSocket.CLOSING: return 'closing';
            case WebSocket.CLOSED: return 'disconnected';
            default: return 'unknown';
        }
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
