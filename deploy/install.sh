#!/usr/bin/env bash
#
# ICT Help — первичная установка на чистую Ubuntu.
#
# Ставит PHP, MySQL, nginx, разворачивает проект в /var/www/ict-help,
# настраивает автозапуск через systemd.
#
# Запуск:  sudo bash deploy/install.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"
DB_NAME="${DB_NAME:-ict_help}"
DB_USER="${DB_USER:-ict_help}"
PHP_VERSION="${PHP_VERSION:-8.3}"
REPO_URL="${REPO_URL:-https://github.com/fonsski/ict-oatk.git}"

# Каталог, из которого запущен скрипт (repo/deploy) — берём отсюда конфиги.
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Запустите с sudo: sudo bash deploy/install.sh"

# ------------------------------------------------------------------
log "Установка системных пакетов"
# ------------------------------------------------------------------
export DEBIAN_FRONTEND=noninteractive
apt-get update -qq
apt-get install -y -qq software-properties-common curl git unzip

# В Ubuntu 22.04 нужного PHP может не быть в штатных репозиториях.
if ! apt-cache show "php${PHP_VERSION}" >/dev/null 2>&1; then
    log "Подключаю репозиторий ondrej/php (в системном нет PHP ${PHP_VERSION})"
    add-apt-repository -y ppa:ondrej/php
    apt-get update -qq
fi

apt-get install -y -qq \
    "php${PHP_VERSION}" "php${PHP_VERSION}-fpm" "php${PHP_VERSION}-cli" \
    "php${PHP_VERSION}-mysql" "php${PHP_VERSION}-mbstring" "php${PHP_VERSION}-xml" \
    "php${PHP_VERSION}-curl" "php${PHP_VERSION}-zip" "php${PHP_VERSION}-gd" \
    "php${PHP_VERSION}-bcmath" "php${PHP_VERSION}-intl" \
    mysql-server nginx

if ! command -v composer >/dev/null 2>&1; then
    log "Установка Composer"
    curl -fsSL https://getcomposer.org/installer -o /tmp/composer-setup.php
    php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer
    rm -f /tmp/composer-setup.php
fi

if ! command -v node >/dev/null 2>&1; then
    log "Установка Node.js 20 (нужен для сборки фронтенда)"
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash -
    apt-get install -y -qq nodejs
fi

# ------------------------------------------------------------------
log "Получение исходного кода в ${APP_DIR}"
# ------------------------------------------------------------------
if [[ -d "${APP_DIR}/.git" ]]; then
    warn "Проект уже развёрнут — обновляю. Для штатных обновлений используйте deploy/update.sh"
    git -C "${APP_DIR}" pull --ff-only
else
    mkdir -p "$(dirname "${APP_DIR}")"
    git clone "${REPO_URL}" "${APP_DIR}"
fi

cd "${APP_DIR}"

# ------------------------------------------------------------------
log "Настройка базы данных"
# ------------------------------------------------------------------
if [[ -z "${DB_PASSWORD:-}" ]]; then
    DB_PASSWORD="$(openssl rand -base64 24 | tr -d '/+=' | head -c 24)"
    GENERATED_DB_PASSWORD=1
fi

systemctl enable --now mysql

mysql <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\`
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'localhost';
FLUSH PRIVILEGES;
SQL

# ------------------------------------------------------------------
log "Файл окружения"
# ------------------------------------------------------------------
if [[ ! -f .env ]]; then
    cp "${SCRIPT_DIR}/.env.production.example" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env
    NEW_ENV=1
else
    warn ".env уже существует — оставляю как есть"
fi

# ------------------------------------------------------------------
log "Установка зависимостей и сборка фронтенда"
# ------------------------------------------------------------------
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent
npm run build

grep -q '^APP_KEY=.\+' .env || php artisan key:generate --force
grep -q '^REVERB_APP_KEY=.\+' .env || php artisan reverb:install --no-interaction || true

# ------------------------------------------------------------------
log "Права на каталоги"
# ------------------------------------------------------------------
mkdir -p storage/app/private storage/logs bootstrap/cache
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"
# Точечно: сплошной chmod по каталогу снял бы флаг запуска с бинарников
# node_modules (vite, esbuild) и сломал бы следующую сборку фронтенда.
chmod -R 775 storage bootstrap/cache
chmod 640 .env

# ------------------------------------------------------------------
log "Миграции и наполнение справочников"
# ------------------------------------------------------------------
sudo -u "${APP_USER}" php artisan migrate --force
sudo -u "${APP_USER}" php artisan storage:link || true

if [[ "${SKIP_SEED:-0}" != "1" ]]; then
    sudo -u "${APP_USER}" php artisan db:seed --class=ProductionSeeder --force
fi

# ------------------------------------------------------------------
log "Кэширование конфигурации"
# ------------------------------------------------------------------
sudo -u "${APP_USER}" php artisan config:cache
sudo -u "${APP_USER}" php artisan route:cache
sudo -u "${APP_USER}" php artisan view:cache

# ------------------------------------------------------------------
log "Настройка nginx"
# ------------------------------------------------------------------
sed "s|php8\.3-fpm\.sock|php${PHP_VERSION}-fpm.sock|; s|/var/www/ict-help|${APP_DIR}|g" \
    "${SCRIPT_DIR}/nginx/ict-help.conf" > /etc/nginx/sites-available/ict-help
ln -sf /etc/nginx/sites-available/ict-help /etc/nginx/sites-enabled/ict-help
rm -f /etc/nginx/sites-enabled/default
nginx -t
systemctl reload nginx

# ------------------------------------------------------------------
log "Автозапуск сервисов вместе с виртуалкой"
# ------------------------------------------------------------------
bash "${SCRIPT_DIR}/setup-autostart.sh"

# ------------------------------------------------------------------
log "Готово"
# ------------------------------------------------------------------
IP_ADDR="$(hostname -I | awk '{print $1}')"
echo
echo "Система развёрнута в ${APP_DIR}"
echo "Откройте: http://${IP_ADDR}"
echo

if [[ "${GENERATED_DB_PASSWORD:-0}" == "1" ]]; then
    echo "Пароль БД (сохранён в .env): ${DB_PASSWORD}"
fi

if [[ "${NEW_ENV:-0}" == "1" ]]; then
    warn "Осталось сделать вручную:"
    echo "  1. Укажите APP_URL в ${APP_DIR}/.env (сейчас заглушка)."
    echo "  2. Впишите телефоны сотрудников в STAFF_*_PHONE и выполните:"
    echo "       cd ${APP_DIR} && sudo -u ${APP_USER} php artisan db:seed --class=StaffUserSeeder --force"
    echo "     Команда покажет пароли для входа — сохраните их."
    echo "  3. После правки .env: sudo -u ${APP_USER} php artisan config:cache"
fi
