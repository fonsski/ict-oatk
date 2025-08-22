<?php

// Скрипт для тестирования конфигурации Laravel

// Загрузка автозагрузчика Composer
require __DIR__ . '/vendor/autoload.php';

// Загрузка окружения из файла .env
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->safeLoad();

// Загрузка базовой конфигурации Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';

// Загрузка конфигурации
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Вывод заголовка
echo "Проверка конфигурации Laravel\n";
echo "=============================\n\n";

// Вывод текущего драйвера почты
echo "Настройки почты:\n";
echo "-----------------\n";
echo "mail.default: " . config('mail.default') . "\n";
echo "mail.mailers (доступные драйверы): " . implode(', ', array_keys(config('mail.mailers'))) . "\n";

// Проверка gmail драйвера
if (isset(config('mail.mailers')['gmail'])) {
    echo "\nКонфигурация gmail драйвера:\n";
    $gmailConfig = config('mail.mailers.gmail');
    foreach ($gmailConfig as $key => $value) {
        if ($key === 'password') {
            echo "  $key: " . (empty($value) ? 'null' : '********') . "\n";
        } else {
            echo "  $key: " . (is_string($value) ? $value : json_encode($value)) . "\n";
        }
    }
} else {
    echo "\nДрайвер gmail не настроен в конфигурации!\n";
}

// Проверка настроек .env
echo "\nНастройки в .env файле:\n";
echo "---------------------\n";
echo "MAIL_MAILER: " . ($_ENV['MAIL_MAILER'] ?? 'не установлено') . "\n";
echo "MAIL_HOST: " . ($_ENV['MAIL_HOST'] ?? 'не установлено') . "\n";
echo "MAIL_USERNAME: " . ($_ENV['MAIL_USERNAME'] ?? 'не установлено') . "\n";

// Проверка настроек Google OAuth
echo "\nНастройки Google OAuth:\n";
echo "--------------------\n";
echo "GOOGLE_CLIENT_ID: " . (isset($_ENV['GOOGLE_CLIENT_ID']) ? substr($_ENV['GOOGLE_CLIENT_ID'], 0, 10) . '...' : 'не установлено') . "\n";
echo "GOOGLE_REDIRECT_URI: " . ($_ENV['GOOGLE_REDIRECT_URI'] ?? 'не установлено') . "\n";

// Дополнительная информация об окружении
echo "\nИнформация об окружении:\n";
echo "----------------------\n";
echo "APP_ENV: " . ($_ENV['APP_ENV'] ?? 'не установлено') . "\n";
echo "APP_DEBUG: " . ($_ENV['APP_DEBUG'] ?? 'не установлено') . "\n";
echo "CACHE_DRIVER: " . ($_ENV['CACHE_DRIVER'] ?? 'не установлено') . "\n";

// Вывод информации о шаблоне
if (file_exists(__DIR__ . '/resources/views/settings/gmail-oauth.blade.php')) {
    echo "\nПроверка шаблона gmail-oauth.blade.php:\n";
    echo "-----------------------------------\n";
    $template = file_get_contents(__DIR__ . '/resources/views/settings/gmail-oauth.blade.php');

    // Проверка используемых конфигураций
    $configChecks = [
        "config('mail.default')" => preg_match("/config\(\s*['\"]mail\.default['\"]\s*\)/", $template),
        "config('mail.mailer')" => preg_match("/config\(\s*['\"]mail\.mailer['\"]\s*\)/", $template),
    ];

    foreach ($configChecks as $check => $result) {
        echo "$check используется в шаблоне: " . ($result ? 'Да' : 'Нет') . "\n";
    }
}

echo "\nПроверка завершена.\n";
