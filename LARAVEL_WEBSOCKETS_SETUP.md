# 🚀 Laravel WebSockets - Самостоятельный сервер

## 🎯 Что это?

**Laravel WebSockets** - это встроенный веб-сокет сервер для Laravel, который:
- ✅ Работает на том же сервере что и Laravel
- ✅ Не требует Redis, Pusher или других внешних сервисов
- ✅ Совместим с Pusher API
- ✅ Имеет веб-интерфейс для мониторинга
- ✅ Работает на любом хостинге с SSH доступом

---

## 🛠️ Быстрая установка

### 1. Запустите установку:
```bash
chmod +x websocket-server.sh
./websocket-server.sh install
```

### 2. Настройте .env файл:
```env
# WebSocket настройки
BROADCAST_DRIVER=pusher

# Laravel WebSockets (локальный сервер)
PUSHER_APP_ID=local
PUSHER_APP_KEY=local-key
PUSHER_APP_SECRET=local-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http
PUSHER_APP_CLUSTER=mt1

# НЕ нужен Redis!
# REDIS_HOST - можно оставить как есть или убрать
```

### 3. Запустите сервер:
```bash
# Интерактивный запуск (для тестирования)
./websocket-server.sh start

# Или в фоне (для продакшена)
./websocket-server.sh background
```

### 4. Откройте ваш сайт и протестируйте!

---

## 🔧 Управление сервером

### Основные команды:
```bash
# Запуск сервера
./websocket-server.sh start

# Запуск в фоне
./websocket-server.sh background

# Проверка статуса
./websocket-server.sh status

# Остановка
./websocket-server.sh stop

# Перезапуск
./websocket-server.sh restart

# Просмотр логов
./websocket-server.sh logs
```

---

## 🌐 Настройка для разных хостингов

### **VPS / Cloud серверы**
Идеально! Полная поддержка. Используйте команды выше.

### **Shared хостинг с SSH**
Работает, но может потребоваться:
```bash
# Если нет прав на pkill
./websocket-server.sh start  # только интерактивный режим

# Для остановки используйте Ctrl+C
```

### **Хостинг без SSH**
К сожалению, не получится. Используйте внешний Pusher или режим polling.

---

## 📊 Мониторинг

### WebSocket Dashboard:
Откройте в браузере: `http://ваш-сайт:6001/app/local-key`

Здесь вы увидите:
- 📈 Статистика подключений
- 💬 Активные каналы
- 📊 Количество сообщений
- 🔌 Список подключенных клиентов

---

## 🐛 Решение проблем

### Ошибка: "Connection refused"
```bash
# Проверьте, запущен ли сервер
./websocket-server.sh status

# Запустите если не запущен
./websocket-server.sh start
```

### Ошибка: "Port 6001 already in use"
```bash
# Найдите процесс на порту 6001
sudo netstat -tulpn | grep 6001

# Остановите старый процесс
./websocket-server.sh stop

# Запустите заново
./websocket-server.sh start
```

### Ошибка: "Permission denied"
```bash
# Дайте права на выполнение
chmod +x websocket-server.sh
chmod +x install-websockets.sh
```

### Сервер запускается, но веб-сокеты не работают
1. Проверьте firewall (порт 6001 должен быть открыт)
2. Убедитесь что .env настроен правильно
3. Очистите кэш: `php artisan config:clear`

---

## 🚀 Продакшн настройки

### 1. Автозапуск сервера
Добавьте в crontab:
```bash
crontab -e

# Добавьте строку (каждую минуту проверяет и запускает если нужно)
* * * * * cd /path/to/your/project && ./websocket-server.sh status > /dev/null || ./websocket-server.sh background
```

### 2. Systemd сервис (Ubuntu/CentOS)
Создайте файл `/etc/systemd/system/laravel-websockets.service`:
```ini
[Unit]
Description=Laravel WebSockets
After=network.target

[Service]
Type=simple
User=www-data
WorkingDirectory=/path/to/your/project
ExecStart=/usr/bin/php artisan websockets:serve
Restart=always

[Install]
WantedBy=multi-user.target
```

Запустите:
```bash
sudo systemctl daemon-reload
sudo systemctl enable laravel-websockets
sudo systemctl start laravel-websockets
```

### 3. Supervisor (для более стабильной работы)
```bash
sudo apt install supervisor

# Создайте конфиг /etc/supervisor/conf.d/laravel-websockets.conf
[program:laravel-websockets]
command=php artisan websockets:serve
directory=/path/to/your/project
autostart=true
autorestart=true
user=www-data
```

---

## 📈 Оптимизация

### Настройки для высокой нагрузки:
В `config/websockets.php` (после установки):
```php
'max_request_size_in_kb' => 250,
'max_connections' => 1000,
'max_channels_per_app' => 100000,
'ping_interval' => 30,
'ping_timeout' => 10,
```

### Мониторинг производительности:
```bash
# Просмотр активных подключений
./websocket-server.sh logs | grep "connection"

# Мониторинг памяти
top -p $(pgrep -f websockets:serve)
```

---

## ✅ Преимущества Laravel WebSockets

- 🚀 **Быстро**: Нет сетевых задержек к внешним сервисам
- 💰 **Бесплатно**: Никаких платных подписок
- 🔒 **Безопасно**: Все данные остаются на вашем сервере
- 📊 **Мониторинг**: Встроенный dashboard
- 🛠️ **Настраиваемо**: Полный контроль над сервером

---

## 🎉 Готово!

Теперь у вас есть полноценный веб-сокет сервер, работающий прямо в Laravel без внешних зависимостей! 