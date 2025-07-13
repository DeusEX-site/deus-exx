#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функции для цветного вывода
print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_header() {
    echo -e "${BLUE}$(printf '=%.0s' {1..50})${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}$(printf '=%.0s' {1..50})${NC}"
}

# Проверка файлов
check_laravel() {
    if [ ! -f "artisan" ]; then
        print_error "Файл artisan не найден. Запустите скрипт из корня Laravel проекта."
        exit 1
    fi
    
    if [ ! -f ".env" ]; then
        print_warning "Файл .env не найден"
    fi
}

# Очистка экрана и заголовок
clear
print_header "СОЗДАНИЕ ТЕСТОВЫХ ЧАТОВ"

# Проверки
print_info "Проверка окружения..."
check_laravel
print_success "Laravel проект найден"

# Получение количества чатов
echo
print_info "Сколько чатов создать? (по умолчанию: 100)"
read -r chat_count

# Если пользователь не ввел число, используем 100
if [[ ! "$chat_count" =~ ^[0-9]+$ ]]; then
    chat_count=100
fi

echo
print_info "Выберите типы операций:"
echo "  1. Все 16 типов (по умолчанию)"
echo "  2. Только создание кап"
echo "  3. Только обновление кап"
echo "  4. Только команды статуса"
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
read -r field_complexity

case "$field_complexity" in
    "2")
        combinations="advanced"
        print_info "Поля: Расширенные"
        ;;
    "3")
        combinations="full"
        print_info "Поля: Все"
        ;;
    *)
        combinations="basic"
        print_info "Поля: Базовые"
        ;;
esac

print_info "Будет создано: $chat_count чатов с типами операций: $operations, полями: $combinations"

# Запуск команды
echo
print_info "Запуск создания чатов с капами..."
print_warning "Все существующие данные будут удалены!"

php artisan test:create-chats $chat_count --operations=$operations --combinations=$combinations

if [ $? -eq 0 ]; then
    print_success "Чаты с капами успешно созданы!"
else
    print_error "Произошла ошибка при создании чатов"
    exit 1
fi

echo
print_info "Готово! Система создала:"
print_info "- $chat_count тестовых чатов"
print_info "- Сообщения с капами всех типов операций"
print_info "- Автоматический анализ и сохранение кап"
print_info "- ID чатов: от 1001 до $((1000 + chat_count))"
print_info "- Использованы типы операций: $operations"
print_info "- Использованы поля: $combinations"

echo
print_info "Система готова для тестирования кап!"
print_info "Нажмите Enter для завершения..."
read -r 