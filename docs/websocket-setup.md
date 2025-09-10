# WebSocket Real-time Updates - Настройка и использование

## 🚀 Быстрый старт

### 1. Запуск WebSocket сервера

```bash
# Запуск WebSocket сервера на порту 8080
./start-websocket.sh

# Или вручную:
php artisan websocket:serve --port=8080
```

### 2. Проверка работы

1. Откройте страницу "Все заявки" в браузере
2. Откройте Developer Tools (F12) → Console
3. Должно появиться сообщение: "WebSocket: Подключение установлено"

## 📡 Как это работает

### Режимы работы:

1. **WebSocket режим** (приоритетный):
   - Мгновенные обновления при изменении заявок
   - Экономия трафика
   - Автоматическое переподключение

2. **HTTP Polling режим** (fallback):
   - Обновление каждую секунду
   - Работает если WebSocket недоступен
   - Автоматическое переключение

### Автоматическое переключение:

- Если WebSocket недоступен → автоматически переключается на HTTP polling
- Если WebSocket восстанавливается → переключается обратно на WebSocket

## 🔧 Настройка

### Изменение порта WebSocket:

```bash
php artisan websocket:serve --port=9000
```

### Отключение WebSocket (только HTTP polling):

В файлах представлений измените:
```javascript
useWebSocket: false, // Вместо true
```

### Изменение интервала HTTP polling:

```javascript
refreshInterval: 5000, // 5 секунд вместо 1 секунды
```

## 🐛 Устранение неполадок

### WebSocket не подключается:

1. **Проверьте, что сервер запущен:**
   ```bash
   ./start-websocket.sh
   ```

2. **Проверьте порт в консоли браузера:**
   - Должно быть: `ws://yourdomain.com:8080`
   - Если localhost: `ws://localhost:8080`

3. **Проверьте файрвол:**
   ```bash
   # Ubuntu/Debian
   sudo ufw allow 8080
   
   # CentOS/RHEL
   sudo firewall-cmd --permanent --add-port=8080/tcp
   sudo firewall-cmd --reload
   ```

### Fallback на HTTP polling:

Если WebSocket не работает, система автоматически переключится на HTTP polling каждую секунду. Это видно в консоли:
```
LiveUpdates: WebSocket отключен
LiveUpdates: Используем HTTP polling
```

## 📊 Мониторинг

### Логи WebSocket сервера:

В консоли где запущен сервер видны все подключения:
```
New connection! (123)
Connection 123 sending message "..." to 2 other connections
Connection 123 has disconnected
```

### Логи в браузере:

Откройте Developer Tools → Console для просмотра:
- Статус подключения
- Ошибки
- Переключения между режимами

## 🔒 Безопасность

### Для продакшена:

1. **Используйте WSS (WebSocket Secure):**
   ```javascript
   websocketUrl: 'wss://yourdomain.com:8080'
   ```

2. **Настройте reverse proxy (Nginx):**
   ```nginx
   location /ws {
       proxy_pass http://localhost:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "upgrade";
       proxy_set_header Host $host;
   }
   ```

3. **Ограничьте доступ по IP:**
   ```bash
   # В WebSocket сервере добавьте проверку IP
   ```

## 🚀 Производительность

### Оптимизация:

- **WebSocket**: ~0.1KB на обновление
- **HTTP Polling**: ~2-5KB каждую секунду
- **Экономия трафика**: до 95% при использовании WebSocket

### Рекомендации:

- Используйте WebSocket для production
- HTTP polling как fallback
- Мониторьте количество подключений

## 📝 API

### WebSocket сообщения:

**От клиента к серверу:**
```javascript
{ type: 'get_tickets' }  // Запрос обновления заявок
```

**От сервера к клиенту:**
```javascript
{
  tickets: [...],        // Массив заявок
  stats: {...},          // Статистика
  last_updated: "..."    // Время обновления
}
```

---

**Дата создания:** {{ date('Y-m-d H:i:s') }}
**Версия:** 1.0
**Автор:** AI Assistant
