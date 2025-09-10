#!/bin/bash

# Скрипт для проверки статуса всех сервисов

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[0;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}🔍 Проверка статуса сервисов ICT системы${NC}"
echo "=================================="

# Проверяем, запущен ли Docker
if ! docker info > /dev/null 2>&1; then
    echo -e "${RED}❌ Docker не запущен${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Docker запущен${NC}"

# Проверяем статус контейнеров
echo ""
echo -e "${BLUE}📦 Статус контейнеров:${NC}"

# Laravel приложение
if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "ict_laravel.test_1"; then
    echo -e "${GREEN}✅ Laravel приложение: Запущено${NC}"
else
    echo -e "${RED}❌ Laravel приложение: Не запущено${NC}"
fi

# MySQL
if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "ict_mysql_1"; then
    echo -e "${GREEN}✅ MySQL: Запущено${NC}"
else
    echo -e "${RED}❌ MySQL: Не запущено${NC}"
fi

# Telegram бот
if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "ict_telegram-bot_1"; then
    echo -e "${GREEN}✅ Telegram бот: Запущено${NC}"
else
    echo -e "${RED}❌ Telegram бот: Не запущено${NC}"
fi

# WebSocket сервер
if docker ps --format "table {{.Names}}\t{{.Status}}" | grep -q "ict_websocket-server_1"; then
    echo -e "${GREEN}✅ WebSocket сервер: Запущено${NC}"
else
    echo -e "${RED}❌ WebSocket сервер: Не запущено${NC}"
fi

echo ""
echo -e "${BLUE}🌐 Проверка доступности сервисов:${NC}"

# Проверяем Laravel приложение
if curl -s -o /dev/null -w "%{http_code}" http://localhost:80 | grep -q "200\|302"; then
    echo -e "${GREEN}✅ Laravel приложение: Доступно (http://localhost:80)${NC}"
else
    echo -e "${RED}❌ Laravel приложение: Недоступно${NC}"
fi

# Проверяем WebSocket сервер
if nc -z localhost 8080 2>/dev/null; then
    echo -e "${GREEN}✅ WebSocket сервер: Доступен (ws://localhost:8080)${NC}"
else
    echo -e "${RED}❌ WebSocket сервер: Недоступен${NC}"
fi

# Проверяем MySQL
if nc -z localhost 3306 2>/dev/null; then
    echo -e "${GREEN}✅ MySQL: Доступен (localhost:3306)${NC}"
else
    echo -e "${RED}❌ MySQL: Недоступен${NC}"
fi

echo ""
echo -e "${BLUE}📊 Логи контейнеров (последние 5 строк):${NC}"

# Показываем логи каждого сервиса
services=("laravel.test" "telegram-bot" "websocket-server" "mysql")

for service in "${services[@]}"; do
    echo ""
    echo -e "${YELLOW}📋 Логи $service:${NC}"
    docker logs --tail 5 "ict_${service}_1" 2>/dev/null || echo "Контейнер не найден или не запущен"
done

echo ""
echo -e "${BLUE}💡 Полезные команды:${NC}"
echo "• Запуск всех сервисов: ./vendor/bin/sail up -d"
echo "• Остановка всех сервисов: ./vendor/bin/sail down"
echo "• Просмотр логов: ./vendor/bin/sail logs"
echo "• Проверка статуса: ./vendor/bin/sail ps"
echo "• Перезапуск сервиса: ./vendor/bin/sail restart [service-name]"

echo ""
echo -e "${GREEN}✨ Проверка завершена!${NC}"
