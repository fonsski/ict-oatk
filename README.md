<p align="center"><img src="public/images/logo-full.png" width="400" alt="ICT Logo"></p>

# Система управления ИТ-инфраструктурой (ICT)

## 📑 Содержание

- [О проекте](#о-проекте)
- [Функциональные возможности](#функциональные-возможности)
- [Технический стек](#технический-стек)
- [Требования](#требования)
- [Установка и настройка](#установка-и-настройка)
  - [Разработка с Laravel Sail](#разработка-с-laravel-sail)
  - [Настройка production-окружения](#настройка-production-окружения)
  - [Настройка файла .env](#настройка-файла-env)
  - [Настройка очередей](#настройка-очередей)
- [Работа с проектом](#работа-с-проектом)
  - [Запуск в режиме разработки](#запуск-в-режиме-разработки)
  - [Полезные команды для разработки](#полезные-команды-для-разработки)
  - [Учетные записи по умолчанию](#учетные-записи-по-умолчанию)
- [Структура проекта](#структура-проекта)
- [API](#api)
- [Дополнительная документация](#дополнительная-документация)
- [Часто задаваемые вопросы](#часто-задаваемые-вопросы)
- [Лицензия](#лицензия)

## 🌐 О проекте

Система управления ИТ-инфраструктурой (ICT) — это веб-приложение, разработанное для эффективного управления ИТ-активами, обработки заявок пользователей, ведения базы знаний и управления аудиториями/оборудованием. Система предназначена для использования ИТ-отделами образовательных учреждений, предприятий и организаций.

Основные цели проекта:
- Автоматизация процесса обработки заявок от пользователей
- Ведение учёта оборудования и ресурсов ИТ-инфраструктуры
- Создание и поддержка базы знаний для пользователей и технических специалистов
- Обеспечение удобного интерфейса для работы ИТ-специалистов
- Предоставление информационной поддержки пользователям

## 🛠 Функциональные возможности

### 🎫 Система заявок
- Создание, редактирование и отслеживание заявок
- Назначение заявок техническим специалистам
- Приоритизация заявок (низкий, средний, высокий, срочный)
- Статусы заявок (открыта, в работе, решена, закрыта)
- Комментарии и история заявок
- Уведомления о статусе заявок

### 🖥 Управление оборудованием
- Учёт компьютеров, принтеров и другого оборудования
- Ведение истории обслуживания оборудования
- Привязка оборудования к аудиториям и местоположениям
- Отслеживание статуса оборудования

### 📚 База знаний
- Создание и редактирование статей в базе знаний
- Категоризация статей
- Поддержка Markdown для форматирования
- Загрузка и прикрепление изображений
- Поиск по базе знаний

### 🏫 Управление аудиториями
- Учёт аудиторий и помещений
- Привязка оборудования к аудиториям
- Информация о расположении аудиторий

### 👥 Управление пользователями
- Роли пользователей (администратор, мастер, техник, пользователь)
- Управление правами доступа
- Профили пользователей

### 📊 Дополнительные функции
- FAQ на главной странице
- Уведомления о важных событиях
- Статистика по заявкам и оборудованию
- Адаптивный дизайн для работы на всех устройствах

## 💻 Технический стек

### Backend
- **PHP 8.2+**
- **Laravel 12.x** - PHP фреймворк
- **MySQL 8.0** - система управления базами данных

### Frontend
- **Tailwind CSS** - утилитарный CSS-фреймворк
- **JavaScript** - клиентский скрипт
- **Blade** - шаблонизатор Laravel

### Инфраструктура
- **Docker** с Laravel Sail для разработки
- **Composer** для управления PHP-зависимостями
- **npm** для управления JavaScript-зависимостями
- **Git** для контроля версий

## 📋 Требования

- PHP 8.2 или выше
- Composer 2.0 или выше
- MySQL 8.0 или выше
- Node.js 16.0 или выше
- npm 8.0 или выше
- Docker и Docker Compose (для разработки с Sail)

## 🚀 Установка и настройка

### Разработка с Laravel Sail

Laravel Sail — это легкий интерфейс командной строки для взаимодействия с конфигурацией Docker по умолчанию в Laravel. Он позволяет быстро запустить проект в изолированной среде без необходимости устанавливать PHP, Composer, MySQL и другие зависимости на вашей локальной машине.

#### 1. Клонирование репозитория

```bash
git clone [ссылка-на-репозиторий]
cd ICT
```

#### 2. Установка PHP-зависимостей с Docker

Если у вас еще не установлен Composer на локальной машине, вы можете использовать официальный образ Docker Composer:

```bash
docker run --rm -v $(pwd):/app -w /app composer install
```

#### 3. Настройка переменных окружения

```bash
cp .env.example .env
```

Отредактируйте файл `.env` с вашими настройками, особенно обратите внимание на настройки базы данных:

```
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ict
DB_USERNAME=sail
DB_PASSWORD=password
```

#### 4. Запуск Docker-контейнеров

```bash
./vendor/bin/sail up -d
```

#### 5. Генерация ключа приложения

```bash
./vendor/bin/sail artisan key:generate
```

#### 6. Выполнение миграций и заполнение базы данными

```bash
./vendor/bin/sail artisan migrate --seed
```

#### 7. Установка JavaScript-зависимостей и сборка фронтенда

```bash
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
```

#### 8. Доступ к приложению

После выполнения всех шагов приложение будет доступно по адресу http://localhost

### Настройка production-окружения

Для развертывания проекта в production-среде следуйте этой инструкции:

#### 1. Подготовка сервера

Убедитесь, что на сервере установлены все необходимые компоненты:
- PHP 8.2+ с расширениями: BCMath, Ctype, Fileinfo, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML
- Composer
- MySQL 8.0+
- Node.js и npm
- Nginx или Apache
- Git

#### 2. Клонирование репозитория

```bash
git clone [ссылка-на-репозиторий] /path/to/ict
cd /path/to/ict
```

#### 3. Установка зависимостей

```bash
composer install --optimize-autoloader --no-dev
npm install
npm run build
```

#### 4. Настройка переменных окружения

```bash
cp .env.example .env
php artisan key:generate
```

Отредактируйте файл `.env` с настройками production-среды:
- Установите `APP_ENV=production`
- Установите `APP_DEBUG=false`
- Настройте подключение к базе данных
- Настройте параметры SMTP для отправки писем

#### 5. Настройка базы данных

```bash
php artisan migrate --force
php artisan db:seed --force
```

#### 6. Настройка прав доступа

```bash
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
```

#### 7. Настройка веб-сервера

Для Nginx создайте конфигурационный файл:

```nginx
server {
    listen 80;
    server_name ваш-домен.ru;
    root /path/to/ict/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-XSS-Protection "1; mode=block";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### 8. Настройка Sail для production (опционально)

Если вы хотите использовать Sail в production-среде:

1. Убедитесь, что Docker и Docker Compose установлены на сервере
2. Скопируйте файлы Docker-конфигурации и запустите контейнеры:

```bash
cp .env.example .env
# Настройте .env файл для production
./vendor/bin/sail up -d
```

3. Настройте Nginx на проксирование запросов к Docker-контейнеру:

```nginx
server {
    listen 80;
    server_name ваш-домен.ru;

    location / {
        proxy_pass http://localhost:80;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

### Настройка файла .env

Основные параметры, которые следует настроить в файле .env:

```
APP_NAME=ICT
APP_ENV=production  # или local для разработки
APP_DEBUG=false     # true для разработки
APP_URL=http://ваш-домен.ru

DB_CONNECTION=mysql
DB_HOST=127.0.0.1   # или mysql при использовании Sail
DB_PORT=3306
DB_DATABASE=ict
DB_USERNAME=ваш_пользователь_бд
DB_PASSWORD=ваш_пароль_бд

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ваш_email@gmail.com
MAIL_PASSWORD=ваш_пароль
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ваш-домен.ru
MAIL_FROM_NAME="${APP_NAME}"

QUEUE_CONNECTION=database
```

### Настройка очередей

Для работы с очередями настройте Supervisor, чтобы процессы обработки очередей работали в фоновом режиме:

1. Установите Supervisor:

```bash
apt-get install supervisor
```

2. Создайте конфигурационный файл `/etc/supervisor/conf.d/laravel-worker.conf`:

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/ict/artisan queue:work database --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/ict/storage/logs/worker.log
stopwaitsecs=3600
```

3. Обновите Supervisor:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start laravel-worker:*
```

## ⚙️ Работа с проектом

### Запуск в режиме разработки

1. Запустите Docker-контейнеры:

```bash
./vendor/bin/sail up -d
```

2. Запустите Vite для отслеживания изменений фронтенда:

```bash
./vendor/bin/sail npm run dev
```

3. В отдельном терминале можно запустить очереди:

```bash
./vendor/bin/sail artisan queue:work
```

### Полезные команды для разработки

```bash
# Запуск тестов
./vendor/bin/sail artisan test

# Очистка кэша
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan view:clear
./vendor/bin/sail artisan route:clear

# Создание миграции
./vendor/bin/sail artisan make:migration create_new_table

# Создание модели с миграцией и контроллером
./vendor/bin/sail artisan make:model NewModel -mcr

# Просмотр логов
./vendor/bin/sail logs

# Доступ к MySQL
./vendor/bin/sail mysql

# Доступ к PHP интерпретатору
./vendor/bin/sail php -v

# Доступ к Composer
./vendor/bin/sail composer require package/name

# Остановка всех контейнеров
./vendor/bin/sail down
```

### Учетные записи по умолчанию

После запуска `php artisan db:seed` в системе будут созданы следующие учетные записи:

- **Администратор**: admin@example.com / password
- **Мастер**: master@example.com / password
- **Техник**: tech@example.com / password
- **Пользователь**: user@example.com / password

## 📂 Структура проекта

```
ICT/
├── app/                    # Основной код приложения
│   ├── Console/           # Консольные команды
│   ├── Http/              # HTTP компоненты (контроллеры, middleware)
│   │   ├── Controllers/   # Контроллеры
│   │   └── Middleware/    # Middleware
│   ├── Models/            # Модели данных
│   ├── Notifications/     # Уведомления
│   ├── Providers/         # Service Providers
│   ├── Repositories/      # Репозитории для работы с данными
│   ├── Services/          # Сервисные классы
│   └── Traits/            # Трейты для многократного использования
├── bootstrap/             # Файлы загрузки фреймворка
├── config/                # Конфигурационные файлы
├── database/              # Миграции, фабрики и сиды
│   ├── factories/         # Фабрики для тестирования
│   ├── migrations/        # Миграции базы данных
│   └── seeders/           # Сиды для наполнения базы
├── public/                # Публично доступные файлы
│   ├── css/               # Скомпилированные CSS
│   ├── js/                # Скомпилированный JavaScript
│   └── images/            # Изображения
├── resources/             # Исходники для фронтенда
│   ├── css/               # CSS/SASS файлы
│   ├── js/                # JavaScript файлы
│   └── views/             # Blade шаблоны
├── routes/                # Определение маршрутов
│   ├── api.php            # API маршруты
│   └── web.php            # Веб-маршруты
├── storage/               # Хранилище (логи, кэш, загруженные файлы)
├── tests/                 # Тесты
└── vendor/                # Зависимости Composer
```

## 🔄 API

Система предоставляет API для интеграции с другими приложениями:

### Заявки

- `GET /api/tickets` - Получить список заявок
- `GET /api/tickets/{id}` - Получить информацию о заявке
- `POST /api/tickets` - Создать новую заявку
- `PUT /api/tickets/{id}` - Обновить заявку
- `DELETE /api/tickets/{id}` - Удалить заявку

### Оборудование

- `GET /api/equipment` - Получить список оборудования
- `GET /api/equipment/{id}` - Получить информацию об оборудовании
- `GET /api/equipment/by-room/{roomId}` - Получить оборудование в аудитории

### Уведомления

- `GET /api/notifications` - Получить уведомления пользователя
- `GET /api/notifications/unread-count` - Получить количество непрочитанных уведомлений
- `POST /api/notifications/mark-as-read/{id}` - Отметить уведомление как прочитанное
- `POST /api/notifications/mark-all-as-read` - Отметить все уведомления как прочитанные

Для аутентификации в API используется Laravel Sanctum. Все запросы должны содержать заголовок `Authorization: Bearer {token}`.

## 📖 Дополнительная документация

Дополнительная документация доступна в следующих файлах:

- [FAQ по проекту](README_FAQ.md) - Ответы на часто задаваемые вопросы
- [Интеграция аудиторий](ROOM_INTEGRATION.md) - Документация по работе с аудиториями
- [Настройка категорий знаний](KNOWLEDGE_CATEGORIES_SETUP.md) - Руководство по настройке базы знаний
- [Улучшения системы заявок](TICKETS_IMPROVEMENT.md) - Документация по работе с системой заявок
- [Итоги реализации FAQ](IMPLEMENTATION_SUMMARY.md) - Информация о реализации системы FAQ

## ❓ Часто задаваемые вопросы

### Как сбросить пароль пользователя?

```bash
# При использовании Sail
./vendor/bin/sail artisan tinker

# В обычной среде
php artisan tinker
```

```php
$user = \App\Models\User::where('email', 'user@example.com')->first();
$user->password = Hash::make('new_password');
$user->save();
```

### Как настроить отправку уведомлений по email?

Настройте параметры SMTP в файле .env и перезапустите сервер:

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=ваш_email@gmail.com
MAIL_PASSWORD=ваш_пароль
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@ваш-домен.ru
MAIL_FROM_NAME="${APP_NAME}"
```

После этого выполните:
```bash
php artisan config:clear
php artisan cache:clear
```

### Как добавить новую роль пользователя?

1. Добавьте запись в таблицу `roles` через миграцию или напрямую через базу данных
2. Обновите метод `hasRole` в модели `User`
3. Добавьте проверки прав доступа в соответствующие контроллеры и middleware

### Как обновить систему?

```bash
# Получение последних изменений из репозитория
git pull

# Обновление зависимостей
composer install --optimize-autoloader
npm install
npm run build

# Применение миграций
php artisan migrate

# Очистка кэша
php artisan cache:clear
php artisan config:clear
php artisan view:clear
php artisan route:clear

# Перезапуск очередей (если используется Supervisor)
supervisorctl restart laravel-worker:*
```

### Как подключить SSL сертификат для HTTPS?

Для Nginx добавьте следующие настройки в конфигурацию сервера:

```nginx
server {
    listen 443 ssl;
    server_name ваш-домен.ru;

    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;

    # Остальные настройки как в http блоке
    root /path/to/ict/public;
    # ...
}

# Перенаправление с HTTP на HTTPS
server {
    listen 80;
    server_name ваш-домен.ru;
    return 301 https://$host$request_uri;
}
```

## 📝 Лицензия

Система ICT — программное обеспечение с открытым исходным кодом, лицензированное под [MIT license](https://opensource.org/licenses/MIT).