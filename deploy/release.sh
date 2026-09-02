#!/usr/bin/env bash
#
# ICT Help — выкат в production одной командой.
#
# Запускается на рабочей машине: проверяет, что выкатывать есть что и что
# код уже в origin, затем по SSH выполняет deploy/update.sh на сервере и
# убеждается, что система поднялась.
#
# Запуск:
#   bash deploy/release.sh
#   SERVER=sysadmin@192.168.0.170 bash deploy/release.sh
#
set -euo pipefail

SERVER="${SERVER:-sysadmin@helpdesk.oatk.local}"
APP_DIR="${APP_DIR:-/var/www/ict-help}"
BRANCH="${BRANCH:-main}"
HEALTH_URL="${HEALTH_URL:-}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

# ------------------------------------------------------------------
log "Проверки перед выкатом"
# ------------------------------------------------------------------
command -v git >/dev/null || die "git не найден"
git rev-parse --git-dir >/dev/null 2>&1 || die "Запускайте из каталога проекта"

CURRENT_BRANCH="$(git rev-parse --abbrev-ref HEAD)"
[[ "${CURRENT_BRANCH}" == "${BRANCH}" ]] \
    || die "Сейчас ветка ${CURRENT_BRANCH}, а выкатывается ${BRANCH}"

# Незакоммиченное на сервер не попадёт — лучше сказать об этом сразу,
# чем выкатить не то, что видно в редакторе.
[[ -z "$(git status --porcelain)" ]] \
    || die "Есть незакоммиченные изменения. Закоммитьте или спрячьте их (git stash)"

git fetch origin --quiet
LOCAL="$(git rev-parse @)"
REMOTE="$(git rev-parse "origin/${BRANCH}")"

[[ "${LOCAL}" == "${REMOTE}" ]] \
    || die "Локальная ветка расходится с origin/${BRANCH}. Сначала git push"

echo "  ветка ${BRANCH}, коммит $(git rev-parse --short HEAD)"
echo "  $(git log -1 --format=%s)"

# ------------------------------------------------------------------
log "Связь с сервером ${SERVER}"
# ------------------------------------------------------------------
ssh -o ConnectTimeout=10 -o BatchMode=yes "${SERVER}" "test -d '${APP_DIR}/.git'" \
    || die "Не удалось подключиться или в ${APP_DIR} нет репозитория. Проверьте SSH-доступ и APP_DIR"

DEPLOYED_BEFORE="$(ssh "${SERVER}" "git -C '${APP_DIR}' rev-parse --short HEAD")"
echo "  сейчас на сервере: ${DEPLOYED_BEFORE}"

if [[ "${DEPLOYED_BEFORE}" == "$(git rev-parse --short HEAD)" ]]; then
    warn "На сервере уже этот коммит — выкатывать нечего"
    exit 0
fi

echo
echo "Будет выкачено на ${SERVER}:"
git --no-pager log --oneline "${DEPLOYED_BEFORE}..HEAD" 2>/dev/null | sed 's/^/  /' \
    || echo "  (история сервера недоступна локально)"

echo
read -r -p "Продолжить? [y/N] " answer
[[ "${answer}" =~ ^[Yy]$ ]] || { echo "Отменено"; exit 0; }

# ------------------------------------------------------------------
log "Обновление на сервере"
# ------------------------------------------------------------------
# update.sh сам снимает дамп базы, включает режим обслуживания, накатывает
# миграции, пересобирает кэш и перезапускает сервисы.
ssh -t "${SERVER}" "sudo APP_DIR='${APP_DIR}' bash '${APP_DIR}/deploy/update.sh'"

# ------------------------------------------------------------------
log "Проверка после выката"
# ------------------------------------------------------------------
DEPLOYED_AFTER="$(ssh "${SERVER}" "git -C '${APP_DIR}' rev-parse --short HEAD")"
echo "  на сервере теперь: ${DEPLOYED_AFTER}"

[[ "${DEPLOYED_AFTER}" == "$(git rev-parse --short HEAD)" ]] \
    || die "Сервер остался на ${DEPLOYED_AFTER} — обновление не доехало"

for unit in ict-help-queue ict-help-reverb; do
    state="$(ssh "${SERVER}" "systemctl is-active ${unit}" 2>/dev/null || echo неизвестно)"
    printf '  %-20s %s\n' "${unit}" "${state}"
    [[ "${state}" == "active" ]] || warn "${unit} не запущен, проверьте: journalctl -u ${unit} -n 50"
done

# Адрес для проверки берём из APP_URL сервера, если не задан явно.
if [[ -z "${HEALTH_URL}" ]]; then
    HEALTH_URL="$(ssh "${SERVER}" "grep -E '^APP_URL=' '${APP_DIR}/.env' | cut -d= -f2-" || true)"
fi

if [[ -n "${HEALTH_URL}" ]]; then
    code="$(curl -s -o /dev/null -w '%{http_code}' --max-time 15 "${HEALTH_URL}" || echo 000)"
    printf '  %-20s HTTP %s\n' "${HEALTH_URL}" "${code}"

    if [[ "${code}" != "200" ]]; then
        warn "Сайт ответил ${code}. Смотрите журнал:"
        warn "  ssh ${SERVER} 'ls ${APP_DIR}/storage/logs/'   # имя файла зависит от LOG_CHANNEL"
        exit 1
    fi
else
    warn "APP_URL не задан в .env сервера — проверьте доступность вручную"
fi

# ------------------------------------------------------------------
log "Полная проверка состояния сервера"
# ------------------------------------------------------------------
# Не завершает выкат ошибкой сама по себе (ключевые проверки — выше и уже
# сработали как die/exit) — это дополнительный отчёт: диск, память, права,
# .env, БД. Отдельный от того, что уже проверено, чтобы не дублировать.
ssh -t "${SERVER}" "sudo APP_DIR='${APP_DIR}' bash '${APP_DIR}/deploy/healthcheck.sh'" \
    || warn "healthcheck.sh нашёл проблемы — см. вывод выше"

log "Выкат завершён"
