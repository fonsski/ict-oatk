#!/usr/bin/env bash
#
# ICT Help — настройка отправки почты.
#
# Без неё не работает восстановление пароля: форма говорит «код отправлен»,
# а письмо не уходит никуда. Скрипт спрашивает почтовый ящик, прописывает
# его в .env, перезапускает то, что держит конфигурацию в памяти, и сразу
# отправляет проверочное письмо.
#
# Запуск:  sudo bash deploy/setup-mail.sh
#
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/ict-help}"
APP_USER="${APP_USER:-www-data}"
ENV_FILE="${APP_DIR}/.env"
DEFAULT_FROM="${DEFAULT_FROM:-ict@oatk.org}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
warn() { printf '\033[1;33m!  %s\033[0m\n' "$*"; }
die()  { printf '\033[1;31mОШИБКА: %s\033[0m\n' "$*" >&2; exit 1; }

[[ $EUID -eq 0 ]] || die "Запустите с sudo: sudo bash deploy/setup-mail.sh"
[[ -f "${ENV_FILE}" ]] || die "Нет ${ENV_FILE} — сначала выполните install.sh"
[[ -t 0 ]] || die "Скрипту нужен ввод с клавиатуры — запускайте его в терминале"

# ------------------------------------------------------------------
# Правка .env
# ------------------------------------------------------------------
# Значение подставляем через окружение, а не через -v: awk разбирает в
# присваиваниях escape-последовательности и съел бы обратный слэш в пароле.
set_env() {
    local key="$1" value="$2" tmp
    tmp="$(mktemp)"

    ENV_KEY="${key}" ENV_VALUE="${value}" awk '
        BEGIN { key = ENVIRON["ENV_KEY"]; value = ENVIRON["ENV_VALUE"]; done = 0 }
        $0 ~ "^[[:space:]]*#?[[:space:]]*" key "=" {
            if (!done) { print key "=" value; done = 1 }
            next
        }
        { print }
        END { if (!done) print key "=" value }
    ' "${ENV_FILE}" > "${tmp}"

    # Пишем через перенаправление, чтобы сохранить владельца и права .env.
    cat "${tmp}" > "${ENV_FILE}"
    rm -f "${tmp}"
}

# В .env значение берём в одинарные кавычки: в двойных phpdotenv
# подставляет переменные, и пароль с ${...} или $ превратился бы во что-то
# другое. Внутри одинарных кавычек не экранируется ничего — поэтому
# одинарную кавычку в пароле придётся исключить.
quote() {
    printf "'%s'" "$1"
}

require_no_quote() {
    [[ "$1" != *"'"* ]] || die "В пароле есть одинарная кавычка — .env её не переживёт. Смените пароль в почте либо впишите MAIL_PASSWORD в ${ENV_FILE} вручную"
}

ask() {
    local prompt="$1" default="${2:-}" answer
    if [[ -n "${default}" ]]; then
        read -r -p "${prompt} [${default}]: " answer
        printf '%s' "${answer:-${default}}"
    else
        read -r -p "${prompt}: " answer
        printf '%s' "${answer}"
    fi
}

# ------------------------------------------------------------------
log "Откуда система будет отправлять письма"
# ------------------------------------------------------------------
cat <<'MENU'

  1) Google Workspace / Gmail        smtp.gmail.com:587, пароль приложения
  2) Google Workspace, ретранслятор  smtp-relay.gmail.com:587, по IP сервера
  3) Яндекс 360                      smtp.yandex.ru:465
  4) Mail.ru для бизнеса             smtp.mail.ru:465
  5) Другой SMTP-сервер              адрес укажу вручную
  6) Почтовый сервер колледжа        через локальный Postfix, без пароля

  Вариант 2 — когда пароль приложения создать не дают: администратор
  домена разрешает отправку с IP-адреса сервера, и пароль не нужен вовсе.

MENU

CHOICE="$(ask "Ваш вариант" "1")"

MAIL_HOST=""; MAIL_PORT=""; MAIL_SCHEME="smtp"
MAIL_USERNAME=""; MAIL_PASSWORD=""
APP_PASSWORD_HINT=""
NEEDS_AUTH=1

case "${CHOICE}" in
    1)
        MAIL_HOST="smtp.gmail.com"; MAIL_PORT="587"; MAIL_SCHEME="smtp"
        APP_PASSWORD_HINT="Google не принимает обычный пароль от ящика. Нужен пароль приложения: https://myaccount.google.com/apppasswords — 16 букв. Страница откроется, только если у ящика включена двухэтапная проверка и администратор домена не запретил пароли приложений. Если пишет «настройка недоступна» — берите вариант 2."
        ;;
    2)
        MAIL_HOST="smtp-relay.gmail.com"; MAIL_PORT="587"; MAIL_SCHEME="smtp"
        NEEDS_AUTH=0
        cat <<'RELAY'

  Ретранслятор пускает по адресу отправителя, а не по паролю. В консоли
  администратора Google Workspace нужно завести службу ретрансляции SMTP
  и внести туда внешний IP-адрес, с которого выходит этот сервер.
  Отправитель должен быть в домене организации.

RELAY
        ;;
    3)
        MAIL_HOST="smtp.yandex.ru"; MAIL_PORT="465"; MAIL_SCHEME="smtps"
        APP_PASSWORD_HINT="Яндекс требует пароль приложения (Почта → Пароли приложений) и включённый доступ по SMTP в настройках ящика."
        ;;
    4)
        MAIL_HOST="smtp.mail.ru"; MAIL_PORT="465"; MAIL_SCHEME="smtps"
        APP_PASSWORD_HINT="Mail.ru требует пароль для внешнего приложения — он создаётся в настройках ящика, обычный пароль не подойдёт."
        ;;
    5)
        MAIL_HOST="$(ask "Адрес SMTP-сервера")"
        [[ -n "${MAIL_HOST}" ]] || die "Адрес сервера обязателен"
        MAIL_PORT="$(ask "Порт" "587")"
        if [[ "${MAIL_PORT}" == "465" ]]; then
            MAIL_SCHEME="smtps"
        else
            MAIL_SCHEME="smtp"
        fi
        # Внутренние серверы часто пускают по IP-адресу отправителя.
        AUTH_ANSWER="$(ask "Сервер спрашивает логин и пароль? (д/н)" "д")"
        [[ "${AUTH_ANSWER}" =~ ^[дДyY] ]] || NEEDS_AUTH=0
        ;;
    6)
        MAIL_HOST="127.0.0.1"; MAIL_PORT="25"; MAIL_SCHEME="smtp"
        NEEDS_AUTH=0
        ;;
    *)
        die "Нет такого варианта: ${CHOICE}"
        ;;
esac

FROM_ADDRESS="$(ask "Адрес, от которого приходят письма" "${DEFAULT_FROM}")"
[[ "${FROM_ADDRESS}" == *@*.* ]] || die "«${FROM_ADDRESS}» не похож на адрес почты"

# ------------------------------------------------------------------
if [[ "${CHOICE}" == "6" ]]; then
    log "Локальный Postfix"
    # ------------------------------------------------------------------
    RELAY_HOST="$(ask "Через какой сервер колледжа отправлять (relayhost)")"
    [[ -n "${RELAY_HOST}" ]] || die "Адрес почтового сервера обязателен"
    MAIL_NAME="$(ask "Имя этого сервера в письмах (mailname)" "$(hostname -f 2>/dev/null || hostname)")"

    if ! command -v postfix >/dev/null 2>&1; then
        export DEBIAN_FRONTEND=noninteractive
        debconf-set-selections <<< "postfix postfix/main_mailer_type select Satellite system"
        debconf-set-selections <<< "postfix postfix/mailname string ${MAIL_NAME}"
        debconf-set-selections <<< "postfix postfix/relayhost string ${RELAY_HOST}"
        apt-get update -qq
        apt-get install -y -qq postfix
    fi

    postconf -e "relayhost = ${RELAY_HOST}"
    postconf -e "myhostname = ${MAIL_NAME}"
    # Postfix нужен только приложению на этой же машине — снаружи он
    # слушать не должен, иначе получится открытый релей.
    postconf -e "inet_interfaces = loopback-only"

    systemctl enable --now postfix
    systemctl restart postfix
    echo "  Postfix отправляет через ${RELAY_HOST}"
fi

if [[ "${NEEDS_AUTH}" == "1" ]]; then
    # ------------------------------------------------------------------
    log "Учётная запись почты"
    # ------------------------------------------------------------------
    [[ -z "${APP_PASSWORD_HINT}" ]] || { echo; echo "  ${APP_PASSWORD_HINT}"; echo; }

    MAIL_USERNAME="$(ask "Логин (обычно это сам адрес ящика)" "${FROM_ADDRESS}")"
    [[ -n "${MAIL_USERNAME}" ]] || die "Логин обязателен"

    # Пароль не передаётся аргументом и не печатается: аргументы видны в
    # выводе ps любому пользователю сервера и оседают в истории оболочки.
    read -rs -p "Пароль (при вводе не отображается): " MAIL_PASSWORD
    echo
    [[ -n "${MAIL_PASSWORD}" ]] || die "Пароль обязателен"
    require_no_quote "${MAIL_PASSWORD}"
fi

FROM_NAME="$(ask "Имя отправителя" "ICT Help")"

# ------------------------------------------------------------------
log "Запись настроек"
# ------------------------------------------------------------------
BACKUP="${ENV_FILE}.bak-$(date +%Y%m%d-%H%M%S)"
cp -a "${ENV_FILE}" "${BACKUP}"
chmod 600 "${BACKUP}"
echo "  копия прежнего .env: ${BACKUP}"

set_env MAIL_MAILER "smtp"
set_env MAIL_HOST "${MAIL_HOST}"
set_env MAIL_PORT "${MAIL_PORT}"
set_env MAIL_SCHEME "${MAIL_SCHEME}"
set_env MAIL_USERNAME "$(quote "${MAIL_USERNAME}")"
set_env MAIL_PASSWORD "$(quote "${MAIL_PASSWORD}")"
set_env MAIL_FROM_ADDRESS "$(quote "${FROM_ADDRESS}")"
set_env MAIL_FROM_NAME "$(quote "${FROM_NAME}")"

echo "  ${MAIL_HOST}:${MAIL_PORT}, отправитель ${FROM_ADDRESS}"

# ------------------------------------------------------------------
log "Перечитывание конфигурации"
# ------------------------------------------------------------------
cd "${APP_DIR}"
sudo -u "${APP_USER}" php artisan config:cache

# Рабочий процесс очереди поднимает конфигурацию один раз при старте.
# Без перезапуска он продолжит ходить на прежний SMTP, и письма будут
# падать уже после того, как проверка прямой отправкой скажет «всё хорошо».
if systemctl list-unit-files ict-help-queue.service >/dev/null 2>&1; then
    systemctl restart ict-help-queue.service
    echo "  очередь перезапущена"
else
    warn "Служба ict-help-queue не найдена — настройте автозапуск: bash deploy/setup-autostart.sh"
fi

# ------------------------------------------------------------------
log "Проверка"
# ------------------------------------------------------------------
TEST_TO="$(ask "Куда отправить проверочное письмо" "${FROM_ADDRESS}")"

if ! sudo -u "${APP_USER}" php artisan mail:test "${TEST_TO}"; then
    echo
    die "Почта пока не работает. Настройки записаны в .env — поправьте их и запустите скрипт снова (прежний файл лежит в ${BACKUP})"
fi

# Настоящие письма идут через очередь, поэтому прямой отправки мало.
sudo -u "${APP_USER}" php artisan mail:test "${TEST_TO}" --queued

sleep 5
if sudo -u "${APP_USER}" php artisan queue:failed 2>/dev/null | grep -q "TestMessage"; then
    warn "Письмо из очереди не ушло. Смотрите причину:"
    warn "  sudo -u ${APP_USER} php artisan queue:failed"
    warn "  tail -50 ${APP_DIR}/storage/logs/queue.log"
    exit 1
fi

log "Почта настроена"
echo
echo "На ${TEST_TO} ушли два письма — прямое и через очередь."
echo "Если пришли оба, восстановление пароля работает."
