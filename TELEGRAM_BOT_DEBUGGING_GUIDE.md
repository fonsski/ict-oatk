# 🔍 Руководство по отладке Telegram бота

## 🐛 **Проблема:**
Telegram бот все еще отправляет двойные сообщения при взятии заявки в работу.

## 🔧 **Исправления применены:**

### 1. **Отключен NotificationService в Docker**
- В `NotificationService.notifyNewTicket()` добавлена проверка `!env('LARAVEL_SAIL')`
- В Docker окружении уведомления о новых заявках обрабатываются только через `TelegramStandalone`

### 2. **Упрощена логика взятия заявки в работу**
- В `TelegramStandalone.handleStartTicketCommand()` отключен вызов `NotificationService`
- Оставлено только прямое сообщение пользователю

### 3. **Добавлено детальное логирование**
- Логирование всех вызовов `sendMessage()`
- Логирование процесса взятия заявки в работу
- Логирование для отслеживания источника двойных сообщений

## 🔍 **Как отладить проблему:**

### 1. **Перезапустите бота:**
```bash
./vendor/bin/sail restart telegram-bot
```

### 2. **Проверьте логи в реальном времени:**
```bash
./vendor/bin/sail logs telegram-bot -f
```

### 3. **Протестируйте взятие заявки в работу:**
```
/start_ticket_2
```

### 4. **Ищите в логах:**
```
# Логи отправки сообщений
TelegramStandalone sendMessage called

# Логи взятия заявки в работу
handleStartTicketCommand called
Sending direct message to user about ticket start
Successfully started ticket
```

## 🎯 **Что искать в логах:**

### ✅ **Нормальное поведение (один раз):**
```
[INFO] handleStartTicketCommand called {"chat_id":123,"ticket_id":2}
[INFO] Sending direct message to user about ticket start {"chat_id":123,"ticket_id":2,"user_id":1}
[INFO] TelegramStandalone sendMessage called {"chat_id":123,"text_preview":"✅ Заявка #2 успешно взята в работу и назначена на вас!","markdown":false}
[INFO] Successfully started ticket {"chat_id":123,"ticket_id":2,"user_id":1,"old_status":"open","new_status":"in_progress"}
```

### ❌ **Проблемное поведение (два раза):**
```
[INFO] TelegramStandalone sendMessage called {"chat_id":123,"text_preview":"✅ Заявка #2 успешно взята в работу и назначена на вас!","markdown":false}
[INFO] TelegramStandalone sendMessage called {"chat_id":123,"text_preview":"✅ Заявка #2 успешно взята в работу и назначена на вас!","markdown":false}
```

## 🔧 **Возможные причины двойных сообщений:**

### 1. **Два экземпляра бота**
- Проверьте, не запущены ли два контейнера с ботом
- Проверьте процессы: `docker ps | grep telegram`

### 2. **Webhook + Polling одновременно**
- Проверьте, не настроен ли webhook для того же бота
- Проверьте настройки в `.env`

### 3. **Дублирование команд**
- Проверьте, не вызывается ли команда дважды
- Проверьте логи на дублирование `handleStartTicketCommand called`

### 4. **Система уведомлений**
- Проверьте, не обрабатываются ли уведомления из базы данных
- Проверьте, не отправляются ли уведомления через WebSocket

## 🚀 **Команды для диагностики:**

### Проверка контейнеров:
```bash
# Список всех контейнеров
docker ps

# Логи только Telegram бота
./vendor/bin/sail logs telegram-bot -f

# Статус контейнеров
./vendor/bin/sail ps
```

### Проверка процессов:
```bash
# Процессы в контейнере бота
./vendor/bin/sail exec telegram-bot ps aux

# Проверка webhook
curl -X GET "https://api.telegram.org/bot<TOKEN>/getWebhookInfo"
```

### Проверка базы данных:
```bash
# Уведомления в базе данных
./vendor/bin/sail exec laravel.test php artisan tinker
>>> \App\Models\Notification::latest()->take(5)->get()
```

## 📊 **Мониторинг:**

### Ключевые логи для отслеживания:
- `TelegramStandalone sendMessage called` - каждое отправленное сообщение
- `handleStartTicketCommand called` - начало обработки команды
- `Sending direct message to user about ticket start` - отправка сообщения о взятии в работу
- `Successfully started ticket` - успешное завершение операции

### Ожидаемое поведение:
- Каждое сообщение должно отправляться только один раз
- Логи должны показывать четкую последовательность операций
- Не должно быть дублирования вызовов `sendMessage`

## 🎯 **Следующие шаги:**

1. **Протестируйте** взятие заявки в работу
2. **Проверьте логи** на дублирование
3. **Сообщите результаты** - какие логи появляются при двойных сообщениях
4. **При необходимости** добавим дополнительное логирование

**С детальным логированием мы сможем точно определить источник двойных сообщений! 🔍**
