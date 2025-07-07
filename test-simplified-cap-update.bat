@echo off
echo 🚀 Тестирование упрощенного обновления кап через reply...
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
echo 📋 Запуск теста упрощенного обновления кап...
%PHP_CMD% artisan test:cap-status-system

if %errorlevel%==0 (
    echo.
    echo 🎉 Тест завершен успешно!
    echo.
    echo 📊 Что было протестировано:
    echo    • Создание капы для тестирования
    echo    • Обновление через reply с упрощенным форматом (только Geo + поля)
    echo    • Проверка обновления лимита и расписания
    echo    • Обработка ошибки неправильного Geo
    echo    • Обработка ошибки отсутствия Geo
    echo    • Сброс полей до значений по умолчанию
    echo    • Обновление через цепочку reply (reply на reply)
    echo.
    echo 📄 Примеры упрощенного формата:
    echo.
    echo    === Обновление лимита и расписания ===
    echo    ^(Reply на сообщение с капой^)
    echo    Geo: AT
    echo    Cap: 30
    echo    Schedule: 10:00/18:00
    echo.
    echo    === Сброс полей до значений по умолчанию ===
    echo    ^(Reply на сообщение с капой^)
    echo    Geo: AT
    echo    Total:
    echo    Schedule:
    echo    Language:
    echo.
    echo    === Обновление только одного поля ===
    echo    ^(Reply на сообщение с капой^)
    echo    Geo: AT
    echo    Cap: 50
    echo.
    echo 🔄 Преимущества упрощенного формата:
    echo    • Быстрота - не нужно указывать все поля повторно
    echo    • Безопасность - невозможно случайно создать новую капу
    echo    • Гибкость - можно обновить только нужные поля
    echo    • Сброс полей - легко сбросить поля до значений по умолчанию
    echo    • Цепочка reply - можно отвечать на любое сообщение в цепочке
    echo.
) else (
    echo.
    echo ❌ Тест завершился с ошибкой
    echo Проверьте логи для получения подробной информации
    echo.
)

pause 