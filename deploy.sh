#!/bin/bash

echo "🚀 Начинаем развертывание..."

# Получаем последние изменения
echo "📥 Получаем изменения из Git..."
git pull origin main

# Устанавливаем/обновляем зависимости
echo "📦 Обновляем PHP зависимости..."
composer install --optimize-autoloader --no-dev

# Очищаем кэш
echo "🧹 Очищаем кэш..."
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear

# Запускаем миграции (если есть новые)
echo "🗄️ Запускаем миграции..."
php artisan migrate --force

# Кэшируем для продакшена
echo "⚡ Кэшируем конфигурацию..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Развертывание завершено!" 