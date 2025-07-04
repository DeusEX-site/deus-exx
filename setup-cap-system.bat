@echo off
echo 🚀 Настройка новой системы отдельных записей кап...
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

echo 📋 Шаг 1: Создание таблицы caps...
%PHP_CMD% artisan migrate:caps
if %errorlevel%==0 (
    echo ✅ Таблица caps создана успешно!
) else (
    echo ❌ Ошибка при создании таблицы caps
    pause
    exit /b 1
)

echo.
echo 🔍 Шаг 2: Анализ существующих сообщений...
%PHP_CMD% artisan analyze:existing-messages --limit=1000
if %errorlevel%==0 (
    echo ✅ Анализ существующих сообщений завершен!
) else (
    echo ❌ Ошибка при анализе существующих сообщений
    pause
    exit /b 1
)

echo.
echo 🧪 Шаг 3: Запуск тестов...
%PHP_CMD% artisan test:new-cap-system
if %errorlevel%==0 (
    echo ✅ Тесты прошли успешно!
) else (
    echo ❌ Ошибка при выполнении тестов
    pause
    exit /b 1
)

echo.
echo 🎉 Настройка завершена успешно!
echo.
echo 📊 Теперь система кап работает следующим образом:
echo    • Каждая капа создает отдельную запись
echo    • Новые сообщения анализируются автоматически
echo    • Поиск работает быстро через базу данных
echo    • Каждая капа отображается отдельно
echo.
echo 🌐 Откройте /cap-analysis в браузере для просмотра результатов
echo.
pause 