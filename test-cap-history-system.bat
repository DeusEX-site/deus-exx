@echo off
echo.
echo 🧪 Тестирование системы истории кап...
echo.

REM Ищем PHP в разных местах
set PHP_PATH=
if exist "C:\xampp\php\php.exe" set PHP_PATH=C:\xampp\php\php.exe
if exist "C:\wamp64\bin\php\php7.4.33\php.exe" set PHP_PATH=C:\wamp64\bin\php\php7.4.33\php.exe
if exist "C:\wamp64\bin\php\php8.0.30\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.0.30\php.exe
if exist "C:\wamp64\bin\php\php8.1.25\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.1.25\php.exe
if exist "C:\wamp64\bin\php\php8.2.13\php.exe" set PHP_PATH=C:\wamp64\bin\php\php8.2.13\php.exe
if exist "C:\php\php.exe" set PHP_PATH=C:\php\php.exe

REM Проверяем PATH
php --version >nul 2>&1
if %errorlevel% == 0 set PHP_PATH=php

if "%PHP_PATH%"=="" (
    echo ❌ PHP не найден! Установите PHP или укажите правильный путь.
    echo.
    echo Попробуйте установить XAMPP или добавить PHP в PATH:
    echo https://www.apachefriends.org/download.html
    echo.
    pause
    goto :end
)

echo ✅ Найден PHP: %PHP_PATH%
echo.

echo 📋 Выполняется тест системы истории кап...
echo.

"%PHP_PATH%" test_cap_history_system.php

if %errorlevel% neq 0 (
    echo.
    echo ❌ Ошибка при выполнении теста!
    echo.
) else (
    echo.
    echo ✅ Тест выполнен успешно!
    echo.
)

:end
pause 