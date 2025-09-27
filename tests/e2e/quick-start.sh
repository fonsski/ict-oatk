#!/bin/bash

# Скрипт для быстрого запуска тестов

set -e

echo "🚀 Быстрый запуск тестов для системы ICT..."

# Проверяем, что мы в правильной директории
if [ ! -f "package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из директории tests/e2e"
    exit 1
fi

# Проверяем доступность приложения
echo "🔍 Проверка доступности приложения..."
if ! curl -s http://localhost > /dev/null; then
    echo "⚠️ Приложение не доступно на http://localhost"
    echo "💡 Запустите приложение командой: ./vendor/bin/sail up -d"
    exit 1
fi

echo "✅ Приложение доступно"

# Функция для быстрого запуска тестов
quick_test() {
    local test_type="$1"
    local description="$2"
    
    echo ""
    echo "🧪 Запуск: $description"
    echo "⏱️ Время: $(date)"
    echo "----------------------------------------"
    
    case "$test_type" in
        "smoke")
            npx playwright test auth.spec.js tickets.spec.js integration.spec.js --project=chromium --headed
            ;;
        "critical")
            npx playwright test auth.spec.js security.spec.js error-handling.spec.js --project=chromium --headed
            ;;
        "single")
            npx playwright test "$2" --project=chromium --headed
            ;;
        "all")
            npx playwright test --project=chromium --headed
            ;;
    esac
    
    echo "✅ Тест завершен: $description"
    echo "⏱️ Время завершения: $(date)"
    echo "----------------------------------------"
}

# Меню быстрого запуска
case "${1:-menu}" in
    "smoke")
        quick_test "smoke" "Smoke тесты (основные функции)"
        ;;
    "critical")
        quick_test "critical" "Критические тесты"
        ;;
    "auth")
        quick_test "single" "auth.spec.js" "Тесты аутентификации"
        ;;
    "tickets")
        quick_test "single" "tickets.spec.js" "Тесты заявок"
        ;;
    "equipment")
        quick_test "single" "equipment.spec.js" "Тесты оборудования"
        ;;
    "rooms")
        quick_test "single" "rooms.spec.js" "Тесты кабинетов"
        ;;
    "users")
        quick_test "single" "users.spec.js" "Тесты пользователей"
        ;;
    "knowledge")
        quick_test "single" "knowledge.spec.js" "Тесты базы знаний"
        ;;
    "notifications")
        quick_test "single" "notifications.spec.js" "Тесты уведомлений"
        ;;
    "integration")
        quick_test "single" "integration.spec.js" "Интеграционные тесты"
        ;;
    "errors")
        quick_test "single" "error-handling.spec.js" "Тесты обработки ошибок"
        ;;
    "performance")
        quick_test "single" "performance.spec.js" "Тесты производительности"
        ;;
    "security")
        quick_test "single" "security.spec.js" "Тесты безопасности"
        ;;
    "all")
        quick_test "all" "Все тесты"
        ;;
    "menu")
        echo "🚀 Быстрый запуск тестов:"
        echo ""
        echo "🎯 Группы тестов:"
        echo "  ./quick-start.sh smoke         - Smoke тесты (основные функции)"
        echo "  ./quick-start.sh critical      - Критические тесты"
        echo "  ./quick-start.sh all           - Все тесты"
        echo ""
        echo "📋 Отдельные тесты:"
        echo "  ./quick-start.sh auth          - Тесты аутентификации"
        echo "  ./quick-start.sh tickets       - Тесты заявок"
        echo "  ./quick-start.sh equipment     - Тесты оборудования"
        echo "  ./quick-start.sh rooms         - Тесты кабинетов"
        echo "  ./quick-start.sh users         - Тесты пользователей"
        echo "  ./quick-start.sh knowledge     - Тесты базы знаний"
        echo "  ./quick-start.sh notifications - Тесты уведомлений"
        echo "  ./quick-start.sh integration   - Интеграционные тесты"
        echo "  ./quick-start.sh errors        - Тесты обработки ошибок"
        echo "  ./quick-start.sh performance   - Тесты производительности"
        echo "  ./quick-start.sh security      - Тесты безопасности"
        echo ""
        echo "💡 Примеры использования:"
        echo "  ./quick-start.sh smoke         # Основные функции"
        echo "  ./quick-start.sh auth          # Только аутентификация"
        echo "  ./quick-start.sh critical      # Критические тесты"
        echo "  ./quick-start.sh all           # Все тесты"
        ;;
    *)
        echo "❌ Неизвестная команда: $1"
        echo "Используйте './quick-start.sh menu' для просмотра доступных команд"
        exit 1
        ;;
esac

echo ""
echo "✅ Тесты завершены!"
echo "📊 Отчеты доступны в директории playwright-report/"
echo "🎥 Видео и скриншоты доступны в директории test-results/"
