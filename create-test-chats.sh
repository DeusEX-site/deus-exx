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

print_info "Будет создано: $chat_count чатов"

# Запуск команды
echo
print_info "Запуск создания чатов..."
print_warning "Все существующие чаты будут удалены!"

php artisan test:create-chats $chat_count

if [ $? -eq 0 ]; then
    print_success "Чаты успешно созданы!"
else
    print_error "Произошла ошибка при создании чатов"
    exit 1
fi

echo
print_info "Готово! Теперь в базе данных $chat_count тестовых чатов."
print_info "ID чатов: от 1001 до $((1000 + chat_count))"
print_info "Первые 10 чатов добавлены в топ-10"

echo
print_info "Нажмите Enter для завершения..."
read -r 