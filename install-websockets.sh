#!/bin/bash

echo "🚀 Установка Laravel WebSockets..."

# Установка пакета
echo "📦 Установка пакета beyondcode/laravel-websockets..."
composer require beyondcode/laravel-websockets pusher/pusher-php-server

# Публикация конфигурации
echo "⚙️ Публикация конфигурации..."
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="migrations"

# Запуск миграций
echo "🗃️ Запуск миграций..."
php artisan migrate

# Публикация конфигурационного файла
echo "📝 Публикация конфигурации WebSockets..."
php artisan vendor:publish --provider="BeyondCode\LaravelWebSockets\WebSocketsServiceProvider" --tag="config"

# Очистка кэша
echo "🧹 Очистка кэша..."
php artisan config:clear
php artisan cache:clear

echo "✅ Установка завершена!"
echo ""
echo "🔧 Следующие шаги:"
echo "1. Обновите .env файл (см. пример ниже)"
echo "2. Запустите: php artisan websockets:serve"
echo "3. Откройте ваш сайт и протестируйте"
echo ""
echo "📋 Пример .env:"
echo "BROADCAST_DRIVER=pusher"
echo "PUSHER_APP_ID=local"
echo "PUSHER_APP_KEY=local-key" 
echo "PUSHER_APP_SECRET=local-secret"
echo "PUSHER_HOST=127.0.0.1"
echo "PUSHER_PORT=6001"
echo "PUSHER_SCHEME=http"
echo "PUSHER_APP_CLUSTER=mt1" 