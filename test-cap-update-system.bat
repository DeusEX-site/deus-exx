@echo off
echo ======================================
echo 🚀 Тестирование системы обновления капы
echo ======================================
echo.

echo 📋 Проверка наличия PHP...
where php >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ PHP не найден в PATH
    echo Убедитесь что PHP установлен и добавлен в PATH
    pause
    exit /b 1
)

echo ✅ PHP найден
echo.

echo 📋 Проверка наличия файлов Laravel...
if not exist artisan (
    echo ❌ Файл artisan не найден
    echo Убедитесь что вы находитесь в корневой папке проекта Laravel
    pause
    exit /b 1
)

echo ✅ Laravel проект найден
echo.

echo 📋 Запуск тестирования системы обновления капы...
echo.
php artisan test:cap-update-system

echo.
echo ======================================
echo 🎉 Тестирование завершено!
echo ======================================
echo.

echo 📊 Дополнительные команды для проверки:
echo - php artisan analyze:existing-messages --limit=100
echo - php artisan test:new-cap-system
echo - php artisan migrate
echo.

pause 