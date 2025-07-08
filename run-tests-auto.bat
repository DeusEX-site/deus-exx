@echo off
chcp 65001 > nul
echo ==========================================
echo      АВТОМАТИЧЕСКИЙ ЗАПУСК ВСЕХ ТЕСТОВ
echo ==========================================
echo.
echo Запуск всех тестов без пауз...
echo Результаты будут сохранены в test-results.log
echo.

rem Очищаем файл логов
echo. > test-results.log
echo ==========================================>> test-results.log
echo АВТОМАТИЧЕСКИЙ ЗАПУСК ВСЕХ ТЕСТОВ - %date% %time%>> test-results.log
echo ==========================================>> test-results.log
echo.>> test-results.log

echo Запуск тестов Artisan команд...
echo.
echo ==========================================>> test-results.log
echo ТЕСТЫ ARTISAN КОМАНД>> test-results.log
echo ==========================================>> test-results.log

echo [1/12] Тест системы анализа капы...
echo 1. Тест системы анализа капы - %time%>> test-results.log
php artisan test:cap-analysis >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [2/12] Тест системы истории капы...
echo 2. Тест системы истории капы - %time%>> test-results.log
php artisan test:cap-history-system >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [3/12] Тест системы статуса капы...
echo 3. Тест системы статуса капы - %time%>> test-results.log
php artisan test:cap-status-system >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [4/12] Тест позиций чата...
echo 4. Тест позиций чата - %time%>> test-results.log
php artisan test:chat-positions >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [5/12] Тест полной системы...
echo 5. Тест полной системы - %time%>> test-results.log
php artisan test:full-system >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [6/12] Тест новой системы капы...
echo 6. Тест новой системы капы - %time%>> test-results.log
php artisan test:new-cap-system >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [7/12] Тест исходящих сообщений...
echo 7. Тест исходящих сообщений - %time%>> test-results.log
php artisan test:outgoing-messages >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [8/12] Тест стабильности позиций...
echo 8. Тест стабильности позиций - %time%>> test-results.log
php artisan test:position-stability >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [9/12] Тест логики замены...
echo 9. Тест логики замены - %time%>> test-results.log
php artisan test:replacement-logic >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [10/12] Тест сообщений обмена...
echo 10. Тест сообщений обмена - %time%>> test-results.log
php artisan test:swap-messages >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [11/12] Тест правила топ-10...
echo 11. Тест правила топ-10 - %time%>> test-results.log
php artisan test:top-ten-rule >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [12/12] Тест стабильности топ-3...
echo 12. Тест стабильности топ-3 - %time%>> test-results.log
php artisan test:top-three-stability >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo Запуск PHP файлов тестов...
echo.
echo ==========================================>> test-results.log
echo ТЕСТЫ PHP ФАЙЛОВ>> test-results.log
echo ==========================================>> test-results.log

echo [1/8] Тест парсинга капы...
echo 1. Тест парсинга капы - %time%>> test-results.log
php test_cap_parsing.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [2/8] Тест системы истории капы...
echo 2. Тест системы истории капы - %time%>> test-results.log
php test_cap_history_system.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [3/8] Тест пустых полей...
echo 3. Тест пустых полей - %time%>> test-results.log
php test_empty_fields.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [4/8] Тест отладки пустых полей...
echo 4. Тест отладки пустых полей - %time%>> test-results.log
php test_empty_fields_debug.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [5/8] Тест парсинга расписания...
echo 5. Тест парсинга расписания - %time%>> test-results.log
php test_schedule_parsing.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [6/8] Тест парсинга мульти-расписания...
echo 6. Тест парсинга мульти-расписания - %time%>> test-results.log
php test_multi_schedule_parsing.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [7/8] Тест системы обновления ответов...
echo 7. Тест системы обновления ответов - %time%>> test-results.log
php test_reply_update_system.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo [8/8] Тест мульти-команд статуса...
echo 8. Тест мульти-команд статуса - %time%>> test-results.log
php test_status_commands_multi.php >> test-results.log 2>&1
echo Завершено с кодом: %errorlevel%>> test-results.log
echo.>> test-results.log

echo ==========================================>> test-results.log
echo АВТОМАТИЧЕСКОЕ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО - %time%>> test-results.log
echo ==========================================>> test-results.log

echo.
echo ==========================================
echo    АВТОМАТИЧЕСКОЕ ТЕСТИРОВАНИЕ ЗАВЕРШЕНО
echo ==========================================
echo.
echo Все тесты выполнены автоматически.
echo Результаты сохранены в файл: test-results.log
echo.
echo Для просмотра результатов выполните:
echo type test-results.log
echo.
pause 