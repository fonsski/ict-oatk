#!/bin/bash

# Скрипт для управления Telegram ботом в Laravel Sail

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
NC='\033[0m' # No Color

# Путь к лог-файлу
LOG_FILE="storage/logs/telegram-bot.log"

# Функция для вывода информации
print_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

# Функция для вывода предупреждений
print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

# Функция для вывода ошибок
print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Функция для проверки статуса бота
check_status() {
    BOT_PID=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:standalone" | grep -v grep | awk '{print $2}')

    if [ -n "$BOT_PID" ]; then
        print_info "Бот активен (PID: $BOT_PID)"
        return 0
    else
        print_warning "Бот не запущен"
        return 1
    fi
}

# Функция для запуска бота
start_bot() {
    print_info "Запуск Telegram бота..."

    if check_status > /dev/null; then
        print_warning "Бот уже запущен"
        return 1
    fi

    # Удаляем webhook перед запуском в режиме long polling
    ./vendor/bin/sail artisan telegram:delete-webhook > /dev/null 2>&1

    # Запускаем бота в фоновом режиме
    ./vendor/bin/sail exec -d laravel.test php artisan telegram:standalone > /dev/null 2>&1

    sleep 2

    if check_status > /dev/null; then
        print_info "Бот успешно запущен"
        print_info "Логи доступны по команде: ./bot.sh logs"
        return 0
    else
        print_error "Не удалось запустить бота"
        return 1
    fi
}

# Функция для остановки бота
stop_bot() {
    print_info "Остановка Telegram бота..."

    BOT_PID=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:standalone" | grep -v grep | awk '{print $2}')

    if [ -n "$BOT_PID" ]; then
        ./vendor/bin/sail exec laravel.test kill -15 $BOT_PID > /dev/null 2>&1
        sleep 2

        if check_status > /dev/null; then
            print_warning "Не удалось корректно остановить бота, принудительная остановка..."
            ./vendor/bin/sail exec laravel.test kill -9 $BOT_PID > /dev/null 2>&1
        else
            print_info "Бот успешно остановлен"
            return 0
        fi
    else
        print_warning "Бот не запущен"
        return 1
    fi
}

# Функция для перезапуска бота
restart_bot() {
    print_info "Перезапуск Telegram бота..."
    stop_bot
    sleep 2
    start_bot
}

# Функция для просмотра логов
view_logs() {
    if [ -f "$LOG_FILE" ]; then
        tail -f $LOG_FILE
    else
        print_error "Лог-файл не найден"
        print_info "Проверьте логи Laravel: ./vendor/bin/sail artisan pail"
    fi
}

# Функция для отображения справки
show_help() {
    echo "Использование: ./bot.sh [команда]"
    echo ""
    echo "Доступные команды:"
    echo "  start    - Запуск бота"
    echo "  stop     - Остановка бота"
    echo "  restart  - Перезапуск бота"
    echo "  status   - Проверка статуса бота"
    echo "  logs     - Просмотр логов бота"
    echo "  help     - Показать эту справку"
    echo ""
}

# Основная логика скрипта
case "$1" in
    start)
        start_bot
        ;;
    stop)
        stop_bot
        ;;
    restart)
        restart_bot
        ;;
    status)
        check_status
        ;;
    logs)
        view_logs
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        show_help
        exit 1
        ;;
esac

exit 0
