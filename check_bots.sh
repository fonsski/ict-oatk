#!/bin/bash

# Скрипт для проверки запущенных экземпляров Telegram бота

echo "Поиск запущенных экземпляров Telegram бота..."
echo ""

BOTS=$(./vendor/bin/sail exec laravel.test ps aux | grep "telegram:standalone" | grep -v grep)

if [ -z "$BOTS" ]; then
    echo "Запущенных экземпляров бота не найдено."
else
    echo "Найдены следующие экземпляры бота:"
    echo "$BOTS"
    echo ""
    COUNT=$(echo "$BOTS" | wc -l)
    echo "Всего запущено экземпляров: $COUNT"

    if [ $COUNT -gt 1 ]; then
        echo ""
        echo "Обнаружено несколько экземпляров бота. Это может привести к дублированию уведомлений."
        echo "Хотите остановить все экземпляры? (y/n)"
        read -r answer
        if [ "$answer" = "y" ]; then
            echo "Останавливаем все экземпляры бота..."
            PIDS=$(echo "$BOTS" | awk '{print $2}')
            for PID in $PIDS; do
                echo "Останавливаем процесс с PID $PID..."
                ./vendor/bin/sail exec laravel.test kill -15 $PID
            done
            echo "Все экземпляры бота остановлены."
        fi
    fi
fi

echo ""
echo "Поиск других процессов, связанных с Telegram..."
OTHER_TELEGRAM=$(./vendor/bin/sail exec laravel.test ps aux | grep -i "telegram" | grep -v "telegram:standalone" | grep -v grep)

if [ -n "$OTHER_TELEGRAM" ]; then
    echo "Найдены другие процессы, связанные с Telegram:"
    echo "$OTHER_TELEGRAM"
fi
