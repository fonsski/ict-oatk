#!/bin/bash

# Скрипт для установки всех улучшений Telegram-бота

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

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

# Функция для вывода заголовков
print_header() {
    echo -e "\n${BLUE}====== $1 ======${NC}\n"
}

# Проверка, запущен ли скрипт из корня проекта
if [ ! -f "artisan" ]; then
    print_error "Скрипт должен быть запущен из корня проекта!"
    exit 1
fi

print_header "Установка улучшений Telegram-бота"

# Проверка наличия токена в .env
print_info "Проверка наличия токена Telegram-бота..."
if ! grep -q "TELEGRAM_BOT_TOKEN" .env; then
    print_warning "Токен Telegram-бота не найден в .env файле."
    read -p "Введите токен бота (получить у @BotFather): " bot_token
    echo "TELEGRAM_BOT_TOKEN=$bot_token" >> .env
    print_info "Токен добавлен в .env файл."
else
    print_info "Токен Telegram-бота уже настроен в .env файле."
fi

# Проверяем установку зависимостей
print_info "Проверка необходимых пакетов..."
if ! grep -q "botman/botman" composer.json || ! grep -q "botman/driver-telegram" composer.json; then
    print_warning "Необходимые пакеты не найдены, устанавливаем..."
    ./vendor/bin/sail composer require botman/botman botman/driver-telegram
else
    print_info "Необходимые пакеты уже установлены."
fi

# Запуск миграций
print_header "Применение миграций"
print_info "Запуск миграций для создания необходимых таблиц..."
./vendor/bin/sail artisan migrate

# Очистка кэша
print_info "Очистка кэша конфигурации..."
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear

# Проверка доступа к API Telegram
print_header "Проверка подключения к API Telegram"
print_info "Тестирование токена Telegram-бота..."
if ./vendor/bin/sail artisan telegram:basic-test; then
    print_info "Подключение к API Telegram успешно установлено."
else
    print_error "Не удалось подключиться к API Telegram. Проверьте токен и соединение."
    print_warning "Вы можете продолжить установку, но бот может не работать корректно."
    read -p "Продолжить установку? (y/n): " continue_install
    if [[ "$continue_install" != "y" ]]; then
        print_info "Установка отменена."
        exit 1
    fi
fi

# Настройка прав доступа к скриптам
print_header "Настройка прав доступа"
print_info "Установка прав на выполнение скриптов..."
chmod +x manage_bots.sh
chmod +x run-telegram-bot.sh
chmod +x bot.sh
chmod +x check_bots.sh

# Создание директории для логов
print_info "Создание директории для логов..."
mkdir -p storage/logs/telegram

# Остановка существующих ботов
print_header "Остановка существующих экземпляров бота"
print_info "Проверка и остановка работающих экземпляров бота..."
./manage_bots.sh stop

# Запуск бота с улучшениями
print_header "Запуск бота с улучшениями"
print_info "Запуск бота..."
./manage_bots.sh start

# Финальная проверка
print_header "Финальная проверка"
print_info "Проверка статуса бота..."
./manage_bots.sh status

print_header "Установка завершена"
print_info "Все улучшения Telegram-бота установлены и применены."
print_info "Используйте './manage_bots.sh help' для просмотра доступных команд управления ботом."
print_info "Документация доступна в файле docs/telegram-bot-improved.md."

exit 0
