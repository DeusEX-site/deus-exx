#!/bin/bash

# Enable UTF-8 encoding
export LANG=en_US.UTF-8
export LC_ALL=en_US.UTF-8

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Function to print colored messages
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_header() {
    echo -e "${PURPLE}$1${NC}"
}

print_separator() {
    echo -e "${CYAN}$(printf '=%.0s' {1..50})${NC}"
}

# Function to check file existence
check_file() {
    if [ ! -f "$1" ]; then
        print_error "Не найден файл: $1"
        return 1
    fi
    return 0
}

# Function to check command availability
check_command() {
    if ! command -v "$1" &> /dev/null; then
        print_error "Команда '$1' не найдена"
        print_info "Установите $1 или добавьте его в PATH"
        return 1
    fi
    return 0
}

# Header
clear
print_separator
print_header "     СИСТЕМА ДИНАМИЧЕСКИХ ТЕСТОВ КАП"
print_separator
echo

print_info "Инициализация системы тестирования..."

# Check if we're in the correct directory
print_info "Проверка рабочей директории..."
if ! check_file "artisan"; then
    print_error "Запустите скрипт из корневой директории Laravel проекта"
    exit 1
fi
print_success "Найден файл artisan"

# Check if PHP is available
print_info "Проверка доступности PHP..."
if ! check_command "php"; then
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_success "PHP найден (версия: $PHP_VERSION)"

# Check if Laravel is properly configured
print_info "Проверка конфигурации Laravel..."
if ! check_file ".env"; then
    print_warning "Файл .env не найден, используется конфигурация по умолчанию"
fi

if ! check_file "composer.json"; then
    print_error "Файл composer.json не найден"
    exit 1
fi
print_success "Laravel проект настроен"

# Check required test files
print_info "Проверка файлов системы тестирования..."
required_files=(
    "DynamicCapTestGenerator.php"
    "DynamicCapTestEngine.php" 
    "DynamicCapCombinationGenerator.php"
    "DynamicCapReportGenerator.php"
    "dynamic_cap_test_runner.php"
    "app/Console/Commands/TestDynamicCapSystem.php"
)

missing_files=0
for file in "${required_files[@]}"; do
    if check_file "$file"; then
        print_success "✓ $file"
    else
        missing_files=$((missing_files + 1))
    fi
done

if [ $missing_files -gt 0 ]; then
    print_error "Отсутствует $missing_files файл(ов) системы тестирования"
    print_info "Убедитесь, что все файлы системы динамических тестов находятся в проекте"
    exit 1
fi

print_success "Все файлы системы тестирования найдены"

echo
print_separator
print_header "🚀 ЗАПУСК ПОЛНОГО ТЕСТИРОВАНИЯ"
print_separator
echo

print_info "Конфигурация тестирования:"
echo "   📋 Тип: Полное тестирование всех операций"
echo "   📝 Вывод: Подробный в реальном времени"
echo "   ⏸️  Пауза: На каждой ошибке"
echo "   🔄 Очистка: Автоматическая после завершения"
echo "   ⏱️  Таймаут: 30 минут"

echo
print_warning "ВНИМАНИЕ: Полное тестирование может занять продолжительное время!"
print_info "Система будет тестировать все 16 типов операций с капами"

echo
print_info "Выберите режим тестирования:"
echo "  1. Полное тестирование (по умолчанию)"
echo "  2. Быстрая диагностика"
echo "  3. Только проверка системы"
echo ""
print_info "Введите номер (1-3) или нажмите Enter для полного тестирования:"
read -r test_mode

case "$test_mode" in
    "2")
        echo
        print_info "Режим: Быстрая диагностика"
        TEST_COMMAND="php artisan test:dynamic-cap-system quick --detailed"
        ;;
    "3")
        echo
        print_info "Режим: Только проверка системы"
        TEST_COMMAND="php artisan test:dynamic-cap-system stats --detailed"
        ;;
    *)
        echo
        print_info "Режим: Полное тестирование"
        TEST_COMMAND="php artisan test:dynamic-cap-system full --detailed --pause-on-error"
        ;;
esac

echo
print_separator
print_header "⚡ ВЫПОЛНЕНИЕ ТЕСТОВ"
print_separator

# Record start time
start_time=$(date +%s)
print_info "Время начала: $(date)"

echo
print_info "Предварительная диагностика..."

# Test database connection
print_info "Проверка подключения к базе данных..."
db_test=$(php artisan migrate:status 2>&1)
if [ $? -eq 0 ]; then
    print_success "База данных доступна"
else
    print_warning "Проблемы с базой данных:"
    echo "$db_test" | head -5
fi

# Test basic artisan command
print_info "Проверка базовых команд Laravel..."
php artisan --version > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Laravel команды работают"
else
    print_error "Проблемы с Laravel командами"
    exit 1
fi

# Check if our test command exists
print_info "Проверка команды test:dynamic-cap-system..."
php artisan list | grep "test:dynamic-cap-system" > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Команда test:dynamic-cap-system найдена"
else
    print_error "Команда test:dynamic-cap-system не найдена"
    print_info "Доступные команды test:"
    php artisan list | grep "test:"
    exit 1
fi

# Test with help first
print_info "Проверка справки команды..."
php artisan test:dynamic-cap-system --help > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Справка команды работает"
else
    print_error "Проблемы со справкой команды"
    php artisan test:dynamic-cap-system --help
    exit 1
fi

echo
print_info "Запуск системы динамических тестов..."

# Run the tests and capture exit code with better error handling
set -o pipefail
print_info "Выполняется: $TEST_COMMAND"
$TEST_COMMAND 2>&1 | tee /tmp/dynamic_cap_test.log
exit_code=${PIPESTATUS[0]}

# Record end time
end_time=$(date +%s)
duration=$((end_time - start_time))

echo
print_separator
print_header "📊 РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ"
print_separator

print_info "Время завершения: $(date)"
print_info "Общее время выполнения: $duration секунд"

if [ $exit_code -eq 0 ]; then
    print_success "Тестирование завершено успешно!"
    print_success "Все компоненты системы работают корректно"
else
    print_error "Тестирование завершено с ошибками (код: $exit_code)"
    
    case $exit_code in
        1)
            print_warning "Общая ошибка - проверьте конфигурацию"
            ;;
        255)
            print_warning "Критическая ошибка PHP/Laravel - проверьте код и зависимости"
            ;;
        127)
            print_warning "Команда не найдена"
            ;;
        *)
            print_warning "Неизвестная ошибка с кодом $exit_code"
            ;;
    esac
    
    if [ -f "/tmp/dynamic_cap_test.log" ]; then
        echo
        print_info "Последние строки лога:"
        print_separator
        tail -20 /tmp/dynamic_cap_test.log | while IFS= read -r line; do
            echo "  $line"
        done
        print_separator
        print_info "Полный лог сохранен в: /tmp/dynamic_cap_test.log"
    fi
    
    echo
    print_info "Попробуйте диагностику:"
    echo "  1. php artisan test:dynamic-cap-system --help"
    echo "  2. php artisan migrate:status"
    echo "  3. php artisan config:cache"
    echo "  4. composer dump-autoload"
fi

echo
print_separator
if [ $exit_code -ne 0 ]; then
    print_info "Хотите запустить диагностику? (y/n)"
    read -r run_diag
    if [[ "$run_diag" =~ ^[Yy]$ ]]; then
        echo
        print_info "Запуск диагностики..."
        print_separator
        echo "📋 Проверка автозагрузки классов:"
        composer dump-autoload -q
        print_success "Автозагрузка обновлена"
        
        echo
        echo "📋 Очистка кэша конфигурации:"
        php artisan config:clear
        php artisan cache:clear
        print_success "Кэш очищен"
        
        echo
        echo "📋 Проверка миграций:"
        php artisan migrate:status | head -10
        
        echo
        print_info "Попробуйте запустить тест еще раз"
    fi
fi

print_separator
print_info "Нажмите Enter для завершения..."
read -r 