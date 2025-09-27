#!/bin/bash

# Скрипт для запуска end-to-end тестов

set -e

echo "🧪 Запуск end-to-end тестов для системы ICT..."

# Проверяем, что мы в правильной директории
if [ ! -f "package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из директории tests/e2e"
    exit 1
fi

# Проверяем наличие Node.js
if ! command -v node &> /dev/null; then
    echo "❌ Ошибка: Node.js не установлен"
    exit 1
fi

# Проверяем наличие npm
if ! command -v npm &> /dev/null; then
    echo "❌ Ошибка: npm не установлен"
    exit 1
fi

# Устанавливаем зависимости если их нет
if [ ! -d "node_modules" ]; then
    echo "📦 Установка зависимостей..."
    npm install
fi

# Устанавливаем браузеры Playwright если их нет
if [ ! -d "node_modules/@playwright/test" ]; then
    echo "🌐 Установка браузеров Playwright..."
    npm run test:install
fi

# Проверяем доступность приложения
echo "🔍 Проверка доступности приложения..."
if ! curl -s http://localhost:8000 > /dev/null; then
    echo "⚠️ Приложение не доступно на http://localhost:8000"
    echo "Запустите приложение командой: php artisan serve"
    exit 1
fi

# Запускаем тесты в зависимости от параметров
case "${1:-all}" in
    "auth")
        echo "🔐 Запуск тестов аутентификации..."
        npm run test auth.spec.js
        ;;
    "tickets")
        echo "🎫 Запуск тестов заявок..."
        npm run test tickets.spec.js
        ;;
    "equipment")
        echo "🖥️ Запуск тестов оборудования..."
        npm run test equipment.spec.js
        ;;
    "rooms")
        echo "🏢 Запуск тестов кабинетов..."
        npm run test rooms.spec.js
        ;;
    "users")
        echo "👥 Запуск тестов пользователей..."
        npm run test users.spec.js
        ;;
    "knowledge")
        echo "📚 Запуск тестов базы знаний..."
        npm run test knowledge.spec.js
        ;;
    "notifications")
        echo "🔔 Запуск тестов уведомлений..."
        npm run test notifications.spec.js
        ;;
    "integration")
        echo "🔗 Запуск интеграционных тестов..."
        npm run test integration.spec.js
        ;;
    "all")
        echo "🚀 Запуск всех тестов..."
        npm run test
        ;;
    "headed")
        echo "👀 Запуск тестов с видимым браузером..."
        npm run test:headed
        ;;
    "debug")
        echo "🐛 Запуск тестов в режиме отладки..."
        npm run test:debug
        ;;
    "ui")
        echo "🎨 Запуск тестов с UI..."
        npm run test:ui
        ;;
    "mobile")
        echo "📱 Запуск мобильных тестов..."
        npm run test -- --project="Mobile Chrome" --project="Mobile Safari"
        ;;
    "desktop")
        echo "🖥️ Запуск десктопных тестов..."
        npm run test -- --project="chromium" --project="firefox" --project="webkit"
        ;;
    "help")
        echo "📖 Доступные команды:"
        echo "  ./run-tests.sh auth          - Тесты аутентификации"
        echo "  ./run-tests.sh tickets       - Тесты заявок"
        echo "  ./run-tests.sh equipment     - Тесты оборудования"
        echo "  ./run-tests.sh rooms         - Тесты кабинетов"
        echo "  ./run-tests.sh users         - Тесты пользователей"
        echo "  ./run-tests.sh knowledge     - Тесты базы знаний"
        echo "  ./run-tests.sh notifications - Тесты уведомлений"
        echo "  ./run-tests.sh integration   - Интеграционные тесты"
        echo "  ./run-tests.sh all           - Все тесты (по умолчанию)"
        echo "  ./run-tests.sh headed        - С видимым браузером"
        echo "  ./run-tests.sh debug         - Режим отладки"
        echo "  ./run-tests.sh ui            - С UI"
        echo "  ./run-tests.sh mobile        - Мобильные тесты"
        echo "  ./run-tests.sh desktop       - Десктопные тесты"
        echo "  ./run-tests.sh help          - Эта справка"
        exit 0
        ;;
    *)
        echo "❌ Неизвестная команда: $1"
        echo "Используйте './run-tests.sh help' для просмотра доступных команд"
        exit 1
        ;;
esac

echo "✅ Тесты завершены!"
echo "📊 Отчеты доступны в директории playwright-report/"
echo "🎥 Видео и скриншоты доступны в директории test-results/"
