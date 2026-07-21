<p align="center"><img src="public/images/logo-full.png" width="400" alt="ICT Logo"></p>

# Система управления ИТ-инфраструктурой (ICT)

## 📑 Содержание

- [О проекте](#о-проекте)
- [Функциональные возможности](#функциональные-возможности)
  - [Система заявок](#система-заявок)
  - [Управление оборудованием](#управление-оборудованием)
  - [База знаний](#база-знаний)
  - [Управление аудиториями](#управление-аудиториями)
  - [Управление пользователями](#управление-пользователями)
  - [Дополнительные функции](#дополнительные-функции)
- [Технический стек](#технический-стек)
  - [Backend](#backend)
  - [Frontend](#frontend)
  - [Инфраструктура](#инфраструктура)
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
  - [Заявки](#заявки)
  - [Оборудование](#оборудование)
  - [Уведомления](#уведомления)
  - [Аутентификация](#аутентификация)
- [Схема базы данных](#схема-базы-данных)
- [Контрибьюция](#контрибьюция)
- [Дополнительная документация](#дополнительная-документация)
- [Часто задаваемые вопросы](#часто-задаваемые-вопросы)
  - [Как сбросить пароль пользователя?](#как-сбросить-пароль-пользователя)
  - [Как настроить отправку уведомлений по email?](#как-настроить-отправку-уведомлений-по-email)
  - [Как добавить новую роль пользователя?](#как-добавить-новую-роль-пользователя)
  - [Как обновить систему?](#как-обновить-систему)
  - [Как подключить SSL сертификат для HTTPS?](#как-подключить-ssl-сертификат-для-https)
  - [Как настроить авторизацию через Google?](#как-настроить-авторизацию-через-google)
  - [Как настроить отправку сообщений через очереди?](#как-настроить-отправку-сообщений-через-очереди)
- [Безопасность](#безопасность)
- [История изменений](#история-изменений)
- [Лицензия](#лицензия)

## 🌐 О проекте

Система управления ИТ-инфраструктурой (ICT) — это веб-приложение, разработанное для эффективного управления ИТ-активами, обработки заявок пользователей, ведения базы знаний и управления аудиториями/оборудованием. Система предназначена для использования ИТ-отделами образовательных учреждений, предприятий и организаций.

Основные цели проекта:

- Централизованное управление ИТ-инфраструктурой организации
- Автоматизация процесса обработки заявок пользователей
- Учет и отслеживание ИТ-оборудования
- Создание и поддержка базы знаний для пользователей и ИТ-специалистов
- Управление аудиториями и расположенным в них оборудованием
- Оптимизация работы ИТ-отдела и повышение качества обслуживания

## 🛠 Функциональные возможности

### 🎫 Система заявок

- Многоуровневая система создания и отслеживания заявок
- Автоматическое назначение заявок на основе категории и нагрузки специалистов
- Настраиваемые статусы и приоритеты заявок
- Коммуникация между заявителем и исполнителем через комментарии
- История изменений по каждой заявке
- Прикрепление файлов и скриншотов к заявкам
- Уведомления по электронной почте и в системе

### 🖥 Управление оборудованием

- Инвентаризация компьютеров, принтеров, сетевого оборудования и др.
- Учет гарантийного обслуживания и контрактов на поддержку
- История перемещений и сервисного обслуживания
- Связь оборудования с аудиториями и заявками
- Управление категориями оборудования и статусами

### 📚 База знаний

- Структурированный каталог статей по решению типовых проблем
- Форматирование с использованием Markdown
- Загрузка изображений и вложений
- Поиск по содержимому статей
- Управление категориями статей
- Публичный и приватный доступ к различным статьям

### 🏫 Управление аудиториями

- Каталог аудиторий с подробной информацией
- Привязка оборудования к аудиториям
- История изменений оборудования в аудитории
- Создание заявок, связанных с конкретной аудиторией

### 👥 Управление пользователями

- Ролевая модель доступа (администратор, техник, пользователь)
- Управление профилями пользователей
- Авторизация через Google или стандартную форму
- Настраиваемые уведомления

### 📊 Дополнительные функции

- Панель мониторинга с ключевыми показателями
- Генерация отчетов и экспорт данных
- Страница с часто задаваемыми вопросами
- Адаптивный интерфейс для мобильных устройств
- Система уведомлений (веб, email)

## 💻 Технический стек

### Backend

- [PHP 8.2](https://www.php.net/) - Основной язык программирования
- [Laravel 12](https://laravel.com/) - PHP фреймворк
- [MySQL 8.0](https://www.mysql.com/) - Система управления базами данных
- [Laravel Sanctum](https://laravel.com/docs/sanctum) - Аутентификация API
- [Laravel Socialite](https://laravel.com/docs/socialite) - Интеграция с OAuth провайдерами
- [Parsedown](https://github.com/erusev/parsedown) - Парсер Markdown для базы знаний
- [Google API Client](https://github.com/googleapis/google-api-php-client) - Клиент для интеграции с Google

### Frontend

- [Blade](https://laravel.com/docs/blade) - Шаблонизатор Laravel
- [Tailwind CSS 3](https://tailwindcss.com/) - Utility-first CSS фреймворк
- [Alpine.js](https://alpinejs.dev/) - Минималистичный JavaScript фреймворк
- [Vite](https://vitejs.dev/) - Сборщик фронтенда

### Инфраструктура

- [Docker](https://www.docker.com/) - Контейнеризация (через Laravel Sail)
- [Laravel Sail](https://laravel.com/docs/sail) - Среда разработки на Docker
- [Nginx](https://www.nginx.com/) - Веб-сервер для production
- [Supervisor](http://supervisord.org/) - Управление процессами для очередей

## 📋 Требования

### Для разработки (с использованием Docker и Laravel Sail)

- Docker Engine и Docker Compose
- Git
- Composer (опционально, можно использовать через Docker)
- Node.js и npm (опционально, можно использовать через Docker)

### Для production-окружения

- PHP 8.2+
- Composer
- MySQL 8.0+
- Node.js 18+ и NPM
- Nginx или Apache
- Supervisor (для управления очередями)
- Git

## 🚀 Установка и настройка

### Разработка с Laravel Sail

Laravel Sail предоставляет легкую среду разработки на Docker, включающую PHP, MySQL и другие службы.

#### 1. Клонирование репозитория

```bash
git clone https://github.com/your-username/ict.git
cd ict
```

#### 2. Установка PHP-зависимостей с Docker

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php82-composer:latest \
    composer install --ignore-platform-reqs
```

#### 3. Настройка переменных окружения

```bash
cp .env.example .env
```

Отредактируйте файл `.env` и настройте следующие параметры:

```
APP_NAME="ICT"
APP_URL=http://localhost

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

Приложение будет доступно по адресу http://localhost.

### Настройка production-окружения

Эти инструкции помогут настроить систему на production-сервере.

#### 1. Подготовка сервера

Установите необходимые пакеты:

```bash
sudo apt update
sudo apt install -y php8.2 php8.2-fpm php8.2-mysql php8.2-cli php8.2-common php8.2-mbstring php8.2-xml php8.2-zip php8.2-gd php8.2-curl unzip nginx mysql-server supervisor git
```

#### 2. Клонирование репозитория

```bash
git clone https://github.com/your-username/ict.git /var/www/ict
cd /var/www/ict
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

Отредактируйте файл `.env` и настройте параметры подключения к базе данных и другие необходимые настройки.

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ict
DB_USERNAME=ict_user
DB_PASSWORD=your_password
```

#### 5. Настройка базы данных

```bash
mysql -u root -p
```

```sql
CREATE DATABASE ict;
CREATE USER 'ict_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON ict.* TO 'ict_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 6. Настройка прав доступа

```bash
sudo chown -R www-data:www-data /var/www/ict
sudo chown -R www-data:www-data /var/www/ict/storage
sudo chown -R www-data:www-data /var/www/ict/bootstrap/cache
```

#### 7. Настройка веб-сервера

Создайте файл конфигурации Nginx:

```bash
sudo nano /etc/nginx/sites-available/ict
```

Содержимое файла:

```nginx
server {
    listen 80;
    listen [::]:80;
    server_name your-domain.com;
    root /var/www/ict/public;

    add_header X-Frame-Options "SAMEORIGIN";
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

Активируйте сайт и перезапустите Nginx:

```bash
sudo ln -s /etc/nginx/sites-available/ict /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

#### 8. Настройка Sail для production (опционально)

Если вы хотите использовать Docker в production, создайте файл `.env.sail` с нужными настройками:

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=ict
DB_USERNAME=sail
DB_PASSWORD=password
```

И запустите контейнеры:

```bash
./vendor/bin/sail --env=.env.sail up -d
```

Настройка Supervisor для очередей:

```bash
sudo nano /etc/supervisor/conf.d/laravel-worker.conf
```

```
[program:laravel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/ict/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/ict/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start laravel-worker:*
```

### Настройка файла .env

Базовая конфигурация `.env` файла:

```
# Основные настройки приложения
APP_NAME="ICT"
APP_ENV=production  # local для разработки
APP_KEY=           # Генерируется автоматически (php artisan key:generate)
APP_DEBUG=false    # true для разработки
APP_URL=https://your-domain.com

# Настройки базы данных
DB_CONNECTION=mysql
DB_HOST=127.0.0.1  # mysql при использовании Sail
DB_PORT=3306
DB_DATABASE=ict
DB_USERNAME=ict_user
DB_PASSWORD=your_password

# Настройки почты
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=your-email@example.com
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
MAIL_FROM_NAME="${APP_NAME}"

# Настройки OAuth для Google
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"

# Настройки очередей
QUEUE_CONNECTION=database  # redis также поддерживается
```

### Настройка очередей

Очереди используются для отправки email-уведомлений и других асинхронных задач.

#### Использование базы данных для очередей

1. Убедитесь, что в файле `.env` установлено:
```
QUEUE_CONNECTION=database
```

2. Выполните миграции для создания таблицы очередей:
```bash
php artisan queue:table
php artisan migrate
```

3. Запустите обработчик очередей:
   
   Для разработки:
   ```bash
   php artisan queue:work
   ```
   
   Для production с Supervisor:
   ```
   [program:laravel-worker]
   process_name=%(program_name)s_%(process_num)02d
   command=php /var/www/ict/artisan queue:work --sleep=3 --tries=3 --max-time=3600
   autostart=true
   autorestart=true
   user=www-data
   numprocs=2
   redirect_stderr=true
   stdout_logfile=/var/www/ict/storage/logs/worker.log
   ```

4. Включите автозапуск в Supervisor:
   ```bash
   sudo supervisorctl reread
   sudo supervisorctl update
   sudo supervisorctl start laravel-worker:*
   ```

5. Использование скрипта `queue_manager.sh` для управления очередями (альтернатива Supervisor):
   ```bash
   chmod +x queue_manager.sh
   ./queue_manager.sh start
   ```

   Команды скрипта:
   - `start` - запуск обработчика очередей
   - `stop` - остановка обработчика
   - `restart` - перезапуск обработчика
   - `status` - проверка статуса

## ⚙️ Работа с проектом

### Запуск в режиме разработки

Для удобства разработки можно использовать команду `composer dev`, которая запускает несколько процессов одновременно:

```bash
composer dev
```

Эта команда запускает:
- Laravel сервер разработки
- Обработчик очередей
- Вывод логов в реальном времени через Laravel Pail
- Vite для автоматической компиляции фронтенда при изменении файлов

Вы также можете запустить эти компоненты отдельно:

```bash
# Сервер разработки Laravel
php artisan serve

# Обработчик очередей
php artisan queue:work

# Вывод логов через Laravel Pail
php artisan pail

# Сборщик фронтенда с hot-reload
npm run dev
```

При использовании Laravel Sail:

```bash
# Запуск всех служб
./vendor/bin/sail up -d

# Сервер разработки уже запущен в контейнере
# Запуск обработчика очередей
./vendor/bin/sail artisan queue:work

# Сборщик фронтенда
./vendor/bin/sail npm run dev
```

### Полезные команды для разработки

```bash
# Создание контроллера
php artisan make:controller NameController --resource

# Создание модели с миграцией и фабрикой
php artisan make:model Name -mf

# Применение миграций
php artisan migrate

# Откат миграций
php artisan migrate:rollback

# Заполнение базы тестовыми данными
php artisan db:seed

# Очистка кэша
php artisan cache:clear
php artisan view:clear
php artisan config:clear
php artisan route:clear

# Создание тестов
php artisan make:test NameTest

# Запуск тестов
php artisan test

# Запуск отдельного теста
php artisan test --filter=NameTest

# Очистка базы данных и применение миграций заново
php artisan migrate:fresh --seed
```

### Учетные записи по умолчанию

После запуска сидов в системе создаются следующие учетные записи:

**Вход выполняется по номеру телефона, а не по email.**

| Роль          | Телефон         | Пароль     | Доступ                                   |
|---------------|-----------------|------------|------------------------------------------|
| Администратор | `+79953940601`  | `admin123` | Полный доступ ко всем функциям системы   |
| Мастер        | `+79000000002`  | `password` | Заявки, оборудование, пользователи       |
| Техник        | `+79000000003`  | `password` | Управление заявками и оборудованием      |
| Пользователь  | `+79000000004`  | `password` | Создание заявок и просмотр базы знаний   |

В production-среде рекомендуется сразу изменить пароли этих учетных записей.

> Для быстрого локального запуска на Windows без Docker/MySQL см. [LOCAL_SETUP.md](LOCAL_SETUP.md).

## 📂 Структура проекта

```
ICT/
├── app/                    # Основной код приложения
│   ├── Console/           # Консольные команды
│   ├── Http/              # HTTP компоненты (контроллеры, middleware)
│   │   ├── Controllers/   # Контроллеры
│   │   │   └── Api/       # API контроллеры
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
├── docs/                  # Документация проекта
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

Система предоставляет REST API для интеграции с другими приложениями.

### Заявки

- `GET /api/tickets` - Получить список заявок
  - Параметры: `status`, `priority`, `assigned_to`, `page`, `per_page`
- `GET /api/tickets/{id}` - Получить информацию о заявке
- `POST /api/tickets` - Создать новую заявку
  - Обязательные поля: `title`, `description`, `category_id`
  - Необязательные поля: `priority_id`, `room_id`, `equipment_id`, `attachments[]`
- `PUT /api/tickets/{id}` - Обновить заявку
- `DELETE /api/tickets/{id}` - Удалить заявку
- `POST /api/tickets/{id}/comments` - Добавить комментарий к заявке
- `GET /api/tickets/{id}/history` - Получить историю изменений заявки

### Оборудование

- `GET /api/equipment` - Получить список оборудования
  - Параметры: `category`, `status`, `room_id`, `search`, `page`, `per_page`
- `GET /api/equipment/{id}` - Получить информацию об оборудовании
- `GET /api/equipment/by-room/{roomId}` - Получить оборудование в аудитории
- `GET /api/equipment/categories` - Получить список категорий оборудования
- `GET /api/equipment/statuses` - Получить список статусов оборудования

### Уведомления

- `GET /api/notifications` - Получить уведомления пользователя
- `GET /api/notifications/unread-count` - Получить количество непрочитанных уведомлений
- `POST /api/notifications/mark-as-read/{id}` - Отметить уведомление как прочитанное
- `POST /api/notifications/mark-all-as-read` - Отметить все уведомления как прочитанные

### Аутентификация

Для аутентификации в API используется Laravel Sanctum. Все запросы должны содержать заголовок `Authorization: Bearer {token}`.

Получение токена:
```
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "password"
}
```

Ответ:
```json
{
    "user": {
        "id": 1,
        "name": "User Name",
        "email": "user@example.com",
        "role": "user"
    },
    "token": "1|laravel_sanctum_token..."
}
```

## 📊 Схема базы данных

Основные таблицы и связи:

- `users` - Пользователи системы
  - Связи: `tickets`, `ticket_comments`, `roles`

- `roles` - Роли пользователей
  - Связи: `users`

- `tickets` - Заявки пользователей
  - Связи: `users`, `ticket_comments`, `ticket_statuses`, `ticket_priorities`, `rooms`, `equipment`

- `ticket_comments` - Комментарии к заявкам
  - Связи: `tickets`, `users`

- `equipment` - Оборудование
  - Связи: `equipment_categories`, `equipment_statuses`, `rooms`, `equipment_service_histories`, `equipment_location_histories`

- `rooms` - Аудитории
  - Связи: `equipment`, `tickets`

- `knowledge_bases` - База знаний
  - Связи: `knowledge_categories`, `knowledge_images`

Полную схему базы данных можно увидеть в миграциях в директории `database/migrations/`.

## 🤝 Контрибьюция

Мы приветствуем вклад в развитие проекта! Если вы хотите принять участие в разработке:

1. Форкните репозиторий
2. Создайте ветку для вашей функции (`git checkout -b feature/amazing-feature`)
3. Закоммитьте изменения (`git commit -m 'Add some amazing feature'`)
4. Отправьте ветку (`git push origin feature/amazing-feature`)
5. Откройте Pull Request

Перед отправкой PR убедитесь, что:
- Код соответствует стилю кодирования Laravel
- Все тесты проходят успешно
- Добавлены новые тесты для новой функциональности
- Обновлена документация, если необходимо

## 📖 Дополнительная документация

- [Документация Laravel](https://laravel.com/docs)
- [Документация Tailwind CSS](https://tailwindcss.com/docs)
- [Руководство пользователя](docs/user-manual.md)
- [Руководство администратора](docs/admin-guide.md)
- [Руководство разработчика](docs/developer-guide.md)
- [API документация](docs/api.md)

Дополнительная документация по системе находится в каталоге `docs/`.

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

1. Добавьте запись в таблицу `roles` через миграцию или напрямую через базу данных:

```php
// В миграции
Schema::table('roles', function (Blueprint $table) {
    // Добавление новой роли
    DB::table('roles')->insert([
        'name' => 'Новая роль',
        'slug' => 'new-role',
        'description' => 'Описание новой роли',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});
```

2. Обновите проверки прав доступа в middleware и политиках:

```php
// В App\Http\Middleware\CheckRole.php
public function handle(Request $request, Closure $next, $role)
{
    if (!$request->user()->hasRole($role)) {
        return redirect()->route('home');
    }
    
    return $next($request);
}
```

3. При необходимости обновите страницы в административной панели для управления доступом.

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

    # Рекомендуемые настройки безопасности
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256:ECDHE-ECDSA-AES256-GCM-SHA384:ECDHE-RSA-AES256-GCM-SHA384:ECDHE-ECDSA-CHACHA20-POLY1305:ECDHE-RSA-CHACHA20-POLY1305;

    # Остальные настройки как в http блоке
    root /var/www/ict/public;
    # ...
}

# Перенаправление с HTTP на HTTPS
server {
    listen 80;
    server_name ваш-домен.ru;
    return 301 https://$host$request_uri;
}
```

### Как настроить авторизацию через Google?

1. Создайте проект в [Google Cloud Console](https://console.cloud.google.com/)
2. Настройте OAuth credentials и получите Client ID и Client Secret
3. Добавьте авторизованный URI перенаправления: `https://your-domain.com/auth/google/callback`
4. Добавьте настройки в файл `.env`:
   ```
   GOOGLE_CLIENT_ID=your-client-id
   GOOGLE_CLIENT_SECRET=your-client-secret
   GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
   ```
5. Убедитесь, что провайдер настроен в `config/services.php`:
   ```php
   'google' => [
       'client_id' => env('GOOGLE_CLIENT_ID'),
       'client_secret' => env('GOOGLE_CLIENT_SECRET'),
       'redirect' => env('GOOGLE_REDIRECT_URI'),
   ],
   ```

### Как настроить отправку сообщений через очереди?

1. Убедитесь, что настроено соединение с очередями в `.env`:
   ```
   QUEUE_CONNECTION=database
   ```

2. Используйте очереди для отправки уведомлений:
   ```php
   // В коде
   Notification::route('mail', $user->email)
       ->notify((new TicketCreatedNotification($ticket))
       ->onQueue('notifications'));
   ```

3. Запустите обработчик для конкретной очереди:
   ```bash
   php artisan queue:work --queue=notifications
   ```

## 🔒 Безопасность

Система включает следующие меры безопасности:

- Защита от CSRF-атак
- Проверка прав доступа на основе ролей
- Хеширование паролей с использованием bcrypt
- Защита от XSS-атак с использованием HTMLPurifier
- Проверка всех входящих данных
- Использование подготовленных запросов для предотвращения SQL-инъекций
- Аутентификация API с использованием Laravel Sanctum

Если вы обнаружили уязвимость в системе, пожалуйста, сообщите об этом администраторам.

## 📋 История изменений

### v1.0.0 (2023-01-15)
- Первый релиз системы
- Базовая функциональность для управления заявками
- Управление оборудованием и аудиториями

### v1.1.0 (2023-04-20)
- Добавлена база знаний
- Улучшена система уведомлений
- Расширены API-возможности

### v1.2.0 (2023-08-10)
- Интеграция с Google для авторизации
- Обновлен дизайн интерфейса
- Добавлены дополнительные отчеты
- Улучшена производительность системы

### v1.3.0 (2024-01-25)
- Обновление до Laravel 12
- Улучшена система поиска
- Добавлены мобильные уведомления
- Оптимизация базы данных

## 📝 Лицензия

Система ICT — программное обеспечение с открытым исходным кодом, лицензированное под [MIT license](https://opensource.org/licenses/MIT).

Copyright &copy; 2023-2024

Данная лицензия разрешает лицам, получившим копию данного программного обеспечения, безвозмездно использовать, копировать, изменять, объединять, публиковать, распространять, сублицензировать и/или продавать копии данного программного обеспечения при соблюдении следующих условий:

Указанное выше уведомление об авторском праве и данное уведомление о разрешении должны быть включены во все копии или значимые части данного программного обеспечения.

ДАННОЕ ПРОГРАММНОЕ ОБЕСПЕЧЕНИЕ ПРЕДОСТАВЛЯЕТСЯ «КАК ЕСТЬ», БЕЗ КАКИХ-ЛИБО ГАРАНТИЙ, ЯВНО ВЫРАЖЕННЫХ ИЛИ ПОДРАЗУМЕВАЕМЫХ.