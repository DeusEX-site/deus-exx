#!/bin/bash

# Enable UTF-8 encoding
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Function to pause execution
pause() {
    read -p "Press any key to continue..." -n1 -s
    echo
}

# Function to get user input
get_input() {
    local prompt="$1"
    local variable_name="$2"
    read -p "$prompt" $variable_name
}

# Function to confirm action
confirm() {
    local prompt="$1"
    local response
    read -p "$prompt" response
    if [[ "$response" =~ ^[Yy]$ ]]; then
        return 0
    else
        return 1
    fi
}

# Main menu function
show_menu() {
    clear
    echo "=========================================="
    echo "     СИСТЕМА ДИНАМИЧЕСКИХ ТЕСТОВ КАП"
    echo "=========================================="
    echo
    echo "🚀 Полная система автоматического тестирования"
    echo "    всех 16 типов операций + команды статуса"
    echo
    echo "Выберите тип тестирования:"
    echo
    echo "1. Быстрое тестирование (ограниченный набор)"
    echo "2. Полное тестирование (все операции)"
    echo "3. Только создание кап"
    echo "4. Только обновление кап"
    echo "5. Только команды статуса"
    echo "6. Только сброс полей"
    echo "7. Показать статистику тестов"
    echo "8. Настройки тестирования"
    echo "9. Справка"
    echo "0. Выход"
    echo
    get_input "Введите номер (0-9): " choice
    
    case "$choice" in
        1) quick_test ;;
        2) full_test ;;
        3) create_test ;;
        4) update_test ;;
        5) status_test ;;
        6) reset_test ;;
        7) stats_test ;;
        8) settings ;;
        9) help ;;
        0) exit_script ;;
        *) 
            echo "Неверный выбор. Попробуйте снова."
            pause
            show_menu
            ;;
    esac
}

# Quick test function
quick_test() {
    clear
    echo "=========================================="
    echo "        БЫСТРОЕ ТЕСТИРОВАНИЕ"
    echo "=========================================="
    echo
    echo "⚡ Запуск быстрого тестирования..."
    echo "⏱️  Время выполнения: ~1-2 минуты"
    echo "📊 Покрытие: основные операции создания"
    echo
    php artisan test:dynamic-cap-system quick
    echo
    echo "✅ Быстрое тестирование завершено!"
    pause
    show_menu
}

# Full test function
full_test() {
    clear
    echo "=========================================="
    echo "        ПОЛНОЕ ТЕСТИРОВАНИЕ"
    echo "=========================================="
    echo
    echo "⚠️  ВНИМАНИЕ: Полное тестирование может занять много времени!"
    echo
    if confirm "Продолжить? (y/n): "; then
        echo
        echo "🎯 Запуск полного тестирования..."
        echo "⏱️  Время выполнения: ~10-30 минут"
        echo "📊 Покрытие: все 16 типов операций + команды статуса"
        echo
        php artisan test:dynamic-cap-system full --max-tests=100
        echo
        echo "✅ Полное тестирование завершено!"
        pause
    fi
    show_menu
}

# Create test function
create_test() {
    clear
    echo "=========================================="
    echo "      ТЕСТИРОВАНИЕ СОЗДАНИЯ КАП"
    echo "=========================================="
    echo
    echo "📝 Запуск тестирования создания кап..."
    echo "⏱️  Время выполнения: ~3-5 минут"
    echo "📊 Покрытие: все типы создания кап"
    echo
    php artisan test:dynamic-cap-system create --max-tests=50
    echo
    echo "✅ Тестирование создания завершено!"
    pause
    show_menu
}

# Update test function
update_test() {
    clear
    echo "=========================================="
    echo "      ТЕСТИРОВАНИЕ ОБНОВЛЕНИЯ КАП"
    echo "=========================================="
    echo
    echo "🔄 Запуск тестирования обновления кап..."
    echo "⏱️  Время выполнения: ~5-8 минут"
    echo "📊 Покрытие: все типы обновления кап"
    echo
    php artisan test:dynamic-cap-system update --max-tests=50
    echo
    echo "✅ Тестирование обновления завершено!"
    pause
    show_menu
}

# Status test function
status_test() {
    clear
    echo "=========================================="
    echo "      ТЕСТИРОВАНИЕ КОМАНД СТАТУСА"
    echo "=========================================="
    echo
    echo "🔧 Запуск тестирования команд статуса..."
    echo "⏱️  Время выполнения: ~1 минута"
    echo "📊 Покрытие: RUN, STOP, DELETE, RESTORE, STATUS"
    echo
    php artisan test:dynamic-cap-system status
    echo
    echo "✅ Тестирование команд статуса завершено!"
    pause
    show_menu
}

# Reset test function
reset_test() {
    clear
    echo "=========================================="
    echo "      ТЕСТИРОВАНИЕ СБРОСА ПОЛЕЙ"
    echo "=========================================="
    echo
    echo "🔄 Запуск тестирования сброса полей..."
    echo "⏱️  Время выполнения: ~2-3 минуты"
    echo "📊 Покрытие: все комбинации сброса полей"
    echo
    php artisan test:dynamic-cap-system reset
    echo
    echo "✅ Тестирование сброса полей завершено!"
    pause
    show_menu
}

# Stats test function
stats_test() {
    clear
    echo "=========================================="
    echo "        СТАТИСТИКА ТЕСТОВ"
    echo "=========================================="
    echo
    echo "📊 Получение статистики планируемых тестов..."
    echo
    php artisan test:dynamic-cap-system stats
    echo
    pause
    show_menu
}

# Settings function
settings() {
    clear
    echo "=========================================="
    echo "        НАСТРОЙКИ ТЕСТИРОВАНИЯ"
    echo "=========================================="
    echo
    echo "Выберите настройки:"
    echo
    echo "1. Малый объем тестов (быстро)"
    echo "2. Средний объем тестов (рекомендуется)"
    echo "3. Большой объем тестов (медленно)"
    echo "4. Максимальный объем тестов (очень медленно)"
    echo "5. Пользовательские настройки"
    echo "6. Назад в главное меню"
    echo
    get_input "Введите номер (1-6): " settings_choice
    
    case "$settings_choice" in
        1) small_settings ;;
        2) medium_settings ;;
        3) large_settings ;;
        4) max_settings ;;
        5) custom_settings ;;
        6) show_menu ;;
        *)
            echo "Неверный выбор. Попробуйте снова."
            pause
            settings
            ;;
    esac
}

# Small settings function
small_settings() {
    clear
    echo "=========================================="
    echo "        МАЛЫЙ ОБЪЕМ ТЕСТОВ"
    echo "=========================================="
    echo
    echo "⚙️  Настройки:"
    echo "• Максимум тестов на тип: 10"
    echo "• Максимум комбинаций: 2"
    echo "• Максимум перестановок: 6"
    echo "• Таймаут: 180 секунд"
    echo
    echo "🎯 Запуск полного тестирования с малым объемом..."
    php artisan test:dynamic-cap-system full --max-tests=10 --max-combinations=2 --max-permutations=6 --timeout=180
    echo
    echo "✅ Тестирование завершено!"
    pause
    show_menu
}

# Medium settings function
medium_settings() {
    clear
    echo "=========================================="
    echo "        СРЕДНИЙ ОБЪЕМ ТЕСТОВ"
    echo "=========================================="
    echo
    echo "⚙️  Настройки:"
    echo "• Максимум тестов на тип: 50"
    echo "• Максимум комбинаций: 3"
    echo "• Максимум перестановок: 12"
    echo "• Таймаут: 300 секунд"
    echo
    echo "🎯 Запуск полного тестирования со средним объемом..."
    php artisan test:dynamic-cap-system full --max-tests=50 --max-combinations=3 --max-permutations=12 --timeout=300
    echo
    echo "✅ Тестирование завершено!"
    pause
    show_menu
}

# Large settings function
large_settings() {
    clear
    echo "=========================================="
    echo "        БОЛЬШОЙ ОБЪЕМ ТЕСТОВ"
    echo "=========================================="
    echo
    echo "⚙️  Настройки:"
    echo "• Максимум тестов на тип: 200"
    echo "• Максимум комбинаций: 4"
    echo "• Максимум перестановок: 24"
    echo "• Таймаут: 600 секунд"
    echo
    echo "⚠️  ВНИМАНИЕ: Большой объем тестов может занять 30-60 минут!"
    if confirm "Продолжить? (y/n): "; then
        echo
        echo "🎯 Запуск полного тестирования с большим объемом..."
        php artisan test:dynamic-cap-system full --max-tests=200 --max-combinations=4 --max-permutations=24 --timeout=600
        echo
        echo "✅ Тестирование завершено!"
        pause
    fi
    show_menu
}

# Max settings function
max_settings() {
    clear
    echo "=========================================="
    echo "        МАКСИМАЛЬНЫЙ ОБЪЕМ ТЕСТОВ"
    echo "=========================================="
    echo
    echo "⚙️  Настройки:"
    echo "• Максимум тестов на тип: без ограничений"
    echo "• Максимум комбинаций: 5"
    echo "• Максимум перестановок: 120"
    echo "• Таймаут: 1800 секунд (30 минут)"
    echo
    echo "⚠️  ВНИМАНИЕ: Максимальный объем может создать десятки тысяч тестов!"
    echo "⚠️  Время выполнения: несколько часов!"
    if confirm "Вы уверены? (y/n): "; then
        echo
        echo "🎯 Запуск полного тестирования с максимальным объемом..."
        php artisan test:dynamic-cap-system full --max-tests=0 --max-combinations=5 --max-permutations=120 --timeout=1800
        echo
        echo "✅ Тестирование завершено!"
        pause
    fi
    show_menu
}

# Custom settings function
custom_settings() {
    clear
    echo "=========================================="
    echo "        ПОЛЬЗОВАТЕЛЬСКИЕ НАСТРОЙКИ"
    echo "=========================================="
    echo
    echo "Введите параметры тестирования:"
    echo
    get_input "Максимум тестов на тип (0=без ограничений): " max_tests
    get_input "Максимум комбинаций полей (1-5): " max_combinations
    get_input "Максимум перестановок (1-120): " max_permutations
    get_input "Таймаут в секундах (60-3600): " timeout
    echo
    echo "🎯 Запуск полного тестирования с пользовательскими настройками..."
    php artisan test:dynamic-cap-system full --max-tests=$max_tests --max-combinations=$max_combinations --max-permutations=$max_permutations --timeout=$timeout
    echo
    echo "✅ Тестирование завершено!"
    pause
    show_menu
}

# Help function
help() {
    clear
    echo "=========================================="
    echo "             СПРАВКА"
    echo "=========================================="
    echo
    echo "🚀 Система динамических тестов кап"
    echo
    echo "📋 Что тестируется:"
    echo "• 16 типов операций с капами"
    echo "• Все комбинации полей и значений"
    echo "• Все возможные перестановки порядка полей"
    echo "• Команды статуса (RUN, STOP, DELETE, RESTORE)"
    echo "• Сброс полей до значений по умолчанию"
    echo "• Валидация ошибочных случаев"
    echo
    echo "🎯 Покрываемые операции:"
    echo "• Сообщение > Создание > Одиночное/Групповое > Одна/Много кап"
    echo "• Сообщение > Обновление > Одиночное/Групповое > Одна/Много кап"
    echo "• Ответ > Обновление > Одиночное/Групповое > Одна/Много кап"
    echo "• Цитата > Обновление > Одиночное/Групповое > Одна/Много кап"
    echo
    echo "📊 Отчеты:"
    echo "• Краткий отчет (консоль)"
    echo "• Детальный отчет (файл)"
    echo "• Анализ ошибок (файл)"
    echo "• CSV экспорт (файл)"
    echo "• JSON отчет (файл)"
    echo
    echo "🔧 Команды Laravel:"
    echo "• php artisan test:dynamic-cap-system"
    echo "• php artisan test:dynamic-cap-system quick"
    echo "• php artisan test:dynamic-cap-system create"
    echo "• php artisan test:dynamic-cap-system update"
    echo "• php artisan test:dynamic-cap-system status"
    echo "• php artisan test:dynamic-cap-system stats"
    echo
    echo "📁 Файлы системы:"
    echo "• DynamicCapTestGenerator.php"
    echo "• DynamicCapTestEngine.php"
    echo "• DynamicCapCombinationGenerator.php"
    echo "• DynamicCapReportGenerator.php"
    echo "• dynamic_cap_test_runner.php"
    echo "• app/Console/Commands/TestDynamicCapSystem.php"
    echo
    pause
    show_menu
}

# Exit function
exit_script() {
    echo "До свидания!"
    pause
    exit 0
}

# Main execution
main() {
    # Check if we're in the correct directory
    if [ ! -f "artisan" ]; then
        echo "❌ Ошибка: Не найден файл artisan"
        echo "Пожалуйста, запустите скрипт из корневой директории Laravel проекта"
        exit 1
    fi
    
    # Check if PHP is available
    if ! command -v php &> /dev/null; then
        echo "❌ Ошибка: PHP не найден"
        echo "Пожалуйста, установите PHP или добавьте его в PATH"
        exit 1
    fi
    
    # Start the menu
    show_menu
}

# Run main function
main 