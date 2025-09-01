#!/bin/bash

# Улучшенный скрипт для управления Telegram ботами

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
NC='\033[0m' # No Color

# Логи
LOG_FILE="storage/logs/telegram-bot-manager.log"

# Функция для вывода информации в консоль и запись в лог
log_info() {
    local message="$1"
    echo -e "${GREEN}[INFO]${NC} $message"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [INFO] $message" >> $LOG_FILE
}

# Функция для вывода предупреждений
log_warning() {
    local message="$1"
    echo -e "${YELLOW}[ПРЕДУПРЕЖДЕНИЕ]${NC} $message"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ПРЕДУПРЕЖДЕНИЕ] $message" >> $LOG_FILE
}

# Функция для вывода ошибок
log_error() {
    local message="$1"
    echo -e "${RED}[ОШИБКА]${NC} $message"
    echo "$(date '+%Y-%m-%d %H:%M:%S') [ОШИБКА] $message" >> $LOG_FILE
}

# Функция для вывода подзаголовков
log_section() {
    local message="$1"
    echo -e "${BLUE}=== $message ===${NC}"
    echo "$(date '+%Y-%m-%d %H:%M:%S') === $message ===" >> $LOG_FILE
}

# Функция для проверки статуса бота
check_status() {
    log_section "Проверка статуса ботов"

    # Сначала проверяем, запущен ли Docker-контейнер с ботом
    if docker ps | grep -q "telegram-bot"; then
        log_info "Обнаружен Docker-контейнер с Telegram ботом."

        # Проверяем логи контейнера, чтобы убедиться, что бот работает
        docker logs --tail 10 $(docker ps -qf "name=telegram-bot") 2>&1 | grep -i "telegram" | head -n 3

        return 0
    fi

    # Если контейнер не запущен, проверяем процессы в основном контейнере
    local running_bots=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:" | grep -v grep)

    if [ -z "$running_bots" ]; then
        log_warning "Ботов не обнаружено"
        return 1
    else
        log_info "Обнаружены запущенные экземпляры бота:"
        echo "$running_bots"

        # Подсчет запущенных экземпляров
        local count=$(echo "$running_bots" | wc -l)
        log_info "Всего запущено экземпляров: $count"

        # Проверка на дубликаты
        if [ $count -gt 1 ]; then
            log_warning "Обнаружено несколько экземпляров бота! Это может привести к дублированию уведомлений."
        fi

        return 0
    fi
}

# Функция для запуска бота
start_bot() {
    log_section "Запуск Telegram бота"

    # Проверяем, не запущен ли уже бот
    if check_status > /dev/null; then
        log_warning "Бот уже запущен. Используйте 'restart', если хотите перезапустить."
        return 1
    fi

    # Проверяем наличие и действительность токена
    log_info "Проверка токена Telegram бота..."

    # Проверяем, что токен существует в конфигурации
    token=$(./vendor/bin/sail artisan tinker --execute="echo config('services.telegram.token');" 2>/dev/null)

    if [ -z "$token" ]; then
        log_error "Токен Telegram бота отсутствует в конфигурации. Проверьте TELEGRAM_BOT_TOKEN в .env файле."
        return 1
    fi

    # Простая проверка формата токена
    if [[ ! "$token" =~ [0-9]+:.+ ]]; then
        log_error "Токен Telegram бота имеет неправильный формат. Проверьте TELEGRAM_BOT_TOKEN в .env файле."
        return 1
    fi

    log_info "Токен Telegram бота найден в конфигурации и имеет правильный формат."

    # Удаляем webhook перед запуском в режиме long polling
    log_info "Удаление существующих webhook..."

    # Получаем токен из конфигурации
    token=$(./vendor/bin/sail artisan tinker --execute="echo config('services.telegram.token');" 2>/dev/null)

    if [ -n "$token" ]; then
        delete_result=$(curl -s "https://api.telegram.org/bot$token/deleteWebhook")
        if echo "$delete_result" | grep -q "\"ok\":true"; then
            log_info "Webhook успешно удален."
        else
            log_warning "Не удалось удалить webhook: $(echo "$delete_result" | grep -o '"description":"[^"]*"' | cut -d'"' -f4)"
        fi
    else
        log_warning "Не удалось получить токен для удаления webhook."
    fi

    # Запускаем бота в Docker
    log_info "Запуск бота в Docker..."
    if [ -f "docker-compose.telegram.yml" ]; then
        # Используем отдельный контейнер для бота
        ./vendor/bin/sail -f docker-compose.yml -f docker-compose.telegram.yml up -d telegram-bot
        log_info "Бот запущен в отдельном контейнере."
    else
        # Запускаем в текущем контейнере
        ./vendor/bin/sail exec -d laravel.test php artisan telegram:standalone > /dev/null 2>&1
        log_info "Бот запущен в основном контейнере Laravel."
    fi

    # Проверяем, запустился ли бот
    log_info "Ожидание запуска бота..."
    # Даём боту больше времени на запуск
    sleep 10

    # Проверяем запущен ли контейнер с ботом
    if docker ps | grep -q "telegram-bot"; then
        log_info "Контейнер бота запущен."
        log_info "Логи доступны по команде: ./manage_bots.sh logs"
        return 0
    else
        # Пробуем проверить статус через основной метод
        if check_status > /dev/null; then
            log_info "Бот успешно запущен."
            log_info "Логи доступны по команде: ./manage_bots.sh logs"
            return 0
        else
            log_error "Не удалось запустить бота. Проверьте логи."
            return 1
        fi
    fi
}

# Функция для остановки бота
stop_bot() {
    log_section "Остановка Telegram бота"

    # Проверяем, запущен ли бот
    if ! check_status > /dev/null; then
        log_warning "Бот не запущен."
        return 1
    fi

    local bot_pids=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:" | grep -v grep | awk '{print $2}')

    if [ -z "$bot_pids" ]; then
        log_warning "Не удалось получить PID процессов бота."
        return 1
    fi

    # Если используется отдельный контейнер
    if [ -f "docker-compose.telegram.yml" ] && docker ps | grep -q "telegram-bot"; then
        log_info "Остановка контейнера Telegram бота..."
        ./vendor/bin/sail -f docker-compose.yml -f docker-compose.telegram.yml stop telegram-bot
    else
        # Остановка процессов бота
        log_info "Остановка процессов бота (PID: $bot_pids)..."
        for pid in $bot_pids; do
            ./vendor/bin/sail exec laravel.test kill -15 $pid > /dev/null 2>&1
        done

        # Проверяем, остановились ли процессы
        sleep 2
        if check_status > /dev/null; then
            log_warning "Не удалось корректно остановить бота. Принудительная остановка..."
            for pid in $bot_pids; do
                ./vendor/bin/sail exec laravel.test kill -9 $pid > /dev/null 2>&1
            done
        fi
    fi

    # Проверяем результат
    if ! check_status > /dev/null; then
        log_info "Бот успешно остановлен."
        return 0
    else
        log_error "Не удалось остановить бота."
        return 1
    fi
}

# Функция для перезапуска бота
restart_bot() {
    log_section "Перезапуск Telegram бота"

    stop_bot
    sleep 2
    start_bot
}

# Функция для просмотра логов
view_logs() {
    log_section "Просмотр логов Telegram бота"

    # Проверяем, запущен ли Docker-контейнер с ботом
    if docker ps | grep -q "telegram-bot"; then
        log_info "Просмотр логов Docker-контейнера с ботом:"
        docker logs --tail 50 $(docker ps -qf "name=telegram-bot")

        log_info "Для отслеживания логов в реальном времени используйте: docker logs -f $(docker ps -qf 'name=telegram-bot')"
        return 0
    fi

    # Если контейнер не запущен, пробуем найти логи в файле
    local log_path="storage/logs/telegram-bot.log"

    if [ ! -f "$log_path" ]; then
        log_warning "Файл логов бота не найден."
        log_info "Проверьте логи Laravel: ./vendor/bin/sail artisan pail"

        # Последняя попытка - проверить общие логи Laravel
        log_info "Пробуем найти упоминания о Telegram в общих логах Laravel:"
        ./vendor/bin/sail exec laravel.test grep -i "telegram" /var/www/html/storage/logs/laravel.log | tail -n 20

        return 1
    fi

    log_info "Последние записи в логах бота:"
    ./vendor/bin/sail exec laravel.test tail -n 50 /var/www/html/$log_path

    log_info "Для отслеживания логов в реальном времени используйте: ./vendor/bin/sail exec laravel.test tail -f /var/www/html/$log_path"
}

# Функция для устранения дубликатов ботов
cleanup_duplicates() {
    log_section "Устранение дублирующихся экземпляров бота"

    local running_bots=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:" | grep -v grep)

    if [ -z "$running_bots" ]; then
        log_info "Запущенных экземпляров бота не обнаружено."
        return 0
    fi

    local count=$(echo "$running_bots" | wc -l)

    if [ $count -eq 1 ]; then
        log_info "Обнаружен только один экземпляр бота. Очистка не требуется."
        return 0
    fi

    log_warning "Обнаружено $count экземпляров бота. Оставляем только самый старый экземпляр."

    # Получаем PID всех экземпляров кроме самого старого
    local pids_to_kill=$(./vendor/bin/sail exec laravel.test ps -o pid,start_time -p $(echo "$running_bots" | awk '{print $2}' | tr '\n' ',') | sort -k 2 | tail -n +2 | awk '{print $1}')

    if [ -z "$pids_to_kill" ]; then
        log_warning "Не удалось определить PID процессов для завершения."
        return 1
    fi

    # Завершаем лишние процессы
    for pid in $pids_to_kill; do
        log_info "Завершение процесса с PID $pid..."
        ./vendor/bin/sail exec laravel.test kill -15 $pid > /dev/null 2>&1
    done

    # Проверяем результат
    sleep 2
    local remaining=$(./vendor/bin/sail exec laravel.test ps aux | grep "php artisan telegram:" | grep -v grep | wc -l)

    if [ $remaining -eq 1 ]; then
        log_info "Успешно оставлен один экземпляр бота."
        return 0
    else
        log_warning "После очистки осталось $remaining экземпляров бота."
        return 1
    fi
}

# Функция для диагностики проблем
diagnose() {
    log_section "Диагностика проблем с Telegram ботом"

    # Проверка наличия токена
    log_info "Проверка наличия токена Telegram бота..."
    if ! grep -q "TELEGRAM_BOT_TOKEN" .env; then
        log_error "Токен Telegram бота не найден в .env файле."
    else
        log_info "Токен найден в .env файле."
    fi

    # Проверка валидности токена
    log_info "Проверка валидности токена..."

    # Получаем токен из конфигурации
    token=$(./vendor/bin/sail artisan tinker --execute="echo config('services.telegram.token');" 2>/dev/null)

    if [ -z "$token" ]; then
        log_error "Токен Telegram бота отсутствует в конфигурации. Проверьте TELEGRAM_BOT_TOKEN в .env файле."
    elif [[ ! "$token" =~ [0-9]+:.+ ]]; then
        log_error "Токен Telegram бота имеет неправильный формат: $token"
    else
        log_info "Токен найден в конфигурации и имеет правильный формат."
        log_info "Токен: ${token:0:5}...${token:(-5)}"

        # Проверяем, запущен ли другой экземпляр бота с этим токеном
        log_info "Проверка API подключения к Telegram..."
        curl_result=$(curl -s "https://api.telegram.org/bot$token/getMe")

        if echo "$curl_result" | grep -q "\"ok\":true"; then
            log_info "Подключение к API Telegram успешно установлено."
            bot_username=$(echo "$curl_result" | grep -o '"username":"[^"]*"' | cut -d'"' -f4)
            bot_id=$(echo "$curl_result" | grep -o '"id":[0-9]*' | cut -d':' -f2)
            log_info "Бот @$bot_username (ID: $bot_id) готов к работе."
        else
            error_desc=$(echo "$curl_result" | grep -o '"description":"[^"]*"' | cut -d'"' -f4)
            log_error "Ошибка при подключении к API Telegram: $error_desc"
            log_info "Запустите ./vendor/bin/sail artisan telegram:basic-test для подробностей."
        fi
    fi

    # Проверка наличия маршрутов
    log_info "Проверка наличия маршрутов для бота..."
    if ./vendor/bin/sail artisan route:list | grep -q "/api/telegram/webhook"; then
        log_info "Маршрут для webhook найден."
    else
        log_warning "Маршрут для webhook не найден."
    fi

    # Проверка состояния webhook
    log_info "Проверка статуса webhook..."
    local token=$(grep "TELEGRAM_BOT_TOKEN" .env | cut -d '=' -f2)
    if [ -n "$token" ]; then
        local webhook_info=$(curl -s "https://api.telegram.org/bot$token/getWebhookInfo")
        if echo "$webhook_info" | grep -q "\"ok\":true"; then
            log_info "Информация о webhook получена."
            if echo "$webhook_info" | grep -q "\"url\":\"\""; then
                log_info "Webhook не установлен (это нормально для режима long polling)."
            else
                local webhook_url=$(echo "$webhook_info" | grep -o '"url":"[^"]*"' | cut -d'"' -f4)
                log_info "Webhook установлен на URL: $webhook_url"
            fi
        else
            log_error "Не удалось получить информацию о webhook."
        fi
    fi

    # Проверка наличия необходимых пакетов
    log_info "Проверка наличия необходимых пакетов..."
    if grep -q "botman/botman" composer.json && grep -q "botman/driver-telegram" composer.json; then
        log_info "Необходимые пакеты найдены в composer.json."
    else
        log_error "Необходимые пакеты не найдены в composer.json."
    fi

    # Проверка наличия миграций
    log_info "Проверка наличия необходимых таблиц в базе данных..."
    if ./vendor/bin/sail artisan migrate:status | grep -q "sent_telegram_notifications"; then
        log_info "Таблица sent_telegram_notifications найдена в схеме базы данных."
    else
        log_warning "Таблица sent_telegram_notifications не найдена. Необходимо выполнить миграции."
    fi

    log_info "Диагностика завершена."
}

# Функция для отображения справки
show_help() {
    echo "Использование: ./manage_bots.sh [команда]"
    echo ""
    echo "Доступные команды:"
    echo "  start    - Запуск бота"
    echo "  stop     - Остановка бота"
    echo "  restart  - Перезапуск бота"
    echo "  status   - Проверка статуса бота"
    echo "  logs     - Просмотр логов бота"
    echo "  cleanup  - Устранение дубликатов ботов"
    echo "  diagnose - Диагностика проблем"
    echo "  help     - Показать эту справку"
    echo ""
}

# Создаем директорию для логов, если она не существует
mkdir -p $(dirname $LOG_FILE)

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
    cleanup)
        cleanup_duplicates
        ;;
    diagnose)
        diagnose
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
