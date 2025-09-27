#!/bin/bash

# Скрипт для запуска конкретных тестов

set -e

echo "🧪 Запуск конкретных тестов для системы ICT..."

# Проверяем, что мы в правильной директории
if [ ! -f "package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из директории tests/e2e"
    exit 1
fi

# Проверяем доступность приложения
echo "🔍 Проверка доступности приложения..."
if ! curl -s http://localhost > /dev/null; then
    echo "⚠️ Приложение не доступно на http://localhost"
    echo "Запустите приложение командой: ./vendor/bin/sail up -d"
    exit 1
fi

# Функция для запуска тестов
run_tests() {
    local test_file="$1"
    local description="$2"
    
    echo ""
    echo "🚀 Запуск: $description"
    echo "📁 Файл: $test_file"
    echo "⏱️ Время: $(date)"
    echo "----------------------------------------"
    
    npx playwright test "$test_file" --project=chromium --headed
    
    echo "✅ Тест завершен: $description"
    echo "⏱️ Время завершения: $(date)"
    echo "----------------------------------------"
}

# Меню выбора тестов
case "${1:-menu}" in
    "auth")
        run_tests "auth.spec.js" "Тесты аутентификации"
        ;;
    "tickets")
        run_tests "tickets.spec.js" "Тесты управления заявками"
        ;;
    "equipment")
        run_tests "equipment.spec.js" "Тесты управления оборудованием"
        ;;
    "rooms")
        run_tests "rooms.spec.js" "Тесты управления кабинетами"
        ;;
    "users")
        run_tests "users.spec.js" "Тесты управления пользователями"
        ;;
    "knowledge")
        run_tests "knowledge.spec.js" "Тесты базы знаний"
        ;;
    "notifications")
        run_tests "notifications.spec.js" "Тесты системы уведомлений"
        ;;
    "integration")
        run_tests "integration.spec.js" "Интеграционные тесты"
        ;;
    "errors")
        run_tests "error-handling.spec.js" "Тесты обработки ошибок"
        ;;
    "performance")
        run_tests "performance.spec.js" "Тесты производительности"
        ;;
    "security")
        run_tests "security.spec.js" "Тесты безопасности"
        ;;
    "smoke")
        echo "🔥 Запуск smoke тестов (основные функции)..."
        run_tests "auth.spec.js" "Тесты аутентификации"
        run_tests "tickets.spec.js" "Тесты управления заявками"
        run_tests "integration.spec.js" "Интеграционные тесты"
        ;;
    "critical")
        echo "🚨 Запуск критических тестов..."
        run_tests "auth.spec.js" "Тесты аутентификации"
        run_tests "security.spec.js" "Тесты безопасности"
        run_tests "error-handling.spec.js" "Тесты обработки ошибок"
        ;;
    "all")
        echo "🚀 Запуск всех тестов..."
        npx playwright test --project=chromium
        ;;
    "menu")
        echo "📖 Доступные команды:"
        echo ""
        echo "🔐 Аутентификация и авторизация:"
        echo "  ./run-specific-tests.sh auth          - Тесты аутентификации"
        echo ""
        echo "📋 Основные функции:"
        echo "  ./run-specific-tests.sh tickets       - Тесты заявок"
        echo "  ./run-specific-tests.sh equipment     - Тесты оборудования"
        echo "  ./run-specific-tests.sh rooms         - Тесты кабинетов"
        echo "  ./run-specific-tests.sh users         - Тесты пользователей"
        echo "  ./run-specific-tests.sh knowledge     - Тесты базы знаний"
        echo "  ./run-specific-tests.sh notifications - Тесты уведомлений"
        echo ""
        echo "🔗 Интеграционные тесты:"
        echo "  ./run-specific-tests.sh integration   - Интеграционные тесты"
        echo ""
        echo "🛡️ Качество и безопасность:"
        echo "  ./run-specific-tests.sh errors        - Тесты обработки ошибок"
        echo "  ./run-specific-tests.sh performance   - Тесты производительности"
        echo "  ./run-specific-tests.sh security      - Тесты безопасности"
        echo ""
        echo "🎯 Группы тестов:"
        echo "  ./run-specific-tests.sh smoke         - Smoke тесты (основные функции)"
        echo "  ./run-specific-tests.sh critical     - Критические тесты"
        echo "  ./run-specific-tests.sh all          - Все тесты"
        echo ""
        echo "💡 Примеры использования:"
        echo "  ./run-specific-tests.sh auth          # Только тесты аутентификации"
        echo "  ./run-specific-tests.sh smoke         # Основные функции"
        echo "  ./run-specific-tests.sh critical      # Критические тесты"
        echo "  ./run-specific-tests.sh all           # Все тесты"
        ;;
    *)
        echo "❌ Неизвестная команда: $1"
        echo "Используйте './run-specific-tests.sh menu' для просмотра доступных команд"
        exit 1
        ;;
esac

echo ""
echo "✅ Тесты завершены!"
echo "📊 Отчеты доступны в директории playwright-report/"
echo "🎥 Видео и скриншоты доступны в директории test-results/"
