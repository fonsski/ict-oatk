#!/bin/bash

# Скрипт для запуска Telegram бота через Sail
# Используется для автоматического запуска бота при старте приложения

echo "🤖 Запуск Telegram бота..."

# Проверяем наличие токена
if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    echo "❌ Ошибка: TELEGRAM_BOT_TOKEN не установлен"
    echo "Добавьте TELEGRAM_BOT_TOKEN в файл .env"
    exit 1
fi

# Проверяем подключение к базе данных
echo "🔍 Проверка подключения к базе данных..."
php artisan migrate:status > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Ошибка: Не удается подключиться к базе данных"
    echo "Убедитесь, что база данных запущена и настроена"
    exit 1
fi

echo "✅ База данных доступна"

# Проверяем настройки Telegram
echo "🔍 Проверка настроек Telegram..."
php artisan telegram:test > /dev/null 2>&1
if [ $? -ne 0 ]; then
    echo "❌ Ошибка: Не удается подключиться к Telegram API"
    echo "Проверьте TELEGRAM_BOT_TOKEN"
    exit 1
fi

echo "✅ Telegram API доступен"

# Запускаем бота
echo "🚀 Запуск Telegram бота в режиме polling..."
php artisan telegram:bot --mode=polling
