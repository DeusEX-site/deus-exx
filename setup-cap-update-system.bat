@echo off
echo 🚀 Настройка системы обновления кап по совпадению aff, brok, geo...
echo.

REM Поиск PHP
where php >nul 2>&1
if %errorlevel%==0 (
    echo ✅ PHP найден
    set PHP_CMD=php
) else (
    echo ❌ PHP не найден в PATH
    echo Попробуйте запустить из папки с PHP или добавьте PHP в PATH
    pause
    exit /b 1
)

echo 📋 Шаг 1: Создание таблицы cap_history...
%PHP_CMD% artisan migrate:cap-history
if %errorlevel%==0 (
    echo ✅ Таблица cap_history создана успешно!
) else (
    echo ❌ Ошибка при создании таблицы cap_history
    pause
    exit /b 1
)

echo.
echo 🔍 Шаг 2: Тестирование системы обновления кап...
%PHP_CMD% artisan test:cap-update-system
if %errorlevel%==0 (
    echo ✅ Тестирование прошло успешно!
) else (
    echo ❌ Ошибка при тестировании системы
    pause
    exit /b 1
)

echo.
echo 🎉 Настройка завершена успешно!
echo.
echo 📊 Новая система обновления кап работает следующим образом:
echo    • При получении сообщения с капой проверяется совпадение aff, brok, geo
echo    • Если найдено совпадение - существующая капа обновляется
echo    • Если совпадения нет - создается новая капа
echo    • Все изменения сохраняются в истории
echo    • История по умолчанию скрыта в выпадающем списке
echo.
echo 🌐 Особенности системы:
echo    • Автоматическое обновление по совпадению параметров
echo    • Полная история изменений каждой капы
echo    • Возможность показать/скрыть записи истории
echo    • Статистика обновлений и изменений
echo    • Поиск и объединение дубликатов
echo.
echo 🔧 Доступные команды:
echo    • php artisan migrate:cap-history - создать таблицу истории
echo    • php artisan test:cap-update-system - тестировать систему
echo.
echo 🌐 Откройте /cap-analysis в браузере для просмотра результатов

pause 