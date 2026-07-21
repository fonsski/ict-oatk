@echo off
REM ==========================================================================
REM  ICT — запуск приложения для ручного тестирования (Windows)
REM  Открывает три окна: веб-сервер, Vite (hot-reload), обработчик очередей.
REM ==========================================================================
cd /d "%~dp0"

if not exist vendor (
    echo Зависимости не установлены. Сначала запустите setup.bat
    pause
    exit /b 1
)

start "ICT - Web server (http://127.0.0.1:8000)" cmd /k php artisan serve --host=127.0.0.1 --port=8000
start "ICT - Vite dev" cmd /k npm run dev
start "ICT - Queue worker" cmd /k php artisan queue:work --tries=3

timeout /t 4 >nul
start http://127.0.0.1:8000

echo.
echo Приложение запущено: http://127.0.0.1:8000
echo Закройте открывшиеся окна, чтобы остановить сервисы.
