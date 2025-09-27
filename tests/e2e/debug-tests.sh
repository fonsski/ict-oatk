#!/bin/bash

# Скрипт для отладки тестов

set -e

echo "🐛 Отладка тестов для системы ICT..."

# Проверяем, что мы в правильной директории
if [ ! -f "package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из директории tests/e2e"
    exit 1
fi

# Функция для отладки тестов
debug_test() {
    local test_file="$1"
    local description="$2"
    
    echo ""
    echo "🔍 Отладка: $description"
    echo "📁 Файл: $test_file"
    echo "⏱️ Время: $(date)"
    echo "----------------------------------------"
    
    # Запуск с подробными логами
    DEBUG=pw:api npx playwright test "$test_file" --project=chromium --headed --debug
    
    echo "✅ Отладка завершена: $description"
    echo "⏱️ Время завершения: $(date)"
    echo "----------------------------------------"
}

# Функция для генерации кода тестов
generate_test() {
    local url="$1"
    local description="$2"
    
    echo ""
    echo "🎨 Генерация теста: $description"
    echo "🌐 URL: $url"
    echo "----------------------------------------"
    
    npx playwright codegen "$url"
    
    echo "✅ Генерация завершена: $description"
    echo "----------------------------------------"
}

# Функция для просмотра отчетов
view_reports() {
    echo ""
    echo "📊 Просмотр отчетов..."
    echo "----------------------------------------"
    
    if [ -d "playwright-report" ]; then
        echo "🌐 Открытие HTML отчета..."
        npx playwright show-report
    else
        echo "❌ Отчеты не найдены. Запустите тесты сначала."
    fi
}

# Функция для очистки результатов
clean_results() {
    echo ""
    echo "🧹 Очистка результатов тестов..."
    echo "----------------------------------------"
    
    rm -rf test-results/
    rm -rf playwright-report/
    rm -f test-results.json
    rm -f test-results.xml
    
    echo "✅ Результаты очищены"
}

# Функция для проверки системы
check_system() {
    echo ""
    echo "🔍 Проверка системы..."
    echo "----------------------------------------"
    
    # Проверяем Node.js
    echo "📦 Node.js версия: $(node --version)"
    
    # Проверяем npm
    echo "📦 npm версия: $(npm --version)"
    
    # Проверяем Playwright
    echo "🎭 Playwright версия: $(npx playwright --version)"
    
    # Проверяем доступность приложения
    if curl -s http://localhost > /dev/null; then
        echo "✅ Приложение доступно на http://localhost"
    else
        echo "❌ Приложение недоступно на http://localhost"
        echo "💡 Запустите приложение командой: ./vendor/bin/sail up -d"
    fi
    
    # Проверяем браузеры
    echo "🌐 Проверка браузеров..."
    npx playwright install --dry-run
}

# Меню отладки
case "${1:-menu}" in
    "auth")
        debug_test "auth.spec.js" "Отладка тестов аутентификации"
        ;;
    "tickets")
        debug_test "tickets.spec.js" "Отладка тестов заявок"
        ;;
    "equipment")
        debug_test "equipment.spec.js" "Отладка тестов оборудования"
        ;;
    "rooms")
        debug_test "rooms.spec.js" "Отладка тестов кабинетов"
        ;;
    "users")
        debug_test "users.spec.js" "Отладка тестов пользователей"
        ;;
    "knowledge")
        debug_test "knowledge.spec.js" "Отладка тестов базы знаний"
        ;;
    "notifications")
        debug_test "notifications.spec.js" "Отладка тестов уведомлений"
        ;;
    "integration")
        debug_test "integration.spec.js" "Отладка интеграционных тестов"
        ;;
    "errors")
        debug_test "error-handling.spec.js" "Отладка тестов обработки ошибок"
        ;;
    "performance")
        debug_test "performance.spec.js" "Отладка тестов производительности"
        ;;
    "security")
        debug_test "security.spec.js" "Отладка тестов безопасности"
        ;;
    "generate")
        generate_test "http://localhost" "Генерация тестов для главной страницы"
        ;;
    "reports")
        view_reports
        ;;
    "clean")
        clean_results
        ;;
    "check")
        check_system
        ;;
    "menu")
        echo "🐛 Меню отладки тестов:"
        echo ""
        echo "🔍 Отладка конкретных тестов:"
        echo "  ./debug-tests.sh auth          - Отладка тестов аутентификации"
        echo "  ./debug-tests.sh tickets       - Отладка тестов заявок"
        echo "  ./debug-tests.sh equipment     - Отладка тестов оборудования"
        echo "  ./debug-tests.sh rooms         - Отладка тестов кабинетов"
        echo "  ./debug-tests.sh users         - Отладка тестов пользователей"
        echo "  ./debug-tests.sh knowledge     - Отладка тестов базы знаний"
        echo "  ./debug-tests.sh notifications - Отладка тестов уведомлений"
        echo "  ./debug-tests.sh integration   - Отладка интеграционных тестов"
        echo "  ./debug-tests.sh errors        - Отладка тестов обработки ошибок"
        echo "  ./debug-tests.sh performance   - Отладка тестов производительности"
        echo "  ./debug-tests.sh security      - Отладка тестов безопасности"
        echo ""
        echo "🛠️ Инструменты:"
        echo "  ./debug-tests.sh generate     - Генерация новых тестов"
        echo "  ./debug-tests.sh reports       - Просмотр отчетов"
        echo "  ./debug-tests.sh clean         - Очистка результатов"
        echo "  ./debug-tests.sh check         - Проверка системы"
        echo ""
        echo "💡 Примеры использования:"
        echo "  ./debug-tests.sh auth          # Отладка тестов аутентификации"
        echo "  ./debug-tests.sh generate      # Генерация новых тестов"
        echo "  ./debug-tests.sh reports       # Просмотр отчетов"
        echo "  ./debug-tests.sh check         # Проверка системы"
        ;;
    *)
        echo "❌ Неизвестная команда: $1"
        echo "Используйте './debug-tests.sh menu' для просмотра доступных команд"
        exit 1
        ;;
esac

echo ""
echo "✅ Отладка завершена!"
echo "📊 Отчеты доступны в директории playwright-report/"
echo "🎥 Видео и скриншоты доступны в директории test-results/"
