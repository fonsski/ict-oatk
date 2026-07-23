# 🐳 Laravel Sail с WebSocket - Настройка и использование

## 🚀 Автоматический запуск WebSocket с Sail

WebSocket сервер теперь автоматически запускается вместе с Laravel Sail контейнерами!

## 📋 Что настроено

### Docker Compose конфигурация
- ✅ **websocket-server** сервис добавлен в `docker-compose.yml`
- ✅ Автоматический перезапуск при сбоях (`restart: unless-stopped`)
- ✅ Healthcheck для мониторинга состояния
- ✅ Логирование с ротацией файлов
- ✅ Правильная сетевая конфигурация

### Конфигурация приложения
- ✅ Автоматическое определение Docker окружения
- ✅ Правильные хосты для внутренней коммуникации
- ✅ Переменные окружения для WebSocket

## 🛠️ Команды для управления

### Использование нового скрипта (рекомендуется):

```bash
# Запустить все сервисы (Laravel + WebSocket)
./sail-with-websocket.sh start

# Проверить здоровье всех сервисов
./sail-with-websocket.sh health

# Перезапустить только WebSocket сервер
./sail-with-websocket.sh websocket

# Показать логи WebSocket сервера
./sail-with-websocket.sh logs

# Остановить все сервисы
./sail-with-websocket.sh stop

# Показать статус контейнеров
./sail-with-websocket.sh status
```

### Стандартные команды Sail:

```bash
# Запустить все сервисы
./vendor/bin/sail up -d

# Остановить все сервисы
./vendor/bin/sail down

# Перезапустить WebSocket сервер
./vendor/bin/sail restart websocket-server

# Показать логи WebSocket сервера
./vendor/bin/sail logs websocket-server -f

# Войти в контейнер WebSocket сервера
./vendor/bin/sail exec websocket-server bash
```

## 🔍 Проверка работы

### 1. Запуск сервисов:
```bash
./sail-with-websocket.sh start
```

### 2. Проверка здоровья:
```bash
./sail-with-websocket.sh health
```

### 3. Проверка WebSocket endpoints:
```bash
# Проверка работы сервера
curl http://localhost:8080/test

# Статус сервера
curl http://localhost:8080/status

# Количество подключенных клиентов
curl http://localhost:8080/clients
```

## 📊 Мониторинг

### Статус контейнеров:
```bash
docker-compose ps
```

### Логи WebSocket сервера:
```bash
./vendor/bin/sail logs websocket-server --tail=50 -f
```

### Healthcheck статус:
```bash
docker inspect sail-ict_websocket-server_1 | grep -A 10 Health
```

## 🌐 Доступные сервисы

После запуска будут доступны:

- **Laravel приложение**: http://localhost
- **WebSocket сервер**: http://localhost:8080
- **Vite dev server**: http://localhost:5173
- **MySQL**: localhost:3306

## 🔧 Переменные окружения

Добавьте в `.env` файл:

```env
# WebSocket конфигурация
WEBSOCKET_HOST=0.0.0.0
WEBSOCKET_PORT=8080
WEBSOCKET_ENABLED=true
WEBSOCKET_TIMEOUT=5
WEBSOCKET_DOCKER_HOST=websocket-server
```

## 🚨 Устранение неполадок

### WebSocket сервер не запускается:

1. **Проверьте логи:**
   ```bash
   ./vendor/bin/sail logs websocket-server
   ```

2. **Проверьте порт 8080:**
   ```bash
   netstat -tulpn | grep 8080
   ```

3. **Перезапустите WebSocket сервер:**
   ```bash
   ./sail-with-websocket.sh websocket
   ```

### WebSocket не отвечает:

1. **Проверьте healthcheck:**
   ```bash
   ./sail-with-websocket.sh health
   ```

2. **Проверьте сеть Docker:**
   ```bash
   docker network ls
   docker network inspect sail-ict_sail
   ```

### Laravel не может подключиться к WebSocket:

1. **Проверьте переменные окружения:**
   ```bash
   ./vendor/bin/sail exec laravel.test env | grep WEBSOCKET
   ```

2. **Проверьте внутреннюю сеть:**
   ```bash
   ./vendor/bin/sail exec laravel.test curl http://websocket-server:8080/test
   ```

## 📈 Производительность

### Рекомендации:

- WebSocket сервер автоматически перезапускается при сбоях
- Логи ротируются (максимум 10MB, 3 файла)
- Healthcheck проверяет состояние каждые 30 секунд
- Контейнер использует минимальные ресурсы

### Мониторинг ресурсов:

```bash
# Использование ресурсов контейнерами
docker stats

# Детальная информация о WebSocket контейнере
docker inspect sail-ict_websocket-server_1
```

## 🎯 Результат

Теперь при запуске `./vendor/bin/sail up -d` автоматически запускаются:

- ✅ **Laravel приложение** (порт 80)
- ✅ **WebSocket сервер** (порт 8080) 
- ✅ **MySQL база данных** (порт 3306)

**WebSocket сервер работает в фоне и автоматически перезапускается! 🚀**
