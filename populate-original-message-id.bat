@echo off
echo 🔄 Populating missing original_message_id values...
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

echo 📋 Шаг 1: Обновление записей в таблице caps...
%PHP_CMD% artisan populate:original-message-id
if %errorlevel%==0 (
    echo ✅ Обновление завершено успешно!
) else (
    echo ❌ Ошибка при обновлении
    pause
    exit /b 1
)

echo.
echo 🎉 Все записи обновлены!
echo.
echo 📊 Теперь все капы и история кап имеют правильные original_message_id
echo.
pause 