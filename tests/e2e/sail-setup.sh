#!/bin/bash

# Скрипт для настройки тестового окружения с Laravel Sail

echo "🐳 Настройка тестового окружения с Laravel Sail..."

# Проверяем, что мы в корневой директории проекта
if [ ! -f "artisan" ]; then
    echo "❌ Ошибка: Запустите скрипт из корневой директории проекта"
    exit 1
fi

# Проверяем, что Sail установлен
if [ ! -f "./vendor/bin/sail" ]; then
    echo "❌ Ошибка: Laravel Sail не найден. Установите его командой: composer require laravel/sail"
    exit 1
fi

# Останавливаем Sail если он запущен
echo "🛑 Остановка существующих контейнеров..."
./vendor/bin/sail down

# Запускаем Sail
echo "🚀 Запуск Laravel Sail..."
./vendor/bin/sail up -d

# Ждем, пока контейнеры запустятся
echo "⏳ Ожидание запуска контейнеров..."
sleep 30

# Проверяем, что приложение доступно
echo "🔍 Проверка доступности приложения..."
max_attempts=10
attempt=1

while [ $attempt -le $max_attempts ]; do
    if curl -s http://localhost > /dev/null; then
        echo "✅ Приложение доступно на http://localhost"
        break
    else
        echo "⏳ Попытка $attempt/$max_attempts: приложение еще не готово..."
        sleep 10
        ((attempt++))
    fi
done

if [ $attempt -gt $max_attempts ]; then
    echo "❌ Приложение не стало доступным за отведенное время"
    exit 1
fi

# Запускаем миграции и сидеры
echo "🔄 Запуск миграций и сидеров..."
./vendor/bin/sail artisan migrate:fresh --seed

# Очищаем кэш
echo "🧹 Очистка кэша..."
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear

# Создаем тестовых пользователей
echo "👥 Создание тестовых пользователей..."
./vendor/bin/sail artisan tinker --execute="
use App\Models\User;
use App\Models\Role;

// Создаем роли если их нет
\$roles = [
    ['name' => 'Администратор', 'slug' => 'admin', 'description' => 'Полный доступ к системе'],
    ['name' => 'Мастер', 'slug' => 'master', 'description' => 'Управление оборудованием и заявками'],
    ['name' => 'Техник', 'slug' => 'technician', 'description' => 'Работа с заявками'],
    ['name' => 'Пользователь', 'slug' => 'user', 'description' => 'Базовый доступ']
];

foreach (\$roles as \$roleData) {
    Role::firstOrCreate(['slug' => \$roleData['slug']], \$roleData);
}

// Создаем тестовых пользователей
\$testUsers = [
    [
        'name' => 'Администратор Тест',
        'phone' => '+7 (999) 123-45-67',
        'email' => 'admin@test.com',
        'password' => bcrypt('password123'),
        'role_id' => Role::where('slug', 'admin')->first()->id,
        'is_active' => true
    ],
    [
        'name' => 'Мастер Тест',
        'phone' => '+7 (999) 123-45-68',
        'email' => 'master@test.com',
        'password' => bcrypt('password123'),
        'role_id' => Role::where('slug', 'master')->first()->id,
        'is_active' => true
    ],
    [
        'name' => 'Техник Тест',
        'phone' => '+7 (999) 123-45-69',
        'email' => 'technician@test.com',
        'password' => bcrypt('password123'),
        'role_id' => Role::where('slug', 'technician')->first()->id,
        'is_active' => true
    ],
    [
        'name' => 'Пользователь Тест',
        'phone' => '+7 (999) 123-45-70',
        'email' => 'user@test.com',
        'password' => bcrypt('password123'),
        'role_id' => Role::where('slug', 'user')->first()->id,
        'is_active' => true
    ]
];

foreach (\$testUsers as \$userData) {
    User::firstOrCreate(['email' => \$userData['email']], \$userData);
}

echo 'Тестовые пользователи созданы успешно!';
"

echo "✅ Настройка завершена!"
echo ""
echo "Для запуска тестов выполните:"
echo "cd tests/e2e"
echo "npm run test"
echo ""
echo "Для запуска с видимым браузером:"
echo "npm run test:headed"
