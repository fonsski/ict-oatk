# 🐳 Sail + WebSocket - Быстрый старт

## 🚀 Запуск всех сервисов одной командой

```bash
# Запустить Laravel + WebSocket + Telegram Bot
./sail-with-websocket.sh start
```

## ✅ Проверка работы

```bash
# Проверить здоровье всех сервисов
./sail-with-websocket.sh health
```

## 🔍 Что запускается автоматически:

- ✅ **Laravel приложение** → http://localhost
- ✅ **WebSocket сервер** → http://localhost:8080  
- ✅ **Telegram Bot** → фоновый процесс
- ✅ **MySQL** → localhost:3306

## 🛠️ Полезные команды:

```bash
# Перезапустить только WebSocket
./sail-with-websocket.sh websocket

# Показать логи WebSocket
./sail-with-websocket.sh logs

# Остановить все
./sail-with-websocket.sh stop

# Статус контейнеров
./sail-with-websocket.sh status
```

## 🎯 Результат:

**WebSocket сервер теперь запускается автоматически с Sail! 🚀**

Больше не нужно запускать WebSocket отдельно - все работает из коробки.
