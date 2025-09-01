# Исправление проблем с Telegram ботом

Если ваш бот не отвечает на команды, следуйте этой инструкции для диагностики и исправления проблем.

## 1. Проверка настроек

### Проверка токена бота

Убедитесь, что токен бота правильно указан в файле `.env`:

```
TELEGRAM_BOT_TOKEN=8471350979:AAFEwk2aHzSreT4YcivY9501LdXNnuS3BtM
```

### Проверка маршрутов

Маршруты в `routes/api.php` должны принимать любые HTTP методы (для тестирования):

```php
Route::any('/telegram/webhook', [TelegramBotController::class, 'handle']);

// Тестовый маршрут
Route::get('/telegram/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Telegram API test route is working',
    ]);
});
```

## 2. Проверка доступности маршрутов

Проверьте доступность тестового маршрута:

```bash
curl -v https://your-domain.com/api/telegram/test
```

Вы должны получить ответ:
```json
{"status":"ok","message":"Telegram API test route is working"}
```

## 3. Использование Long Polling вместо Webhook

Если webhook не работает, используйте long polling:

```bash
# Сначала удалите webhook
./vendor/bin/sail artisan telegram:delete-webhook

# Затем запустите бота в режиме long polling
./vendor/bin/sail artisan telegram:polling
```

Это запустит бота в режиме опроса сервера Telegram, минуя проблемы с webhook.

## 4. Проверка логов

Проверьте логи Laravel для поиска ошибок:

```bash
./vendor/bin/sail artisan pail
```

или

```bash
./vendor/bin/sail cat storage/logs/laravel.log
```

## 5. Проверка composer пакетов

Убедитесь, что все необходимые пакеты установлены:

```bash
./vendor/bin/sail composer require botman/botman botman/driver-telegram
```

## 6. Запуск миграций

Запустите миграцию для добавления поля telegram_id:

```bash
./vendor/bin/sail artisan migrate
```

## Диагностика через webhook API

Для проверки настроек webhook выполните:

```bash
curl -X GET https://api.telegram.org/bot{TELEGRAM_BOT_TOKEN}/getWebhookInfo
```

Если результат содержит ошибку 404, это указывает на то, что ваш webhook URL не отвечает правильно.

## Перезапуск контейнеров

Иногда помогает перезапуск контейнеров:

```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

## Дополнительные проверки

1. Убедитесь, что порт 443 открыт и доступен для входящих соединений
2. Проверьте наличие SSL сертификата (Telegram требует HTTPS)
3. Убедитесь, что URL в webhook правильный и соответствует вашему домену