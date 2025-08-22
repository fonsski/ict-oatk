# Руководство по настройке почтового сервиса для ICT

Для корректной работы функций сброса пароля и уведомления об активации учетной записи необходимо настроить почтовый сервис. В этом руководстве описаны шаги для настройки почтового сервиса в Laravel 12 с использованием Sail.

## Настройки почты в .env файле

Откройте файл `.env` в корне проекта и найдите или добавьте следующие параметры:

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="ict@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

## Варианты настройки почтового сервиса

### 1. Разработка (локальная среда)

Для локальной разработки удобно использовать Mailhog, который уже включен в Laravel Sail.

```env
MAIL_MAILER=smtp
MAIL_HOST=mailhog
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="ict@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Чтобы проверить отправленные письма, перейдите в Mailhog по адресу: http://localhost:8025

### 2. Рабочая среда (production)

Для рабочей среды рекомендуется использовать реальный SMTP-сервер. Ниже приведены настройки для популярных почтовых сервисов.

#### Gmail:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-gmail-address@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS="your-gmail-address@gmail.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Примечание: Для Gmail необходимо создать пароль приложения в настройках безопасности Google.

#### Yandex:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.yandex.ru
MAIL_PORT=465
MAIL_USERNAME=your-yandex-username
MAIL_PASSWORD=your-yandex-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="your-email@yandex.ru"
MAIL_FROM_NAME="${APP_NAME}"
```

#### Mail.ru:

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mail.ru
MAIL_PORT=465
MAIL_USERNAME=your-mail-username
MAIL_PASSWORD=your-mail-password
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS="your-email@mail.ru"
MAIL_FROM_NAME="${APP_NAME}"
```

## Проверка настройки почты

После настройки почтового сервиса рекомендуется проверить его работоспособность. Для этого можно использовать Artisan-команду:

```bash
php artisan make:mail TestMail
```

Затем отредактируйте созданный класс и добавьте простой тестовый шаблон. После этого можно отправить тестовое письмо с помощью Tinker:

```bash
php artisan tinker
Mail::to('test@example.com')->send(new App\Mail\TestMail());
```

## Очереди для отправки писем

Для повышения производительности рекомендуется настроить очереди для отправки писем. Добавьте в `.env` файл:

```env
QUEUE_CONNECTION=database
```

И запустите миграцию для создания таблицы очередей:

```bash
php artisan queue:table
php artisan migrate
```

Затем запустите обработчик очередей:

```bash
php artisan queue:work
```

В production-среде рекомендуется использовать supervisor для управления очередями.

## Дополнительная информация

- Письма отправляются через классы уведомлений: `App\Notifications\PasswordResetNotification` и `App\Notifications\AccountActivationNotification`.
- При необходимости шаблоны писем можно настроить, изменив методы `toMail` в этих классах.
- Для тестирования функционала сброса пароля и активации учетной записи рекомендуется использовать локальное тестирование с Mailhog перед развертыванием в production.