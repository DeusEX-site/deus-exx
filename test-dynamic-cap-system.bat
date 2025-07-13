@echo off
chcp 65001 > nul
cls

echo ==========================================
echo      СИСТЕМА ДИНАМИЧЕСКИХ ТЕСТОВ КАП
echo ==========================================
echo.

if not exist "artisan" (
    echo ❌ Ошибка: Не найден файл artisan
    echo Пожалуйста, запустите скрипт из корневой директории Laravel проекта
    pause
    exit /b 1
)

echo 🚀 Запуск полного тестирования динамических кап...
echo 📝 Подробный вывод в реальном времени
echo ⏸️  Пауза на каждой ошибке
echo.

REM Запускаем полное тестирование
php artisan test:dynamic-cap-system full --detailed --pause-on-error

echo.
echo ✅ Тестирование завершено!
pause 