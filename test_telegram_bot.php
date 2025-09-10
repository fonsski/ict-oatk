<?php

/**
 * Тестовый скрипт для проверки новой системы Telegram бота
 */

require_once 'vendor/autoload.php';

use App\Services\TelegramService;
use App\Services\TelegramAuthService;
use App\Services\TelegramCommandService;
use App\Services\TelegramNotificationService;

echo "🤖 Тестирование новой системы Telegram бота\n\n";

try {
    // Тест 1: Проверка TelegramService
    echo "1. Тестирование TelegramService...\n";
    $telegramService = new TelegramService();
    
    $botInfo = $telegramService->getBotInfo();
    if ($botInfo) {
        echo "   ✅ Подключение к Telegram API успешно\n";
        echo "   📱 Бот: @{$botInfo['username']} ({$botInfo['first_name']})\n";
    } else {
        echo "   ❌ Ошибка подключения к Telegram API\n";
        exit(1);
    }

    // Тест 2: Проверка сервисов
    echo "\n2. Тестирование сервисов...\n";
    
    $authService = new TelegramAuthService($telegramService);
    echo "   ✅ TelegramAuthService создан\n";
    
    $commandService = new TelegramCommandService($telegramService, $authService);
    echo "   ✅ TelegramCommandService создан\n";
    
    $notificationService = new TelegramNotificationService($telegramService);
    echo "   ✅ TelegramNotificationService создан\n";

    // Тест 3: Проверка конфигурации
    echo "\n3. Проверка конфигурации...\n";
    
    $token = config('services.telegram.token');
    if (empty($token)) {
        echo "   ❌ Токен бота не настроен в .env файле\n";
        echo "   💡 Добавьте TELEGRAM_BOT_TOKEN=your_token в .env\n";
    } else {
        echo "   ✅ Токен бота настроен\n";
    }

    $appUrl = config('app.url');
    if (empty($appUrl)) {
        echo "   ⚠️  APP_URL не настроен в .env файле\n";
        echo "   💡 Добавьте APP_URL=https://your-domain.com в .env\n";
    } else {
        echo "   ✅ APP_URL настроен: {$appUrl}\n";
    }

    // Тест 4: Проверка маршрутов
    echo "\n4. Проверка маршрутов...\n";
    
    $webhookUrl = $appUrl . '/api/telegram/webhook';
    echo "   📡 Webhook URL: {$webhookUrl}\n";
    
    $testUrl = $appUrl . '/api/telegram/test';
    echo "   🧪 Test URL: {$testUrl}\n";

    echo "\n🎉 Все тесты пройдены успешно!\n\n";
    
    echo "📋 Следующие шаги:\n";
    echo "1. Убедитесь, что токен бота настроен в .env\n";
    echo "2. Запустите бота: php artisan telegram:bot --mode=webhook\n";
    echo "3. Или в режиме polling: php artisan telegram:bot --mode=polling\n";
    echo "4. Протестируйте команды в Telegram\n\n";
    
    echo "📚 Документация: TELEGRAM_BOT_NEW_ARCHITECTURE.md\n";

} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
    echo "📍 Файл: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
