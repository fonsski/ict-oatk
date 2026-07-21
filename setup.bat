@echo off
REM ==========================================================================
REM  ICT — первоначальная установка для локального тестирования (Windows)
REM  Требуется: PHP 8.2+, Node.js 18+, npm. Composer НЕ обязателен
REM  (используется локальный composer.phar).
REM ==========================================================================
setlocal
cd /d "%~dp0"

echo.
echo [1/7] Проверка окружения...
where php >nul 2>&1 || (echo ОШИБКА: PHP не найден в PATH & exit /b 1)
where npm >nul 2>&1 || (echo ОШИБКА: npm не найден в PATH & exit /b 1)

echo [2/7] Получение Composer (если отсутствует)...
if not exist composer.phar (
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php --quiet
    del composer-setup.php
)

echo [3/7] Установка PHP-зависимостей...
php composer.phar install --no-interaction --no-progress || exit /b 1

echo [4/7] Установка npm-зависимостей...
call npm install --no-audit --no-fund || exit /b 1

echo [5/7] Настройка .env и ключа приложения...
if not exist .env copy .env.example .env >nul
php artisan key:generate --force

echo [6/7] Создание БД (SQLite) и наполнение данными...
if not exist database\database.sqlite type nul > database\database.sqlite
php artisan migrate:fresh --seed --force || exit /b 1

echo [7/7] Сборка фронтенда...
call npm run build || exit /b 1

echo.
echo ================================================================
echo  Установка завершена. Запустите start.bat для старта приложения.
echo ================================================================
endlocal
