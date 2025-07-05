@echo off
title Тестирование уведомлений о капах
echo.
echo ========================================
echo   ТЕСТИРОВАНИЕ УВЕДОМЛЕНИЙ О КАПАХ
echo ========================================
echo.

echo 1. Тест с включенными уведомлениями
echo 2. Тест с отключенными уведомлениями
echo 3. Выход
echo.

set /p choice="Выберите опцию (1-3): "

if "%choice%"=="1" (
    echo.
    echo Запуск тестирования с включенными уведомлениями...
    echo.
    php artisan test:cap-notifications
    goto :end
)

if "%choice%"=="2" (
    echo.
    echo Запуск тестирования с отключенными уведомлениями...
    echo.
    php artisan test:cap-notifications --disable-notifications
    goto :end
)

if "%choice%"=="3" (
    echo.
    echo Выход...
    goto :end
)

echo.
echo Неверный выбор. Попробуйте еще раз.
echo.
pause
goto :start

:end
echo.
echo ========================================
echo   ТЕСТИРОВАНИЕ ЗАВЕРШЕНО
echo ========================================
echo.
echo Для получения ID чата отправьте боту команду /start
echo или добавьте бота в группу и отправьте любое сообщение
echo.
pause 