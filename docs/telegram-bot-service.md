# Инструкция по запуску Telegram бота как службы

Эта инструкция описывает, как запустить Telegram бота как системную службу, чтобы он работал в фоновом режиме и автоматически перезапускался при необходимости.

## 1. Использование Supervisor

Supervisor - это система управления процессами для Linux, которая позволяет контролировать и автоматически перезапускать процессы.

### Установка Supervisor

```bash
sudo apt-get update
sudo apt-get install supervisor
```

### Создание конфигурации для бота

Создайте конфигурационный файл в директории `/etc/supervisor/conf.d/`:

```bash
sudo nano /etc/supervisor/conf.d/telegram-bot.conf
```

Добавьте следующее содержимое (отредактируйте пути в соответствии с вашей установкой):

```ini
[program:telegram-bot]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/your/project/artisan telegram:standalone
directory=/path/to/your/project
autostart=true
autorestart=true
user=www-data
numprocs=1
redirect_stderr=true
stdout_logfile=/path/to/your/project/storage/logs/telegram-bot.log
stopwaitsecs=3600
```

### Перезапуск Supervisor

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start telegram-bot:*
```

### Проверка статуса

```bash
sudo supervisorctl status telegram-bot:*
```

## 2. Использование Systemd (альтернативный способ)

Systemd является стандартной системой инициализации в большинстве современных дистрибутивов Linux.

### Создание службы

Создайте файл службы:

```bash
sudo nano /etc/systemd/system/telegram-bot.service
```

Добавьте следующее содержимое:

```ini
[Unit]
Description=Laravel Telegram Bot
After=network.target

[Service]
User=www-data
Group=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php artisan telegram:standalone
Restart=always
RestartSec=3

[Install]
WantedBy=multi-user.target
```

### Запуск службы

```bash
sudo systemctl daemon-reload
sudo systemctl enable telegram-bot.service
sudo systemctl start telegram-bot.service
```

### Проверка статуса

```bash
sudo systemctl status telegram-bot.service
```

## 3. Использование с Docker и Laravel Sail

Если вы используете Laravel Sail, вы можете добавить сервис для бота в ваш `docker-compose.yml`:

```yaml
# Добавьте этот блок в существующий файл docker-compose.yml
telegram-bot:
  image: ${COMPOSE_PROJECT_NAME}_laravel.test
  depends_on:
    - laravel.test
  environment:
    - CONTAINER_ROLE=telegram-bot
  command: php artisan telegram:standalone
  restart: unless-stopped
  volumes:
    - '.:/var/www/html'
```

Затем создайте скрипт запуска в `/etc/entrypoint.sh`:

```bash
#!/bin/sh

if [ "$CONTAINER_ROLE" = "telegram-bot" ]; then
    echo "Running Telegram Bot..."
    exec php /var/www/html/artisan telegram:standalone
else
    # Запуск стандартного entrypoint
    exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
fi
```

Запустите сервис:

```bash
./vendor/bin/sail down
./vendor/bin/sail up -d
```

## 4. Рекомендации по обслуживанию

### Мониторинг логов

Регулярно проверяйте логи бота:

```bash
# Для Supervisor
tail -f /path/to/your/project/storage/logs/telegram-bot.log

# Для Systemd
journalctl -u telegram-bot.service -f
```

### Обновление бота

При обновлении кода бота, перезапустите службу:

```bash
# Для Supervisor
sudo supervisorctl restart telegram-bot:*

# Для Systemd
sudo systemctl restart telegram-bot.service

# Для Docker/Sail
./vendor/bin/sail restart
```

### Настройка оповещений о сбоях

Вы можете настроить оповещения о сбоях службы, используя инструменты мониторинга, такие как Monit, Nagios или Prometheus.

## Заключение

После настройки службы ваш Telegram бот будет работать непрерывно, автоматически проверять наличие новых заявок каждые 15 секунд и отправлять уведомления пользователям.