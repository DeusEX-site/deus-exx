#!/bin/bash

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Функция для вывода цветного текста
print_color() {
    printf "${1}${2}${NC}\n"
}

# Проверка аргументов
case "$1" in
    "start")
        print_color $GREEN "🚀 Запуск WebSocket сервера..."
        print_color $YELLOW "📡 Сервер будет доступен на: http://127.0.0.1:6001"
        print_color $YELLOW "🌐 Dashboard: http://127.0.0.1:6001/app/local-key"
        echo ""
        php artisan websockets:serve
        ;;
    "stop")
        print_color $RED "🛑 Остановка WebSocket сервера..."
        pkill -f "websockets:serve"
        print_color $GREEN "✅ Сервер остановлен"
        ;;
    "restart")
        print_color $YELLOW "🔄 Перезапуск WebSocket сервера..."
        pkill -f "websockets:serve"
        sleep 2
        print_color $GREEN "🚀 Запуск сервера..."
        nohup php artisan websockets:serve > websocket.log 2>&1 &
        print_color $GREEN "✅ Сервер перезапущен в фоне"
        print_color $BLUE "📄 Логи: tail -f websocket.log"
        ;;
    "status")
        print_color $BLUE "📊 Статус WebSocket сервера:"
        if pgrep -f "websockets:serve" > /dev/null; then
            print_color $GREEN "✅ Сервер запущен"
            print_color $YELLOW "🔗 PID: $(pgrep -f 'websockets:serve')"
        else
            print_color $RED "❌ Сервер не запущен"
        fi
        ;;
    "logs")
        print_color $BLUE "📄 Просмотр логов WebSocket сервера:"
        if [ -f "websocket.log" ]; then
            tail -f websocket.log
        else
            print_color $RED "❌ Файл логов не найден"
            print_color $YELLOW "💡 Запустите сервер с помощью: ./websocket-server.sh restart"
        fi
        ;;
    "background")
        print_color $GREEN "🚀 Запуск WebSocket сервера в фоне..."
        nohup php artisan websockets:serve > websocket.log 2>&1 &
        print_color $GREEN "✅ Сервер запущен в фоне"
        print_color $BLUE "📄 Логи: tail -f websocket.log"
        print_color $YELLOW "🔗 PID: $!"
        ;;
    "install")
        print_color $BLUE "📦 Установка Laravel WebSockets..."
        composer require beyondcode/laravel-websockets pusher/pusher-php-server
        php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"
        php artisan migrate
        php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"
        php artisan config:clear
        print_color $GREEN "✅ Установка завершена!"
        ;;
    *)
        print_color $BLUE "🔧 Управление Laravel WebSocket сервером"
        echo ""
        print_color $YELLOW "Использование: ./websocket-server.sh [команда]"
        echo ""
        print_color $GREEN "Доступные команды:"
        echo "  start       - Запустить сервер (интерактивно)"
        echo "  stop        - Остановить сервер"
        echo "  restart     - Перезапустить сервер в фоне"
        echo "  background  - Запустить сервер в фоне"
        echo "  status      - Проверить статус сервера"
        echo "  logs        - Посмотреть логи сервера"
        echo "  install     - Установить Laravel WebSockets"
        echo ""
        print_color $BLUE "Примеры:"
        print_color $YELLOW "  ./websocket-server.sh start"
        print_color $YELLOW "  ./websocket-server.sh background"
        print_color $YELLOW "  ./websocket-server.sh status"
        ;;
esac 