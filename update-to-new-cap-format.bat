@echo off
echo 🚀 Обновление до нового стандартного формата кап...
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

echo.
echo 📋 Запуск обновления системы кап...
%PHP_CMD% artisan update:new-cap-format --test

if %errorlevel%==0 (
    echo.
    echo 🎉 Обновление завершено успешно!
    echo.
    echo 📊 Что изменилось:
    echo    • Удалена логика поиска слова "cap" и синонимов
    echo    • Система теперь обрабатывает только стандартный формат
    echo    • Добавлены новые поля: Language, Funnel, Pending ACQ, Freeze status
    echo    • Broker переименован в Recipient
    echo    • Автоматические значения по умолчанию для пустых полей
    echo.
    echo 📄 Примеры нового формата сообщений:
    echo.
    echo    === Одиночная капа ===
    echo    Affiliate: G06
    echo    Recipient: TMedia
    echo    Cap: 15
    echo    Total: 
    echo    Geo: IE
    echo    Language: en
    echo    Funnel: 
    echo    Schedule: 10:00/18:00 GMT+03:00
    echo    Date: 
    echo    Pending ACQ: No
    echo    Freeze status on ACQ: No
    echo.
    echo    === Несколько аффилейтов ===
    echo    Affiliate: G06, aff2
    echo    Recipient: TMedia
    echo    Cap: 20
    echo    ^(создает 2 записи кап^)
    echo.
    echo    === Несколько получателей ===
    echo    Affiliate: G06
    echo    Recipient: TMedia, brok2
    echo    Cap: 30
    echo    ^(создает 2 записи кап^)
    echo.
    echo ✅ Система готова к работе с новым форматом!
) else (
    echo ❌ Ошибка при обновлении системы
    pause
    exit /b 1
)

echo.
pause 