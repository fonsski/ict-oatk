#!/bin/bash

# Скрипт для настройки тестового окружения

echo "🚀 Настройка тестового окружения для ICT системы..."

# Проверяем, что мы в правильной директории
if [ ! -f "package.json" ]; then
    echo "❌ Ошибка: Запустите скрипт из директории tests/e2e"
    exit 1
fi

# Устанавливаем зависимости
echo "📦 Установка зависимостей..."
npm install

# Устанавливаем браузеры Playwright
echo "🌐 Установка браузеров Playwright..."
npm run test:install

# Создаем тестовую базу данных
echo "🗄️ Настройка тестовой базы данных..."
cd ../..
php artisan migrate:fresh --seed

# Создаем тестовых пользователей
echo "👥 Создание тестовых пользователей..."
php artisan tinker --execute="
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

# Очищаем кэш
echo "🧹 Очистка кэша..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Генерируем ключ приложения если его нет
if [ ! -f ".env" ]; then
    echo "⚙️ Создание файла .env..."
    cp .env.example .env
    php artisan key:generate
fi

# Запускаем миграции
echo "🔄 Запуск миграций..."
php artisan migrate

# Запускаем сидеры
echo "🌱 Запуск сидеров..."
php artisan db:seed

echo "✅ Тестовое окружение настроено успешно!"
echo ""
echo "Для запуска тестов выполните:"
echo "cd tests/e2e"
echo "npm run test"
echo ""
echo "Для запуска с видимым браузером:"
echo "npm run test:headed"
echo ""
echo "Для отладки:"
echo "npm run test:debug"
