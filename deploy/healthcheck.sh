#!/usr/bin/env bash
#
# ICT Help — проверка состояния развёрнутой системы.
#
# Только читает состояние, ничего не перезапускает и не меняет — безопасно
# запускать в любое время, в том числе на живом сайте.
#
# Запуск:  sudo bash deploy/healthcheck.sh
#
# Код выхода: 0 — всё в порядке (возможны предупреждения),
#             1 — есть хотя бы одна критичная проблема.
#
set -uo pipefail

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"

OK=0
WARN=0
FAIL=0

pass() { printf '  \033[1;32m✓\033[0m %s\n' "$*"; OK=$((OK+1)); }
warn() { printf '  \033[1;33m!\033[0m %s\n' "$*"; WARN=$((WARN+1)); }
fail() { printf '  \033[1;31m✗\033[0m %s\n' "$*"; FAIL=$((FAIL+1)); }
section() { printf '\n\033[1;34m== %s ==\033[0m\n' "$*"; }

[[ -d "${APP_DIR}" ]] || { echo "Каталог ${APP_DIR} не найден. Укажите APP_DIR=... перед запуском"; exit 1; }
cd "${APP_DIR}"

# ------------------------------------------------------------------
section "Версия кода"
# ------------------------------------------------------------------
if [[ -d .git ]]; then
    COMMIT="$(git -c safe.directory="${APP_DIR}" log -1 --format='%h %s' 2>/dev/null)"
    if [[ -n "${COMMIT}" ]]; then
        pass "Текущий коммит: ${COMMIT}"
    else
        warn "Не удалось прочитать git-лог (проверьте safe.directory)"
    fi

    DIRTY="$(git -c safe.directory="${APP_DIR}" status --porcelain 2>/dev/null)"
    if [[ -n "${DIRTY}" ]]; then
        warn "В рабочем каталоге есть незакоммиченные изменения — код правили вручную мимо deploy/update.sh"
    else
        pass "Рабочий каталог чистый (без ручных правок)"
    fi

    # Сеть может быть недоступна — не считаем это критичным, только предупреждаем.
    if git -c safe.directory="${APP_DIR}" fetch --quiet 2>/dev/null; then
        BEHIND="$(git -c safe.directory="${APP_DIR}" rev-list --count HEAD..origin/main 2>/dev/null || echo '?')"
        if [[ "${BEHIND}" == "0" ]]; then
            pass "На последней версии origin/main"
        elif [[ "${BEHIND}" == "?" ]]; then
            warn "Не удалось сравнить с origin/main"
        else
            warn "Отстаёт от origin/main на ${BEHIND} коммит(ов) — выполните deploy/update.sh"
        fi
    else
        warn "Нет доступа к origin (проверка отставания от GitHub пропущена)"
    fi
else
    fail "В ${APP_DIR} нет git-репозитория"
fi

# ------------------------------------------------------------------
section "Системные сервисы"
# ------------------------------------------------------------------
PHP_FPM_UNIT="$(systemctl list-unit-files --no-legend 'php*-fpm.service' 2>/dev/null \
    | awk '{print $1}' | head -n1)"

for service in mysql nginx "${PHP_FPM_UNIT}" \
               ict-help-queue.service ict-help-reverb.service ict-help-scheduler.timer; do
    [[ -n "${service}" ]] || { warn "Юнит php-fpm не найден"; continue; }
    if ! systemctl cat "${service}" >/dev/null 2>&1; then
        warn "${service}: юнит не установлен"
        continue
    fi
    if systemctl is-active --quiet "${service}"; then
        pass "${service}: активен"
    else
        fail "${service}: НЕ активен"
    fi
done

# ------------------------------------------------------------------
section "Диск и память"
# ------------------------------------------------------------------
ROOT_USE="$(df -P / | awk 'NR==2 {gsub("%","",$5); print $5}')"
if [[ "${ROOT_USE}" -ge 95 ]]; then
    fail "Диск (/) заполнен на ${ROOT_USE}%"
elif [[ "${ROOT_USE}" -ge 85 ]]; then
    warn "Диск (/) заполнен на ${ROOT_USE}% — стоит освободить место"
else
    pass "Диск (/) занят на ${ROOT_USE}%"
fi

MEM_AVAIL_PCT="$(free | awk '/^Mem:/ {printf "%d", $7/$2*100}')"
if [[ "${MEM_AVAIL_PCT}" -lt 10 ]]; then
    warn "Свободной памяти мало: ${MEM_AVAIL_PCT}%"
else
    pass "Свободной памяти: ${MEM_AVAIL_PCT}%"
fi

# ------------------------------------------------------------------
section "Конфигурация (.env)"
# ------------------------------------------------------------------
if [[ -f .env ]]; then
    APP_ENV_VAL="$(grep -E '^APP_ENV=' .env | cut -d= -f2- || true)"
    APP_DEBUG_VAL="$(grep -E '^APP_DEBUG=' .env | cut -d= -f2- || true)"
    APP_KEY_VAL="$(grep -E '^APP_KEY=' .env | cut -d= -f2- || true)"
    LOG_CHANNEL_VAL="$(grep -E '^LOG_CHANNEL=' .env | cut -d= -f2- || true)"

    [[ "${APP_ENV_VAL}" == "production" ]] \
        && pass "APP_ENV=production" \
        || warn "APP_ENV=${APP_ENV_VAL:-<не задано>} (ожидался production)"

    [[ "${APP_DEBUG_VAL}" == "false" ]] \
        && pass "APP_DEBUG=false" \
        || fail "APP_DEBUG=${APP_DEBUG_VAL:-<не задано>} — на проде должно быть false (иначе ошибки показывают стек-трейс всем)"

    [[ -n "${APP_KEY_VAL}" ]] \
        && pass "APP_KEY задан" \
        || fail "APP_KEY пуст — сессии и шифрование не работают"

    if [[ -n "${LOG_CHANNEL_VAL}" ]]; then
        pass "LOG_CHANNEL=${LOG_CHANNEL_VAL}"
        if [[ "${LOG_CHANNEL_VAL}" == "daily" ]]; then
            echo "      (файлы вида storage/logs/laravel-ГГГГ-ММ-ДД.log, а не laravel.log)"
        fi
    else
        warn "LOG_CHANNEL не задан в .env (используется значение по умолчанию)"
    fi
else
    fail ".env не найден"
fi

# ------------------------------------------------------------------
section "Права на запись"
# ------------------------------------------------------------------
for dir in storage bootstrap/cache; do
    if [[ -d "${dir}" ]]; then
        TESTFILE="${dir}/.healthcheck-$$"
        if sudo -u "${APP_USER}" touch "${TESTFILE}" 2>/dev/null; then
            rm -f "${TESTFILE}"
            pass "${dir}/ доступен для записи пользователем ${APP_USER}"
        else
            fail "${dir}/ НЕ доступен для записи пользователем ${APP_USER} — chown -R ${APP_USER}:${APP_USER} ${dir} && chmod -R 775 ${dir}"
        fi
    else
        fail "${dir}/ не найден"
    fi
done

# ------------------------------------------------------------------
section "Журнал ошибок"
# ------------------------------------------------------------------
TODAY_LOG="storage/logs/laravel-$(date +%Y-%m-%d).log"
if [[ -f "${TODAY_LOG}" ]]; then
    ERR_COUNT="$(grep -ac "\.ERROR:" "${TODAY_LOG}" 2>/dev/null || echo 0)"
    if [[ "${ERR_COUNT}" -gt 0 ]]; then
        warn "За сегодня в ${TODAY_LOG}: ${ERR_COUNT} ошибок (production.ERROR)"
    else
        pass "За сегодня в логе ошибок не зафиксировано"
    fi
elif [[ -f storage/logs/laravel.log ]]; then
    ERR_COUNT="$(grep -ac "\.ERROR:" storage/logs/laravel.log 2>/dev/null || echo 0)"
    [[ "${ERR_COUNT}" -gt 0 ]] \
        && warn "В storage/logs/laravel.log: ${ERR_COUNT} ошибок" \
        || pass "Ошибок в логе не зафиксировано"
else
    warn "Файл журнала за сегодня не найден (storage/logs/) — либо ошибок ещё не было, либо логирование не работает"
fi

# ------------------------------------------------------------------
section "База данных"
# ------------------------------------------------------------------
if command -v php >/dev/null 2>&1; then
    if sudo -u "${APP_USER}" php artisan migrate:status >/tmp/ict-help-migrate-status.$$ 2>&1; then
        PENDING="$(grep -c 'Pending' /tmp/ict-help-migrate-status.$$ 2>/dev/null || echo 0)"
        rm -f /tmp/ict-help-migrate-status.$$
        pass "Подключение к БД работает"
        [[ "${PENDING}" -gt 0 ]] \
            && warn "Есть непрогнанные миграции (${PENDING}) — выполните php artisan migrate --force" \
            || pass "Все миграции применены"
    else
        rm -f /tmp/ict-help-migrate-status.$$
        fail "Нет подключения к БД (или artisan не отвечает)"
    fi
else
    fail "PHP не найден в PATH"
fi

# ------------------------------------------------------------------
section "Веб-сервер"
# ------------------------------------------------------------------
if command -v curl >/dev/null 2>&1; then
    HTTP_CODE="$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://127.0.0.1/ 2>/dev/null || echo 000)"
    case "${HTTP_CODE}" in
        200|301|302) pass "Главная страница отвечает (HTTP ${HTTP_CODE})" ;;
        000) fail "Сайт не отвечает на http://127.0.0.1/ (проверьте nginx/php-fpm)" ;;
        5*) fail "Главная страница отвечает ошибкой HTTP ${HTTP_CODE}" ;;
        *) warn "Главная страница вернула неожиданный код HTTP ${HTTP_CODE}" ;;
    esac
else
    warn "curl не установлен — проверка HTTP пропущена"
fi

# ------------------------------------------------------------------
section "Итог"
# ------------------------------------------------------------------
echo "  OK: ${OK}   Предупреждения: ${WARN}   Проблемы: ${FAIL}"
echo

if [[ "${FAIL}" -gt 0 ]]; then
    printf '\033[1;31mЕсть критичные проблемы — см. пункты с ✗ выше.\033[0m\n'
    exit 1
elif [[ "${WARN}" -gt 0 ]]; then
    printf '\033[1;33mВсё работает, но есть на что обратить внимание (пункты с !).\033[0m\n'
    exit 0
else
    printf '\033[1;32mВсё в порядке.\033[0m\n'
    exit 0
fi
