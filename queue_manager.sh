#!/bin/bash

# Скрипт для управления очередями Laravel
# Используется для запуска, остановки и мониторинга очередей

# Директория проекта
PROJECT_DIR="$(dirname "$(realpath "$0")")"
cd "$PROJECT_DIR"

# Цвета для вывода
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функция для вывода сообщений
log() {
  echo -e "${BLUE}[$(date +'%Y-%m-%d %H:%M:%S')]${NC} $1"
}

# Проверка статуса очереди
check_queue_status() {
  WORKER_COUNT=$(ps aux | grep 'artisan queue:work' | grep -v grep | wc -l)

  if [ $WORKER_COUNT -gt 0 ]; then
    echo -e "${GREEN}Активно обработчиков очереди: $WORKER_COUNT${NC}"
    ps aux | grep 'artisan queue:work' | grep -v grep
  else
    echo -e "${RED}Обработчики очереди не запущены${NC}"
  fi

  # Проверяем количество заданий в очереди
  JOBS_COUNT=$(php artisan queue:size)
  echo -e "${BLUE}Заданий в очереди: $JOBS_COUNT${NC}"
}

# Запуск обработчика очереди
start_queue() {
  log "Запуск обработчика очереди..."
  nohup php artisan queue:work database --sleep=3 --tries=3 --max-time=3600 > storage/logs/queue.log 2>&1 &

  if [ $? -eq 0 ]; then
    log "${GREEN}Обработчик очереди успешно запущен${NC}"
  else
    log "${RED}Ошибка при запуске обработчика очереди${NC}"
    exit 1
  fi
}

# Остановка обработчика очереди
stop_queue() {
  log "Остановка обработчиков очереди..."
  pkill -f 'artisan queue:work'

  if [ $? -eq 0 ]; then
    log "${GREEN}Обработчики очереди остановлены${NC}"
  else
    log "${YELLOW}Не найдено активных обработчиков очереди${NC}"
  fi
}

# Перезапуск обработчика очереди
restart_queue() {
  stop_queue
  sleep 2
  start_queue
}

# Вывод списка заданий в очереди
list_jobs() {
  log "Список заданий в очереди:"
  php artisan queue:list
}

# Проверка логов обработчика очереди
check_logs() {
  if [ -f "storage/logs/queue.log" ]; then
    log "Последние 20 строк из логов обработчика очереди:"
    tail -n 20 storage/logs/queue.log
  else
    log "${RED}Файл логов не найден${NC}"
  fi

  # Проверяем ошибки в Laravel логе
  if [ -f "storage/logs/laravel.log" ]; then
    log "Проверка ошибок в Laravel логе:"
    grep -i "error\|exception" storage/logs/laravel.log | tail -n 10
  fi
}

# Справка
show_help() {
  echo -e "${BLUE}Управление очередями Laravel${NC}"
  echo ""
  echo "Использование: $0 [команда]"
  echo ""
  echo "Команды:"
  echo "  start    - Запустить обработчик очереди"
  echo "  stop     - Остановить обработчики очереди"
  echo "  restart  - Перезапустить обработчики очереди"
  echo "  status   - Проверить статус очереди"
  echo "  list     - Показать задания в очереди"
  echo "  logs     - Показать логи обработчика очереди"
  echo "  test     - Отправить тестовое email сообщение в очередь"
  echo "  help     - Показать эту справку"
  echo ""
}

# Отправка тестового сообщения
send_test_email() {
  read -p "Введите email для тестового сообщения: " EMAIL

  if [ -z "$EMAIL" ]; then
    log "${RED}Email не указан${NC}"
    exit 1
  fi

  log "Отправка тестового email на $EMAIL..."
  php artisan test:email-notifications --email="$EMAIL"
}

# Проверка прав на выполнение
if [ "$EUID" -ne 0 ]; then
  log "${YELLOW}Предупреждение: Скрипт запущен без прав администратора${NC}"
  log "${YELLOW}Некоторые операции могут быть недоступны${NC}"
fi

# Обработка параметров командной строки
case "$1" in
  start)
    start_queue
    ;;
  stop)
    stop_queue
    ;;
  restart)
    restart_queue
    ;;
  status)
    check_queue_status
    ;;
  list)
    list_jobs
    ;;
  logs)
    check_logs
    ;;
  test)
    send_test_email
    ;;
  help|--help|-h)
    show_help
    ;;
  *)
    if [ -z "$1" ]; then
      show_help
    else
      log "${RED}Неизвестная команда: $1${NC}"
      show_help
      exit 1
    fi
    ;;
esac

exit 0
