@echo off
chcp 65001 > nul
:menu
cls
echo ==========================================
echo     СИСТЕМА ДИНАМИЧЕСКИХ ТЕСТОВ КАП
echo ==========================================
echo.
echo 🚀 Полная система автоматического тестирования
echo    всех 16 типов операций + команды статуса
echo.
echo Выберите тип тестирования:
echo.
echo 1. Быстрое тестирование (ограниченный набор)
echo 2. Полное тестирование (все операции)
echo 3. Только создание кап
echo 4. Только обновление кап
echo 5. Только команды статуса
echo 6. Только сброс полей
echo 7. Показать статистику тестов
echo 8. Настройки тестирования
echo 9. Справка
echo 0. Выход
echo.
set /p choice=Введите номер (0-9): 

if "%choice%"=="1" goto quick_test
if "%choice%"=="2" goto full_test
if "%choice%"=="3" goto create_test
if "%choice%"=="4" goto update_test
if "%choice%"=="5" goto status_test
if "%choice%"=="6" goto reset_test
if "%choice%"=="7" goto stats_test
if "%choice%"=="8" goto settings
if "%choice%"=="9" goto help
if "%choice%"=="0" goto exit

echo Неверный выбор. Попробуйте снова.
pause
goto menu

:quick_test
cls
echo ==========================================
echo        БЫСТРОЕ ТЕСТИРОВАНИЕ
echo ==========================================
echo.
echo ⚡ Запуск быстрого тестирования...
echo ⏱️  Время выполнения: ~1-2 минуты
echo 📊 Покрытие: основные операции создания
echo.
php artisan test:dynamic-cap-system quick
echo.
echo ✅ Быстрое тестирование завершено!
pause
goto menu

:full_test
cls
echo ==========================================
echo        ПОЛНОЕ ТЕСТИРОВАНИЕ
echo ==========================================
echo.
echo ⚠️  ВНИМАНИЕ: Полное тестирование может занять много времени!
echo.
set /p confirm=Продолжить? (y/n): 
if /I "%confirm%" NEQ "y" goto menu
echo.
echo 🎯 Запуск полного тестирования...
echo ⏱️  Время выполнения: ~10-30 минут
echo 📊 Покрытие: все 16 типов операций + команды статуса
echo.
php artisan test:dynamic-cap-system full --max-tests=100
echo.
echo ✅ Полное тестирование завершено!
pause
goto menu

:create_test
cls
echo ==========================================
echo      ТЕСТИРОВАНИЕ СОЗДАНИЯ КАП
echo ==========================================
echo.
echo 📝 Запуск тестирования создания кап...
echo ⏱️  Время выполнения: ~3-5 минут
echo 📊 Покрытие: все типы создания кап
echo.
php artisan test:dynamic-cap-system create --max-tests=50
echo.
echo ✅ Тестирование создания завершено!
pause
goto menu

:update_test
cls
echo ==========================================
echo      ТЕСТИРОВАНИЕ ОБНОВЛЕНИЯ КАП
echo ==========================================
echo.
echo 🔄 Запуск тестирования обновления кап...
echo ⏱️  Время выполнения: ~5-8 минут
echo 📊 Покрытие: все типы обновления кап
echo.
php artisan test:dynamic-cap-system update --max-tests=50
echo.
echo ✅ Тестирование обновления завершено!
pause
goto menu

:status_test
cls
echo ==========================================
echo      ТЕСТИРОВАНИЕ КОМАНД СТАТУСА
echo ==========================================
echo.
echo 🔧 Запуск тестирования команд статуса...
echo ⏱️  Время выполнения: ~1 минута
echo 📊 Покрытие: RUN, STOP, DELETE, RESTORE, STATUS
echo.
php artisan test:dynamic-cap-system status
echo.
echo ✅ Тестирование команд статуса завершено!
pause
goto menu

:reset_test
cls
echo ==========================================
echo      ТЕСТИРОВАНИЕ СБРОСА ПОЛЕЙ
echo ==========================================
echo.
echo 🔄 Запуск тестирования сброса полей...
echo ⏱️  Время выполнения: ~2-3 минуты
echo 📊 Покрытие: все комбинации сброса полей
echo.
php artisan test:dynamic-cap-system reset
echo.
echo ✅ Тестирование сброса полей завершено!
pause
goto menu

:stats_test
cls
echo ==========================================
echo        СТАТИСТИКА ТЕСТОВ
echo ==========================================
echo.
echo 📊 Получение статистики планируемых тестов...
echo.
php artisan test:dynamic-cap-system stats
echo.
pause
goto menu

:settings
cls
echo ==========================================
echo        НАСТРОЙКИ ТЕСТИРОВАНИЯ
echo ==========================================
echo.
echo Выберите настройки:
echo.
echo 1. Малый объем тестов (быстро)
echo 2. Средний объем тестов (рекомендуется)
echo 3. Большой объем тестов (медленно)
echo 4. Максимальный объем тестов (очень медленно)
echo 5. Пользовательские настройки
echo 6. Назад в главное меню
echo.
set /p settings_choice=Введите номер (1-6): 

if "%settings_choice%"=="1" goto small_settings
if "%settings_choice%"=="2" goto medium_settings
if "%settings_choice%"=="3" goto large_settings
if "%settings_choice%"=="4" goto max_settings
if "%settings_choice%"=="5" goto custom_settings
if "%settings_choice%"=="6" goto menu

echo Неверный выбор. Попробуйте снова.
pause
goto settings

:small_settings
cls
echo ==========================================
echo        МАЛЫЙ ОБЪЕМ ТЕСТОВ
echo ==========================================
echo.
echo ⚙️  Настройки:
echo • Максимум тестов на тип: 10
echo • Максимум комбинаций: 2
echo • Максимум перестановок: 6
echo • Таймаут: 180 секунд
echo.
echo 🎯 Запуск полного тестирования с малым объемом...
php artisan test:dynamic-cap-system full --max-tests=10 --max-combinations=2 --max-permutations=6 --timeout=180
echo.
echo ✅ Тестирование завершено!
pause
goto menu

:medium_settings
cls
echo ==========================================
echo        СРЕДНИЙ ОБЪЕМ ТЕСТОВ
echo ==========================================
echo.
echo ⚙️  Настройки:
echo • Максимум тестов на тип: 50
echo • Максимум комбинаций: 3
echo • Максимум перестановок: 12
echo • Таймаут: 300 секунд
echo.
echo 🎯 Запуск полного тестирования со средним объемом...
php artisan test:dynamic-cap-system full --max-tests=50 --max-combinations=3 --max-permutations=12 --timeout=300
echo.
echo ✅ Тестирование завершено!
pause
goto menu

:large_settings
cls
echo ==========================================
echo        БОЛЬШОЙ ОБЪЕМ ТЕСТОВ
echo ==========================================
echo.
echo ⚙️  Настройки:
echo • Максимум тестов на тип: 200
echo • Максимум комбинаций: 4
echo • Максимум перестановок: 24
echo • Таймаут: 600 секунд
echo.
echo ⚠️  ВНИМАНИЕ: Большой объем тестов может занять 30-60 минут!
set /p confirm=Продолжить? (y/n): 
if /I "%confirm%" NEQ "y" goto settings
echo.
echo 🎯 Запуск полного тестирования с большим объемом...
php artisan test:dynamic-cap-system full --max-tests=200 --max-combinations=4 --max-permutations=24 --timeout=600
echo.
echo ✅ Тестирование завершено!
pause
goto menu

:max_settings
cls
echo ==========================================
echo        МАКСИМАЛЬНЫЙ ОБЪЕМ ТЕСТОВ
echo ==========================================
echo.
echo ⚙️  Настройки:
echo • Максимум тестов на тип: без ограничений
echo • Максимум комбинаций: 5
echo • Максимум перестановок: 120
echo • Таймаут: 1800 секунд (30 минут)
echo.
echo ⚠️  ВНИМАНИЕ: Максимальный объем может создать десятки тысяч тестов!
echo ⚠️  Время выполнения: несколько часов!
set /p confirm=Вы уверены? (y/n): 
if /I "%confirm%" NEQ "y" goto settings
echo.
echo 🎯 Запуск полного тестирования с максимальным объемом...
php artisan test:dynamic-cap-system full --max-tests=0 --max-combinations=5 --max-permutations=120 --timeout=1800
echo.
echo ✅ Тестирование завершено!
pause
goto menu

:custom_settings
cls
echo ==========================================
echo        ПОЛЬЗОВАТЕЛЬСКИЕ НАСТРОЙКИ
echo ==========================================
echo.
echo Введите параметры тестирования:
echo.
set /p max_tests=Максимум тестов на тип (0=без ограничений): 
set /p max_combinations=Максимум комбинаций полей (1-5): 
set /p max_permutations=Максимум перестановок (1-120): 
set /p timeout=Таймаут в секундах (60-3600): 
echo.
echo 🎯 Запуск полного тестирования с пользовательскими настройками...
php artisan test:dynamic-cap-system full --max-tests=%max_tests% --max-combinations=%max_combinations% --max-permutations=%max_permutations% --timeout=%timeout%
echo.
echo ✅ Тестирование завершено!
pause
goto menu

:help
cls
echo ==========================================
echo             СПРАВКА
echo ==========================================
echo.
echo 🚀 Система динамических тестов кап
echo.
echo 📋 Что тестируется:
echo • 16 типов операций с капами
echo • Все комбинации полей и значений
echo • Все возможные перестановки порядка полей
echo • Команды статуса (RUN, STOP, DELETE, RESTORE)
echo • Сброс полей до значений по умолчанию
echo • Валидация ошибочных случаев
echo.
echo 🎯 Покрываемые операции:
echo • Сообщение ^> Создание ^> Одиночное/Групповое ^> Одна/Много кап
echo • Сообщение ^> Обновление ^> Одиночное/Групповое ^> Одна/Много кап
echo • Ответ ^> Обновление ^> Одиночное/Групповое ^> Одна/Много кап
echo • Цитата ^> Обновление ^> Одиночное/Групповое ^> Одна/Много кап
echo.
echo 📊 Отчеты:
echo • Краткий отчет (консоль)
echo • Детальный отчет (файл)
echo • Анализ ошибок (файл)
echo • CSV экспорт (файл)
echo • JSON отчет (файл)
echo.
echo 🔧 Команды Laravel:
echo • php artisan test:dynamic-cap-system
echo • php artisan test:dynamic-cap-system quick
echo • php artisan test:dynamic-cap-system create
echo • php artisan test:dynamic-cap-system update
echo • php artisan test:dynamic-cap-system status
echo • php artisan test:dynamic-cap-system stats
echo.
echo 📁 Файлы системы:
echo • DynamicCapTestGenerator.php
echo • DynamicCapTestEngine.php
echo • DynamicCapCombinationGenerator.php
echo • DynamicCapReportGenerator.php
echo • dynamic_cap_test_runner.php
echo • app/Console/Commands/TestDynamicCapSystem.php
echo.
pause
goto menu

:exit
echo До свидания!
pause
exit 