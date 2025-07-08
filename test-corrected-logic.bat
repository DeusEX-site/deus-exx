@echo off
echo 🧪 Тестирование правильной логики original_message_id...
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

echo 📋 Запуск тестов...
%PHP_CMD% test_corrected_logic.php
if %errorlevel%==0 (
    echo ✅ Тесты завершены успешно!
) else (
    echo ❌ Ошибка при выполнении тестов
    pause
    exit /b 1
)

echo.
echo 🎉 Тестирование завершено!
echo.
pause 