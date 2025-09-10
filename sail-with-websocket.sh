#!/bin/bash

# Sail with WebSocket Management Script
# Управление Laravel Sail с автоматическим запуском WebSocket сервера

echo "🚀 Laravel Sail with WebSocket Server"
echo "======================================"

# Функция для проверки статуса контейнеров
check_containers() {
    echo "📊 Проверка статуса контейнеров..."
    docker-compose ps
}

# Функция для запуска всех сервисов
start_all() {
    echo "🚀 Запуск всех сервисов (Laravel + WebSocket + Telegram Bot)..."
    ./vendor/bin/sail up -d
    
    echo "⏳ Ожидание запуска сервисов..."
    sleep 10
    
    echo "🔍 Проверка статуса WebSocket сервера..."
    if curl -f http://localhost:8080/test > /dev/null 2>&1; then
        echo "✅ WebSocket сервер запущен и работает"
    else
        echo "❌ WebSocket сервер не отвечает"
        echo "📋 Логи WebSocket сервера:"
        ./vendor/bin/sail logs websocket-server --tail=20
    fi
}

# Функция для остановки всех сервисов
stop_all() {
    echo "🛑 Остановка всех сервисов..."
    ./vendor/bin/sail down
}

# Функция для перезапуска WebSocket сервера
restart_websocket() {
    echo "🔄 Перезапуск WebSocket сервера..."
    ./vendor/bin/sail restart websocket-server
    
    echo "⏳ Ожидание запуска WebSocket сервера..."
    sleep 5
    
    echo "🔍 Проверка статуса WebSocket сервера..."
    if curl -f http://localhost:8080/test > /dev/null 2>&1; then
        echo "✅ WebSocket сервер перезапущен и работает"
    else
        echo "❌ WebSocket сервер не отвечает"
    fi
}

# Функция для просмотра логов
show_logs() {
    echo "📋 Логи WebSocket сервера:"
    ./vendor/bin/sail logs websocket-server --tail=50 -f
}

# Функция для проверки здоровья сервисов
health_check() {
    echo "🏥 Проверка здоровья сервисов..."
    
    echo "🔍 Laravel приложение:"
    if curl -f http://localhost > /dev/null 2>&1; then
        echo "  ✅ Laravel приложение работает"
    else
        echo "  ❌ Laravel приложение не отвечает"
    fi
    
    echo "🔍 WebSocket сервер:"
    if curl -f http://localhost:8080/test > /dev/null 2>&1; then
        echo "  ✅ WebSocket сервер работает"
        echo "  📊 Статус WebSocket:"
        curl -s http://localhost:8080/status | jq . 2>/dev/null || curl -s http://localhost:8080/status
    else
        echo "  ❌ WebSocket сервер не отвечает"
    fi
    
    echo "🔍 Telegram Bot:"
    if docker-compose ps telegram-bot | grep -q "Up"; then
        echo "  ✅ Telegram Bot запущен"
    else
        echo "  ❌ Telegram Bot не запущен"
    fi
}

# Функция для показа помощи
show_help() {
    echo "Использование: $0 [команда]"
    echo ""
    echo "Команды:"
    echo "  start     - Запустить все сервисы"
    echo "  stop      - Остановить все сервисы"
    echo "  restart   - Перезапустить все сервисы"
    echo "  websocket - Перезапустить только WebSocket сервер"
    echo "  status    - Показать статус контейнеров"
    echo "  health    - Проверить здоровье сервисов"
    echo "  logs      - Показать логи WebSocket сервера"
    echo "  help      - Показать эту справку"
    echo ""
    echo "Примеры:"
    echo "  $0 start     # Запустить все сервисы"
    echo "  $0 health    # Проверить здоровье"
    echo "  $0 logs      # Показать логи WebSocket"
}

# Основная логика
case "${1:-help}" in
    "start")
        start_all
        ;;
    "stop")
        stop_all
        ;;
    "restart")
        stop_all
        start_all
        ;;
    "websocket")
        restart_websocket
        ;;
    "status")
        check_containers
        ;;
    "health")
        health_check
        ;;
    "logs")
        show_logs
        ;;
    "help"|*)
        show_help
        ;;
esac
