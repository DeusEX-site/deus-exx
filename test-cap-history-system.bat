@echo off
echo ========================================
echo ТЕСТИРОВАНИЕ СИСТЕМЫ ИСТОРИИ КАП
echo ========================================
echo.

echo [1/3] Применяем миграцию...
php artisan migrate --force
if %errorlevel% neq 0 (
    echo ❌ Ошибка при применении миграции
    pause
    exit /b 1
)

echo [2/3] Запускаем тесты...
php artisan test:cap-history
if %errorlevel% neq 0 (
    echo ❌ Ошибка при выполнении тестов
    pause
    exit /b 1
)

echo [3/3] Очистка тестовых данных...
php artisan test:cap-history --clear

echo.
echo ✅ Тестирование завершено успешно!
echo.
echo 🔍 Что было протестировано:
echo - Создание новых кап
echo - Обновление существующих кап с изменениями  
echo - Попытка обновления без изменений
echo - Множественные расписания с GMT
echo - Система истории изменений
echo.
pause 