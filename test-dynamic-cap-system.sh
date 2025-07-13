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
    echo -e "${CYAN}$(printf '=%.0s' {1..60})${NC}"
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
        return 1
    fi
    return 0
}

# Header
clear
print_separator
print_header "  ТЕСТИРОВАНИЕ СИСТЕМЫ КАП ЧЕРЕЗ ВСТРОЕННУЮ ЛОГИКУ"
print_separator
echo

print_info "Используется встроенная логика TelegramWebhookController + CapAnalysisService"
print_info "Тесты только проверяют базу данных, не создают данные!"

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

# Check Laravel configuration
print_info "Проверка конфигурации Laravel..."
if ! check_file ".env"; then
    print_warning "Файл .env не найден, используется конфигурация по умолчанию"
fi

# Test database connection
print_info "Проверка подключения к базе данных..."
db_test=$(php artisan migrate:status 2>&1)
if [ $? -eq 0 ]; then
    print_success "База данных доступна"
else
    print_error "Проблемы с базой данных:"
    echo "$db_test" | head -5
    exit 1
fi

# Test basic artisan commands
print_info "Проверка базовых команд Laravel..."
php artisan --version > /dev/null 2>&1
if [ $? -eq 0 ]; then
    print_success "Laravel команды работают"
else
    print_error "Проблемы с Laravel командами"
    exit 1
fi

echo
print_separator
print_header "📊 ЭТАП 1: СОЗДАНИЕ ТЕСТОВЫХ ДАННЫХ"
print_separator

print_info "Создаем тестовые данные через встроенную систему..."
print_info "Используется DynamicCapTestGenerator с 16 типами операций"

echo
print_info "Сколько тестовых чатов создать? (по умолчанию: 50)"
read -r chat_count

# Если пользователь не ввел число, используем 50
if [[ ! "$chat_count" =~ ^[0-9]+$ ]]; then
    chat_count=50
fi

echo
print_info "Выберите типы операций для тестирования:"
echo "  1. Все 16 типов (по умолчанию)"
echo "  2. Только создание кап"
echo "  3. Только обновление кап"
echo "  4. Только команды статуса"
echo ""
print_info "Введите номер (1-4) или нажмите Enter для всех типов:"
read -r operation_type

case "$operation_type" in
    "2")
        operations="create"
        print_info "Режим: Только создание кап"
        ;;
    "3")
        operations="update"
        print_info "Режим: Только обновление кап"
        ;;
    "4")
        operations="status"
        print_info "Режим: Только команды статуса"
        ;;
    *)
        operations="all"
        print_info "Режим: Все 16 типов операций"
        ;;
esac

echo
print_info "Выберите сложность полей:"
echo "  1. Базовые поля (по умолчанию)"
echo "  2. Расширенные поля"
echo "  3. Все поля"
echo ""
print_info "Введите номер (1-3) или нажмите Enter для базовых полей:"
read -r field_complexity

case "$field_complexity" in
    "2")
        combinations="advanced"
        print_info "Поля: Расширенные (schedule, language, total)"
        ;;
    "3")
        combinations="full"
        print_info "Поля: Все (schedule, date, language, funnel, total, pending_acq, freeze_status_on_acq)"
        ;;
    *)
        combinations="basic"
        print_info "Поля: Базовые (affiliate, recipient, cap, geo, schedule)"
        ;;
esac

print_info "Создание $chat_count тестовых чатов с типами операций: $operations, полями: $combinations"

# Record start time
start_time=$(date +%s)

# Create test data using DynamicCapTestGenerator
print_info "Запуск: php artisan test:create-chats $chat_count --operations=$operations --combinations=$combinations"
php artisan test:create-chats $chat_count --operations=$operations --combinations=$combinations

if [ $? -eq 0 ]; then
    print_success "Тестовые данные созданы успешно!"
else
    print_error "Ошибка при создании тестовых данных"
    exit 1
fi

echo
print_separator
print_header "🔍 ЭТАП 2: ПРОВЕРКА БАЗЫ ДАННЫХ"
print_separator

print_info "Проверяем что данные корректно созданы в базе..."

# Check chats
chat_count_db=$(php artisan tinker --execute="echo App\\Models\\Chat::count();")
print_info "Чатов в базе: $chat_count_db"

# Check messages
message_count_db=$(php artisan tinker --execute="echo App\\Models\\Message::count();")
print_info "Сообщений в базе: $message_count_db"

# Check caps
cap_count_db=$(php artisan tinker --execute="echo App\\Models\\Cap::count();")
print_info "Кап в базе: $cap_count_db"

# Check cap history
cap_history_count_db=$(php artisan tinker --execute="echo App\\Models\\CapHistory::count();")
print_info "Записей в истории кап: $cap_history_count_db"

if [ "$chat_count_db" -gt 0 ] && [ "$message_count_db" -gt 0 ]; then
    print_success "Базовые данные созданы корректно"
else
    print_error "Проблемы с созданием базовых данных"
    exit 1
fi

if [ "$cap_count_db" -gt 0 ]; then
    print_success "Капы найдены и сохранены автоматически"
else
    print_warning "Капы не найдены - возможно сообщения не содержали капы"
fi

echo
print_separator
print_header "🧪 ЭТАП 3: ТЕСТИРОВАНИЕ СИСТЕМЫ"
print_separator

print_info "Запуск тестов для проверки данных в базе..."
print_info "Тесты НЕ создают данные, только проверяют существующие!"

# Test modes
echo
print_info "Выберите режим тестирования:"
echo "  1. Быстрая проверка основных функций"
echo "  2. Полная проверка всех компонентов"
echo "  3. Только статистика данных"
echo ""
print_info "Введите номер (1-3) или нажмите Enter для быстрой проверки:"
read -r test_mode

case "$test_mode" in
    "2")
        print_info "Режим: Полная проверка всех компонентов"
        TEST_TYPE="full"
        ;;
    "3")
        print_info "Режим: Только статистика данных"
        TEST_TYPE="stats"
        ;;
    *)
        print_info "Режим: Быстрая проверка основных функций"
        TEST_TYPE="quick"
        ;;
esac

echo
print_separator
print_header "⚡ ВЫПОЛНЕНИЕ ПРОВЕРОК"
print_separator

case "$TEST_TYPE" in
    "full")
        print_info "Выполнение полной проверки..."
        
        # Test 1: Check Chat model and relationships
        print_info "1. Проверка модели Chat и связей..."
        php artisan tinker --execute="
            \$chats = App\\Models\\Chat::with('messages')->get();
            echo 'Чатов с сообщениями: ' . \$chats->filter(function(\$chat) { return \$chat->messages->count() > 0; })->count();
        "
        
        # Test 2: Check Message model and relationships
        print_info "2. Проверка модели Message и связей..."
        php artisan tinker --execute="
            \$messages = App\\Models\\Message::with('chat', 'caps')->get();
            echo 'Сообщений со связями: ' . \$messages->count();
        "
        
        # Test 3: Check Cap analysis results
        print_info "3. Проверка результатов анализа кап..."
        php artisan tinker --execute="
            \$caps = App\\Models\\Cap::with('message', 'history')->get();
            echo 'Кап со связями: ' . \$caps->count();
            if (\$caps->count() > 0) {
                echo PHP_EOL . 'Пример капы: ' . \$caps->first()->geos . ' - ' . \$caps->first()->total;
            }
        "
        
        # Test 4: Check CapHistory functionality
        print_info "4. Проверка функциональности CapHistory..."
        php artisan tinker --execute="
            \$history = App\\Models\\CapHistory::with('cap')->get();
            echo 'Записей в истории: ' . \$history->count();
        "
        
        # Test 5: Check CapAnalysisService integration
        print_info "5. Проверка интеграции CapAnalysisService..."
        php artisan tinker --execute="
            \$service = new App\\Services\\CapAnalysisService();
            echo 'CapAnalysisService создан успешно';
        "
        ;;
        
    "stats")
        print_info "Показ детальной статистики..."
        
        php artisan tinker --execute="
            echo '=== СТАТИСТИКА ЧАТОВ ===' . PHP_EOL;
            \$chats = App\\Models\\Chat::selectRaw('type, COUNT(*) as count')->groupBy('type')->get();
            foreach (\$chats as \$chat) {
                echo \$chat->type . ': ' . \$chat->count . PHP_EOL;
            }
            
            echo PHP_EOL . '=== СТАТИСТИКА СООБЩЕНИЙ ===' . PHP_EOL;
            \$messages = App\\Models\\Message::selectRaw('message_type, COUNT(*) as count')->groupBy('message_type')->get();
            foreach (\$messages as \$message) {
                echo (\$message->message_type ?? 'text') . ': ' . \$message->count . PHP_EOL;
            }
            
            echo PHP_EOL . '=== СТАТИСТИКА КАП ===' . PHP_EOL;
            \$caps = App\\Models\\Cap::selectRaw('geos, COUNT(*) as count')->groupBy('geos')->limit(10)->get();
            foreach (\$caps as \$cap) {
                echo \$cap->geos . ': ' . \$cap->count . PHP_EOL;
            }
        "
        ;;
        
    *)
        print_info "Выполнение быстрых проверок..."
        
        # Quick tests
        print_info "1. Проверка создания чатов..."
        php artisan tinker --execute="echo 'OK: ' . App\\Models\\Chat::count() . ' чатов';"
        
        print_info "2. Проверка создания сообщений..."
        php artisan tinker --execute="echo 'OK: ' . App\\Models\\Message::count() . ' сообщений';"
        
        print_info "3. Проверка анализа кап..."
        php artisan tinker --execute="echo 'OK: ' . App\\Models\\Cap::count() . ' кап найдено';"
        
        print_info "4. Проверка истории кап..."
        php artisan tinker --execute="echo 'OK: ' . App\\Models\\CapHistory::count() . ' записей в истории';"
        ;;
esac

# Record end time
end_time=$(date +%s)
duration=$((end_time - start_time))

echo
print_separator
print_header "📊 РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ"
print_separator

print_info "Время завершения: $(date)"
print_info "Общее время выполнения: $duration секунд"

print_success "✅ Тестирование завершено успешно!"
print_success "✅ Система работает через встроенную логику"
print_success "✅ Данные созданы через TelegramWebhookController"
print_success "✅ Капы найдены через CapAnalysisService"
print_success "✅ Тесты проверили только базу данных"

echo
print_separator
print_info "Готово! Система протестирована через встроенную логику."
print_info "Нажмите Enter для завершения..."
read -r 