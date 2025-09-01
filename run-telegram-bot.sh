#!/bin/bash

# Скрипт для запуска Telegram бота в Docker контейнере

# Переходим в директорию проекта
cd /var/www/html

# Функция для правильного завершения бота при получении сигнала
function cleanup {
    echo "Получен сигнал остановки. Завершаем работу бота..."
    exit 0
}

# Перехватываем сигналы для корректного завершения
trap cleanup SIGINT SIGTERM

echo "Запускаем Telegram бота..."
echo "Время запуска: $(date)"

# Проверяем наличие токена
if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    # Проверяем токен в .env файле
    if grep -q "TELEGRAM_BOT_TOKEN" .env; then
        echo "Токен найден в .env файле"
    else
        echo "ОШИБКА: Токен Telegram бота не найден! Добавьте TELEGRAM_BOT_TOKEN в .env файл"
        exit 1
    fi
fi

# Удаляем webhook перед запуском long polling
echo "Удаляем существующий webhook..."
php artisan telegram:delete-webhook

# Запускаем бота
echo "Запускаем бота в режиме long polling..."
php artisan telegram:standalone

# Код ниже не выполнится, пока работает бот
# Если бот завершится с ошибкой, выполнится следующий код
echo "Бот завершил работу!"
echo "Время завершения: $(date)"

# Проверяем логи на наличие ошибок
if grep -q "Error\|Exception\|Fatal" storage/logs/laravel.log; then
    echo "ВНИМАНИЕ: В логах обнаружены ошибки!"
fi

exit 0
