@echo off
chcp 65001 > nul
:menu
cls
echo ==========================================
echo       ИНТЕРАКТИВНОЕ МЕНЮ ТЕСТОВ
echo ==========================================
echo.
echo Выберите действие:
echo.
echo 1. Запустить все тесты (полная отладка)
echo 2. Запустить основные тесты (быстрая отладка)
echo 3. Тесты системы капы
echo 4. Тесты позиций и чатов
echo 5. Тесты парсинга и расписания
echo 6. Тесты сообщений и обмена
echo 7. Отдельные PHP файлы тестов
echo 8. Выход
echo.
set /p choice=Введите номер (1-8): 

if "%choice%"=="1" goto all_tests
if "%choice%"=="2" goto core_tests
if "%choice%"=="3" goto cap_tests
if "%choice%"=="4" goto position_tests
if "%choice%"=="5" goto parsing_tests
if "%choice%"=="6" goto message_tests
if "%choice%"=="7" goto php_tests
if "%choice%"=="8" goto exit

echo Неверный выбор. Попробуйте снова.
pause
goto menu

:all_tests
cls
echo Запуск всех тестов...
call run-all-tests.bat
goto menu

:core_tests
cls
echo Запуск основных тестов...
call run-core-tests.bat
goto menu

:cap_tests
cls
echo ==========================================
echo           ТЕСТЫ СИСТЕМЫ КАПЫ
echo ==========================================
echo.
echo 1. Тест системы анализа капы
php artisan test:cap-analysis
echo.
pause
echo.
echo 2. Тест системы истории капы
php artisan test:cap-history-system
echo.
pause
echo.
echo 3. Тест системы статуса капы
php artisan test:cap-status-system
echo.
pause
echo.
echo 4. Тест новой системы капы
php artisan test:new-cap-system
echo.
pause
echo.
echo Тесты системы капы завершены.
pause
goto menu

:position_tests
cls
echo ==========================================
echo        ТЕСТЫ ПОЗИЦИЙ И ЧАТОВ
echo ==========================================
echo.
echo 1. Тест позиций чата
php artisan test:chat-positions
echo.
pause
echo.
echo 2. Тест стабильности позиций
php artisan test:position-stability
echo.
pause
echo.
echo 3. Тест правила топ-10
php artisan test:top-ten-rule
echo.
pause
echo.
echo 4. Тест стабильности топ-3
php artisan test:top-three-stability
echo.
pause
echo.
echo Тесты позиций и чатов завершены.
pause
goto menu

:parsing_tests
cls
echo ==========================================
echo      ТЕСТЫ ПАРСИНГА И РАСПИСАНИЯ
echo ==========================================
echo.
echo 1. Тест парсинга капы
php test_cap_parsing.php
echo.
pause
echo.
echo 2. Тест парсинга расписания
php test_schedule_parsing.php
echo.
pause
echo.
echo 3. Тест парсинга мульти-расписания
php test_multi_schedule_parsing.php
echo.
pause
echo.
echo 4. Тест пустых полей
php test_empty_fields.php
echo.
pause
echo.
echo Тесты парсинга и расписания завершены.
pause
goto menu

:message_tests
cls
echo ==========================================
echo       ТЕСТЫ СООБЩЕНИЙ И ОБМЕНА
echo ==========================================
echo.
echo 1. Тест исходящих сообщений
php artisan test:outgoing-messages
echo.
pause
echo.
echo 2. Тест сообщений обмена
php artisan test:swap-messages
echo.
pause
echo.
echo 3. Тест логики замены
php artisan test:replacement-logic
echo.
pause
echo.
echo 4. Тест системы обновления ответов
php test_reply_update_system.php
echo.
pause
echo.
echo Тесты сообщений и обмена завершены.
pause
goto menu

:php_tests
cls
echo ==========================================
echo        ОТДЕЛЬНЫЕ PHP ФАЙЛЫ ТЕСТОВ
echo ==========================================
echo.
echo 1. Тест парсинга капы
php test_cap_parsing.php
echo.
pause
echo.
echo 2. Тест системы истории капы
php test_cap_history_system.php
echo.
pause
echo.
echo 3. Тест пустых полей
php test_empty_fields.php
echo.
pause
echo.
echo 4. Тест отладки пустых полей
php test_empty_fields_debug.php
echo.
pause
echo.
echo 5. Тест парсинга расписания
php test_schedule_parsing.php
echo.
pause
echo.
echo 6. Тест парсинга мульти-расписания
php test_multi_schedule_parsing.php
echo.
pause
echo.
echo 7. Тест системы обновления ответов
php test_reply_update_system.php
echo.
pause
echo.
echo 8. Тест мульти-команд статуса
php test_status_commands_multi.php
echo.
pause
echo.
echo PHP тесты завершены.
pause
goto menu

:exit
echo До свидания!
pause
exit 