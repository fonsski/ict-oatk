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

# Скрипт обновляет в том числе сам себя: git reset ниже перезаписывает этот
# файл, а bash дочитывает его с диска по ходу выполнения — так можно
# получить обрывок команды из новой версии. Поэтому сразу продолжаем работу
# с копии во временном каталоге.
if [[ "${ICT_HELP_SELF_COPY:-0}" != "1" ]]; then
    SELF_COPY="$(mktemp)"
    cp "${BASH_SOURCE[0]}" "${SELF_COPY}"
    # Убрать копию поручаем самой копии: exec подменяет процесс целиком, и
    # trap на выходе здесь уже не отработает.
    ICT_HELP_SELF_COPY=1 ICT_HELP_SELF_COPY_PATH="${SELF_COPY}" \
        exec bash "${SELF_COPY}" "$@"
fi

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

# Всё, что нужно сделать на выходе, — в одном месте: обработчик EXIT
# бывает только один, и второй trap просто вытеснил бы первый.
MAINTENANCE_ON=0

cleanup() {
    if [[ "${MAINTENANCE_ON}" == "1" ]]; then
        # Что бы ни случилось выше, сайт не должен остаться закрытым.
        sudo -u "${APP_USER}" php artisan up || true
    fi

    if [[ -n "${ICT_HELP_SELF_COPY_PATH:-}" ]]; then
        rm -f "${ICT_HELP_SELF_COPY_PATH}"
    fi
}

trap cleanup EXIT

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
log "Права на storage"
# ------------------------------------------------------------------
# Строго до artisan down: режим обслуживания — это файл, который создаёт
# сам ${APP_USER} в storage/framework. Если права съехали (например, код
# обновляли вручную из-под своей учётной записи), artisan down молча не
# сработает, и обновление пойдёт на живом сайте.
chown -R "${APP_USER}:${APP_USER}" storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# ------------------------------------------------------------------
log "Режим обслуживания"
# ------------------------------------------------------------------
if sudo -u "${APP_USER}" php artisan down --retry=60; then
    MAINTENANCE_ON=1
else
    warn "Не удалось включить режим обслуживания — обновление идёт на работающем сайте"
fi

# ------------------------------------------------------------------
log "Получение нового кода"
# ------------------------------------------------------------------
# Каталог принадлежит ${APP_USER}, а git здесь работает от root и чужой
# репозиторий трогать отказывается. Разрешаем прямо в вызове: полагаться
# на записанную настройку нельзя — она уходит в домашний каталог, а какой
# он под sudo, зависит от настроек самого sudo.
GIT=(git -c "safe.directory=${APP_DIR}")

"${GIT[@]}" fetch --all --quiet
"${GIT[@]}" reset --hard origin/main

# То же разрешение, но записанное: composer запускает git сам, ключ ему не
# передать. Если записать не выйдет — потеряем только тишину в выводе.
if ! git config --global --get-all safe.directory 2>/dev/null | grep -qxF "${APP_DIR}"; then
    git config --global --add safe.directory "${APP_DIR}" \
        || warn "Не удалось записать safe.directory — composer будет ругаться на владельца каталога"
fi
chown -R "${APP_USER}:${APP_USER}" "${APP_DIR}"

# ------------------------------------------------------------------
log "Зависимости и сборка фронтенда"
# ------------------------------------------------------------------
# Composer знает, что работает от root, и на всякий случай выключает
# плагины — в том числе те, что раскладывают файлы пакетов Laravel.
# Здесь это осознанно: весь скрипт и так идёт под sudo.
COMPOSER_ALLOW_SUPERUSER=1 composer install \
    --no-dev --optimize-autoloader --no-interaction
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
# Без этого systemd ругается, что описания служб на диске новее его
# собственных, и перезапускает их по устаревшим настройкам.
systemctl daemon-reload
systemctl restart ict-help-queue.service
systemctl restart ict-help-reverb.service
systemctl reload nginx
echo "  очередь, reverb, nginx"

log "Обновление завершено"
