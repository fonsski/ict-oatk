#!/bin/bash

# Скрипт для запуска Telegram бота в Docker контейнере

# Настройка логирования
LOG_FILE="/var/www/html/storage/logs/telegram-bot.log"
echo "$(date) - Запуск скрипта Telegram бота" >> $LOG_FILE

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

# Проверка существования предыдущего экземпляра
if pgrep -f "php artisan telegram:standalone" > /dev/null; then
    echo "ВНИМАНИЕ: Обнаружен уже работающий экземпляр бота. Проверка на дубликаты."
    echo "$(date) - Обнаружен работающий экземпляр бота" >> $LOG_FILE

    # Подсчитываем количество запущенных экземпляров
    INSTANCES=$(pgrep -c -f "php artisan telegram:standalone")
    if [ "$INSTANCES" -gt 1 ]; then
        echo "ВНИМАНИЕ: Обнаружено $INSTANCES экземпляров бота. Завершаем лишние."
        echo "$(date) - Обнаружено $INSTANCES экземпляров бота" >> $LOG_FILE

        # Оставляем только самый старый процесс
        pgrep -f "php artisan telegram:standalone" | sort | tail -n +2 | xargs kill -15
        sleep 2
    fi
fi

# Проверяем наличие токена
if [ -z "$TELEGRAM_BOT_TOKEN" ]; then
    # Проверяем токен в .env файле
    if grep -q "TELEGRAM_BOT_TOKEN" .env; then
        echo "Токен найден в .env файле"
        # Экспортируем токен из .env файла
        export TELEGRAM_BOT_TOKEN=$(grep "TELEGRAM_BOT_TOKEN" .env | cut -d '=' -f2)
    else
        echo "ОШИБКА: Токен Telegram бота не найден! Добавьте TELEGRAM_BOT_TOKEN в .env файл"
        echo "$(date) - ОШИБКА: Токен не найден" >> $LOG_FILE
        exit 1
    fi
fi

# Удаляем webhook перед запуском long polling
echo "Удаляем существующий webhook..."
php artisan telegram:delete-webhook

# Проверяем подключение к API Telegram
echo "Проверка подключения к API Telegram..."
if ! php artisan telegram:basic-test; then
    echo "ОШИБКА: Не удалось подключиться к API Telegram. Проверьте токен и соединение."
    echo "$(date) - ОШИБКА: Неудачная проверка подключения к API" >> $LOG_FILE
    exit 1
fi
echo "Подключение к API Telegram успешно."

# Запускаем бота
echo "Запускаем бота в режиме long polling..."
echo "$(date) - Запуск бота" >> $LOG_FILE

# Устанавливаем обработчик ошибок
ERROR_COUNT=0
MAX_ERRORS=5
RESTART_DELAY=10

while true; do
    # Запускаем бота с перехватом ошибок
    if ! php artisan telegram:standalone; then
        ERROR_COUNT=$((ERROR_COUNT+1))
        echo "ОШИБКА: Бот завершился с кодом ошибки. Попытка $ERROR_COUNT из $MAX_ERRORS."
        echo "$(date) - Ошибка выполнения, попытка $ERROR_COUNT" >> $LOG_FILE

        if [ "$ERROR_COUNT" -ge "$MAX_ERRORS" ]; then
            echo "Достигнуто максимальное количество попыток перезапуска ($MAX_ERRORS). Завершаем работу."
            echo "$(date) - Достигнут лимит попыток перезапуска" >> $LOG_FILE
            exit 1
        fi

        echo "Перезапуск через $RESTART_DELAY секунд..."
        sleep $RESTART_DELAY
    else
        # Если бот завершился без ошибок, сбрасываем счетчик
        ERROR_COUNT=0
    fi
done

# Этот код не должен выполниться из-за бесконечного цикла выше
echo "Бот завершил работу штатно. Это необычно."
echo "Время завершения: $(date)"
echo "$(date) - Штатное завершение работы (неожиданно)" >> $LOG_FILE

# Проверяем логи на наличие ошибок
if grep -q "Error\|Exception\|Fatal" storage/logs/laravel.log; then
    echo "ВНИМАНИЕ: В логах обнаружены ошибки!"
    echo "$(date) - В логах обнаружены ошибки" >> $LOG_FILE
fi

exit 0
