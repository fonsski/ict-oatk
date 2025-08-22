<?php

// Скрипт для проверки настроек окружения Laravel
echo "Проверка настроек окружения Laravel\n";
echo "====================================\n\n";

// Проверка файла .env
$envFile = __DIR__ . "/.env";
if (!file_exists($envFile)) {
    echo "ОШИБКА: Файл .env не найден!\n";
    exit(1);
}

// Чтение файла .env
$envContent = file_get_contents($envFile);
$lines = explode("\n", $envContent);

// Поиск настроек почты
echo "Настройки почты:\n";
echo "----------------\n";

$mailSettings = [
    "MAIL_MAILER" => null,
    "MAIL_HOST" => null,
    "MAIL_PORT" => null,
    "MAIL_USERNAME" => null,
    "MAIL_PASSWORD" => null,
    "MAIL_ENCRYPTION" => null,
    "MAIL_FROM_ADDRESS" => null,
    "MAIL_FROM_NAME" => null,
];

// Извлечение настроек из .env
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, "#") === 0) {
        continue;
    }

    if (strpos($line, "=") !== false) {
        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (array_key_exists($key, $mailSettings)) {
            $mailSettings[$key] = $value;
        }
    }
}

// Вывод настроек почты
foreach ($mailSettings as $key => $value) {
    if ($key === "MAIL_PASSWORD" && !empty($value)) {
        echo "$key: ***********\n";
    } else {
        echo "$key: " . ($value !== null ? $value : "не установлено") . "\n";
    }
}

// Поиск настроек базы данных
echo "\nНастройки базы данных:\n";
echo "---------------------\n";

$dbSettings = [
    "DB_CONNECTION" => null,
    "DB_HOST" => null,
    "DB_PORT" => null,
    "DB_DATABASE" => null,
    "DB_USERNAME" => null,
    "DB_PASSWORD" => null,
];

// Извлечение настроек БД из .env
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, "#") === 0) {
        continue;
    }

    if (strpos($line, "=") !== false) {
        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (array_key_exists($key, $dbSettings)) {
            $dbSettings[$key] = $value;
        }
    }
}

// Вывод настроек БД
foreach ($dbSettings as $key => $value) {
    if ($key === "DB_PASSWORD" && !empty($value)) {
        echo "$key: ***********\n";
    } else {
        echo "$key: " . ($value !== null ? $value : "не установлено") . "\n";
    }
}

// Проверка настроек приложения
echo "\nОсновные настройки приложения:\n";
echo "----------------------------\n";

$appSettings = [
    "APP_NAME" => null,
    "APP_ENV" => null,
    "APP_KEY" => null,
    "APP_DEBUG" => null,
    "APP_URL" => null,
];

// Извлечение настроек приложения из .env
foreach ($lines as $line) {
    $line = trim($line);
    if (empty($line) || strpos($line, "#") === 0) {
        continue;
    }

    if (strpos($line, "=") !== false) {
        [$key, $value] = explode("=", $line, 2);
        $key = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if (array_key_exists($key, $appSettings)) {
            $appSettings[$key] = $value;
        }
    }
}

// Вывод настроек приложения
foreach ($appSettings as $key => $value) {
    echo "$key: " . ($value !== null ? $value : "не установлено") . "\n";
}

// Проверка ключа приложения
if (empty($appSettings["APP_KEY"]) || $appSettings["APP_KEY"] === "base64:") {
    echo "\nВНИМАНИЕ: APP_KEY не установлен. Выполните команду:\n";
    echo "php artisan key:generate\n";
}

// Рекомендации по очистке кэша
echo "\nРекомендации по очистке кэша:\n";
echo "php artisan config:clear\n";
echo "php artisan cache:clear\n";
echo "php artisan route:clear\n";
echo "php artisan view:clear\n";

echo "\nПроверка окружения завершена.\n";
