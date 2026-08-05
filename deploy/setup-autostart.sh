#!/usr/bin/env bash
#
# ICT Help — автозапуск при включении виртуалки.
#
# Включает автостарт для всего, из чего состоит система:
#   nginx        — веб-сервер
#   php-fpm      — обработчик PHP
#   mysql        — база данных
#   очередь      — письма и уведомления
#   reverb       — WebSocket живых уведомлений
#   планировщик  — периодические задачи (таймер раз в минуту)
#
# После этого система поднимается сама, без входа в консоль.
#
# Запуск:  sudo bash deploy/setup-autostart.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"
PHP_BIN="${PHP_BIN:-$(command -v php || echo /usr/bin/php)}"

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Запустите с sudo: sudo bash deploy/setup-autostart.sh"
[[ -d "${APP_DIR}" ]] || die "Каталог ${APP_DIR} не найден. Укажите APP_DIR=... перед запуском"

# ------------------------------------------------------------------
log "Установка юнитов systemd"
# ------------------------------------------------------------------
for unit in ict-help-queue.service ict-help-reverb.service \
            ict-help-scheduler.service ict-help-scheduler.timer; do
    # Подставляем реальные пути и пользователя — юниты написаны под
    # /var/www/ict-help и www-data, но установка может быть в другом месте.
    sed -e "s|/var/www/ict-help|${APP_DIR}|g" \
        -e "s|/usr/bin/php|${PHP_BIN}|g" \
        -e "s|^User=www-data|User=${APP_USER}|" \
        -e "s|^Group=www-data|Group=${APP_USER}|" \
        "${SCRIPT_DIR}/systemd/${unit}" > "/etc/systemd/system/${unit}"
    echo "  ${unit}"
done

systemctl daemon-reload

# ------------------------------------------------------------------
log "Включение автозапуска базовых сервисов"
# ------------------------------------------------------------------
PHP_FPM_UNIT="$(systemctl list-unit-files --no-legend 'php*-fpm.service' \
    | awk '{print $1}' | head -n1)"

for service in mysql nginx ${PHP_FPM_UNIT}; do
    if ! systemctl cat "${service}" >/dev/null 2>&1; then
        warn "${service}: юнит не найден, пропускаю"
        continue
    fi

    if systemctl enable --now "${service}" >/dev/null 2>&1; then
        echo "  ${service} — автозапуск включён"
    else
        warn "${service}: не удалось включить, проверьте вручную"
    fi
done

# ------------------------------------------------------------------
log "Включение автозапуска сервисов ICT Help"
# ------------------------------------------------------------------
systemctl enable --now ict-help-queue.service
systemctl enable --now ict-help-reverb.service
systemctl enable --now ict-help-scheduler.timer
echo "  очередь, reverb и планировщик включены"

# ------------------------------------------------------------------
log "Текущее состояние"
# ------------------------------------------------------------------
printf '%-34s %s\n' "СЕРВИС" "СОСТОЯНИЕ"
for service in mysql nginx ${PHP_FPM_UNIT} \
               ict-help-queue.service ict-help-reverb.service ict-help-scheduler.timer; do
    [[ -n "${service}" ]] || continue
    state="$(systemctl is-active "${service}" 2>/dev/null || echo неизвестно)"
    boot="$(systemctl is-enabled "${service}" 2>/dev/null || echo -)"
    printf '%-34s %s (автозапуск: %s)\n' "${service}" "${state}" "${boot}"
done

echo
echo "Готово. Система поднимется автоматически при следующем включении виртуалки."
echo "Проверить после перезагрузки:  systemctl status ict-help-queue ict-help-reverb"
