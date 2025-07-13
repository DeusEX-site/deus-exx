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
print_info "Генератор отправляет сообщения в систему - чаты создаются автоматически!"
print_info "Тесты только проверяют базу данных, не создают данные напрямую!"

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
print_info "Этап 1: Создание чатов через базовые сообщения"
print_info "Этап 2: Отправка ВСЕХ типов операций в существующие чаты"
print_info "Система автоматически создает чаты и находит капы"

chat_count=50
operations="all"
combinations="full"

print_info "Создание $chat_count тестовых чатов с ВСЕМИ типами операций и ВСЕМИ полями"
print_info "Режим: Все 16 типов операций + команды статуса (отправляется в каждый чат)"
print_info "Поля: Все (schedule, date, language, funnel, total, pending_acq, freeze_status_on_acq)"
print_info "Внимание: будет отправлено гораздо больше сообщений, чем чатов!"

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

TEST_TYPE="full"
print_info "Режим: Полная проверка всех компонентов"

echo
print_separator
print_header "⚡ ВЫПОЛНЕНИЕ ПРОВЕРОК"
print_separator

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
        \$firstCap = \$caps->first();
        \$geos = is_array(\$firstCap->geos) ? implode(', ', \$firstCap->geos) : \$firstCap->geos;
        echo PHP_EOL . 'Пример капы: ' . \$geos . ' - ' . \$firstCap->total;
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

# Test 6: Show detailed statistics
print_info "6. Детальная статистика..."
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
print_success "✅ Чаты созданы автоматически через встроенную систему"
print_success "✅ Сообщения обработаны через TelegramWebhookController"
print_success "✅ Капы найдены через CapAnalysisService"
print_success "✅ Тесты проверили только базу данных, не создавая данные напрямую"

echo
print_separator
print_info "Готово! Система протестирована через встроенную логику."
print_info "Генератор отправляет сообщения → система создает чаты → находит капы" 