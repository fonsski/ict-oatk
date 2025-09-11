# 🚀 Руководство по развертыванию Telegram бота на сервере

## 📋 **Что нужно сделать на сервере:**

### 1. **Остановить старые процессы бота:**
```bash
# Найти и остановить старые процессы
ps aux | grep telegram
kill [PID_процесса]

# Или остановить все контейнеры
docker-compose down
```

### 2. **Обновить код на сервере:**
```bash
# Получить обновления
git pull origin main

# Убедиться, что все файлы на месте
ls -la app/Services/Telegram*
ls -la app/Http/Controllers/TelegramController.php
ls -la app/Console/Commands/TelegramBot.php
```

### 3. **Проверить конфигурацию Docker Compose:**
```bash
# Проверить, что в docker-compose.yml правильная команда
grep -A 5 -B 5 "telegram:bot" docker-compose.yml
```

Должно быть:
```yaml
command: php artisan telegram:bot --mode=polling
```

### 4. **Запустить контейнеры:**
```bash
# Запустить все контейнеры
docker-compose up -d

# Или только telegram-bot
docker-compose up -d telegram-bot
```

### 5. **Проверить логи:**
```bash
# Посмотреть логи telegram-bot
docker-compose logs telegram-bot -f

# Или через docker
docker logs [container_name] -f
```

### 6. **Проверить статус:**
```bash
# Статус контейнеров
docker-compose ps

# Или через docker
docker ps
```

## 🔍 **Что должно быть в логах при успешном запуске:**

```
Starting Telegram bot in polling mode...
Testing connection to Telegram API...
✅ Connection successful!
Bot: @your_bot_name (bot_name)
Starting long polling...
Bot is listening. Press Ctrl+C to stop.
```

## 🚨 **Возможные проблемы:**

### **Проблема 1: Старая команда**
Если видите ошибку:
```
Command "telegram:standalone" is not defined.
```

**Решение:**
```bash
# Пересоздать контейнер
docker-compose down telegram-bot
docker-compose up -d telegram-bot
```

### **Проблема 2: Нет токена**
Если видите ошибку:
```
Telegram bot token is not configured
```

**Решение:**
```bash
# Проверить .env файл
grep TELEGRAM_BOT_TOKEN .env

# Если нет, добавить
echo "TELEGRAM_BOT_TOKEN=your_bot_token" >> .env
```

### **Проблема 3: Контейнер не запускается**
```bash
# Проверить логи
docker-compose logs telegram-bot

# Пересоздать контейнер
docker-compose down
docker-compose up -d --build
```

## 🧪 **Тестирование:**

### **1. Проверить webhook (если используется):**
```bash
curl -X GET "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

### **2. Проверить API endpoint:**
```bash
curl https://your-domain.com/api/telegram/test
```

### **3. Протестировать команды в Telegram:**
- `/start`
- `/help`
- `/login`
- `/tickets`
- `/all_tickets`
- `/stats`

## 📱 **Новые команды бота:**

- `/tickets` - активные заявки (до 20)
- `/all_tickets` - все заявки включая закрытые (до 30)
- `/stats` - статистика заявок
- `/active` - заявки в работе
- `/ticket_123` - детали заявки
- `/start_ticket_123` - взять в работу
- `/resolve_123` - отметить решенной

## 🔧 **Автозапуск:**

Бот теперь настроен на автоматический запуск при старте контейнера. Контейнер будет перезапускаться автоматически при сбоях благодаря `restart: unless-stopped`.

## 📞 **Если что-то не работает:**

1. Проверьте логи: `docker-compose logs telegram-bot -f`
2. Проверьте статус: `docker-compose ps`
3. Перезапустите: `docker-compose restart telegram-bot`
4. Проверьте токен в .env файле
5. Убедитесь, что все файлы обновлены

**Бот должен работать постоянно и автоматически запускаться при перезагрузке сервера! 🚀**

