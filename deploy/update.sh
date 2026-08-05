#!/usr/bin/env bash
#
# ICT Help — обновление уже развёрнутой системы.
#
# Забирает новый код, ставит зависимости, накатывает миграции,
# пересобирает кэш и перезапускает фоновые сервисы.
#
# Запуск:  sudo bash deploy/update.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Запустите с sudo: sudo bash deploy/update.sh"
[[ -d "${APP_DIR}/.git" ]] || die "В ${APP_DIR} нет git-репозитория — сначала выполните install.sh"

cd "${APP_DIR}"

# ------------------------------------------------------------------
log "Резервная копия базы"
# ------------------------------------------------------------------
BACKUP_DIR="${APP_DIR}/storage/backups"
mkdir -p "${BACKUP_DIR}"
DB_NAME="$(grep -E '^DB_DATABASE=' .env | cut -d= -f2-)"
DB_USER="$(grep -E '^DB_USERNAME=' .env | cut -d= -f2-)"
DB_PASS="$(grep -E '^DB_PASSWORD=' .env | cut -d= -f2-)"
BACKUP_FILE="${BACKUP_DIR}/${DB_NAME}-$(date +%Y%m%d-%H%M%S).sql.gz"

if mysqldump --user="${DB_USER}" --password="${DB_PASS}" \
        --single-transaction --quick "${DB_NAME}" 2>/dev/null | gzip > "${BACKUP_FILE}"; then
    echo "  ${BACKUP_FILE}"
    # Держим только 10 последних копий, чтобы не забить диск виртуалки.
    ls -1t "${BACKUP_DIR}"/*.sql.gz 2>/dev/null | tail -n +11 | xargs -r rm --
else
    warn "Не удалось снять дамп — обновление продолжается, но отката не будет"
fi

# ------------------------------------------------------------------
log "Режим обслуживания"
# ------------------------------------------------------------------
sudo -u "${APP_USER}" php artisan down --retry=60 || true
# Что бы дальше ни случилось — сайт не должен остаться закрытым.
trap 'sudo -u "${APP_USER}" php artisan up || true' EXIT

# ------------------------------------------------------------------
log "Получение нового кода"
# ------------------------------------------------------------------
git fetch --all --quiet
git reset --hard origin/main
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"

# ------------------------------------------------------------------
log "Зависимости и сборка фронтенда"
# ------------------------------------------------------------------
composer install --no-dev --optimize-autoloader --no-interaction
npm ci --silent
npm run build

# ------------------------------------------------------------------
log "Миграции"
# ------------------------------------------------------------------
sudo -u "${APP_USER}" php artisan migrate --force

# ------------------------------------------------------------------
log "Пересборка кэша"
# ------------------------------------------------------------------
sudo -u "${APP_USER}" php artisan config:cache
sudo -u "${APP_USER}" php artisan route:cache
sudo -u "${APP_USER}" php artisan view:cache

chown -R "${APP_USER}:${APP_USER}" storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ------------------------------------------------------------------
log "Перезапуск сервисов"
# ------------------------------------------------------------------
systemctl restart ict-help-queue.service
systemctl restart ict-help-reverb.service
systemctl reload nginx
echo "  очередь, reverb, nginx"

log "Обновление завершено"
