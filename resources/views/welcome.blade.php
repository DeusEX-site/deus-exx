<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Laravel WebSocket Demo</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        <style>
            * {
                box-sizing: border-box;
                margin: 0;
                padding: 0;
            }
            
            body {
                font-family: 'Figtree', sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                color: #333;
            }
            
            .container {
                max-width: 1200px;
                margin: 0 auto;
                padding: 20px;
            }
            
            .header {
                text-align: center;
                color: white;
                margin-bottom: 30px;
            }
            
            .header h1 {
                font-size: 2.5rem;
                font-weight: 600;
                margin-bottom: 10px;
                text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            }
            
            .header p {
                font-size: 1.1rem;
                opacity: 0.9;
            }
            
            .auth-links {
                position: absolute;
                top: 20px;
                right: 20px;
            }
            
            .auth-links a {
                color: white;
                text-decoration: none;
                margin-left: 20px;
                padding: 8px 16px;
                border-radius: 20px;
                background: rgba(255,255,255,0.2);
                transition: all 0.3s ease;
            }
            
            .auth-links a:hover {
                background: rgba(255,255,255,0.3);
                transform: translateY(-2px);
            }
            
            .main-content {
                display: grid;
                grid-template-columns: 1fr 1fr;
                gap: 30px;
                margin-top: 20px;
            }
            
            .messages-section, .controls-section {
                background: white;
                border-radius: 15px;
                padding: 25px;
                box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            }
            
            .section-title {
                font-size: 1.5rem;
                font-weight: 600;
                margin-bottom: 20px;
                color: #4a5568;
                border-bottom: 2px solid #e2e8f0;
                padding-bottom: 10px;
            }
            
            .messages-container {
                height: 400px;
                overflow-y: auto;
                border: 2px solid #e2e8f0;
                border-radius: 10px;
                padding: 15px;
                background: #f8fafc;
            }
            
            .message {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
                padding: 12px 16px;
                border-radius: 20px;
                margin-bottom: 10px;
                box-shadow: 0 2px 10px rgba(79, 172, 254, 0.3);
                animation: slideIn 0.3s ease-out;
            }
            
            .message:nth-child(even) {
                background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
                color: #2d3748;
                box-shadow: 0 2px 10px rgba(168, 237, 234, 0.3);
            }
            
            .message-header {
                display: flex;
                justify-content: space-between;
                font-size: 0.85rem;
                opacity: 0.8;
                margin-bottom: 5px;
            }
            
            .message-text {
                font-size: 1rem;
                font-weight: 500;
            }
            
            .test-controls {
                margin-bottom: 25px;
            }
            
            .test-form {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            
            .form-group {
                display: flex;
                flex-direction: column;
            }
            
            .form-group label {
                font-weight: 500;
                margin-bottom: 5px;
                color: #4a5568;
            }
            
            .form-group input {
                padding: 12px;
                border: 2px solid #e2e8f0;
                border-radius: 8px;
                font-size: 1rem;
                transition: border-color 0.3s ease;
            }
            
            .form-group input:focus {
                outline: none;
                border-color: #4facfe;
                box-shadow: 0 0 0 3px rgba(79, 172, 254, 0.1);
            }
            
            .btn {
                padding: 12px 24px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border: none;
                border-radius: 8px;
                font-size: 1rem;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
            }
            
            .btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
            }
            
            .status {
                padding: 12px;
                border-radius: 8px;
                margin-top: 15px;
                font-weight: 500;
            }
            
            .status.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            
            .status.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            
            .connection-status {
                display: flex;
                align-items: center;
                gap: 10px;
                margin-bottom: 20px;
            }
            
            .status-dot {
                width: 12px;
                height: 12px;
                border-radius: 50%;
                background: #dc3545;
                animation: pulse 2s infinite;
            }
            
            .status-dot.connected {
                background: #28a745;
            }
            
            @keyframes slideIn {
                from {
                    transform: translateX(-20px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes pulse {
                0% { transform: scale(1); }
                50% { transform: scale(1.1); }
                100% { transform: scale(1); }
            }
            
            @media (max-width: 768px) {
                .main-content {
                    grid-template-columns: 1fr;
                    gap: 20px;
                }
                
                .header h1 {
                    font-size: 2rem;
                }
            }
        </style>
    </head>
    <body>
        @if (Route::has('login'))
            <div class="auth-links">
                @auth
                    <a href="{{ url('/dashboard') }}">Dashboard</a>
                @else
                    <a href="{{ route('login') }}">Войти</a>
                    @if (Route::has('register'))
                        <a href="{{ route('register') }}">Регистрация</a>
                    @endif
                @endauth
            </div>
        @endif

        <div class="container">
            <div class="header">
                <h1>🚀 Laravel WebSocket Demo</h1>
                <p>Сообщения в реальном времени с веб-сокетами</p>
            </div>

            <div class="main-content">
                <!-- Секция сообщений -->
                <div class="messages-section">
                    <h2 class="section-title">📨 Сообщения в реальном времени</h2>
                    
                    <div class="connection-status">
                        <div class="status-dot" id="statusDot"></div>
                        <span id="connectionStatus">Подключение...</span>
                    </div>
                    
                    <div class="messages-container" id="messagesContainer">
                        <div class="message">
                            <div class="message-header">
                                <span>Система</span>
                                <span>{{ now()->format('H:i:s') }}</span>
                            </div>
                            <div class="message-text">Добро пожаловать! Ожидание сообщений...</div>
                        </div>
                    </div>
                </div>
                
                <!-- Секция управления -->
                <div class="controls-section">
                    <h2 class="section-title">🎛️ Управление</h2>
                    
                    <div class="test-controls">
                        <form class="test-form" id="messageForm">
                            <div class="form-group">
                                <label for="messageInput">Сообщение:</label>
                                <input type="text" 
                                       id="messageInput" 
                                       name="message" 
                                       placeholder="Введите ваше сообщение..." 
                                       required>
                            </div>
                            
                            <button type="submit" class="btn">📤 Отправить сообщение</button>
                        </form>
                        
                        <div id="sendStatus"></div>
                    </div>
                    
                    <div class="test-controls">
                        <h3 style="margin-bottom: 15px; color: #4a5568;">Быстрые тесты:</h3>
                        <button class="btn" onclick="sendTestMessage('Привет всем! 👋')">
                            Тест 1: Приветствие
                        </button>
                        <br><br>
                        <button class="btn" onclick="sendTestMessage('Тестирую веб-сокеты 🚀')">
                            Тест 2: Веб-сокеты
                        </button>
                        <br><br>
                        <button class="btn" onclick="sendTestMessage('Laravel Broadcasting работает! 💪')">
                            Тест 3: Broadcasting
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pusher CDN -->
        <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
        
        <script>
            // Инициализация переменных
            let pusher;
            let channel;
            let messagesContainer = document.getElementById('messagesContainer');
            let statusDot = document.getElementById('statusDot');
            let connectionStatus = document.getElementById('connectionStatus');
            let messageForm = document.getElementById('messageForm');
            let sendStatus = document.getElementById('sendStatus');
            
            // Инициализация Pusher (используем локальные настройки)
            function initializePusher() {
                try {
                    pusher = new Pusher('local-key', {
                        cluster: 'mt1',
                        wsHost: '{{ request()->getHost() }}',
                        wsPort: 6001,
                        wssPort: 6001,
                        forceTLS: false,
                        enabledTransports: ['ws', 'wss'],
                        disableStats: true,
                        authEndpoint: '/broadcasting/auth'
                    });
                    
                    // Подключение к каналу
                    channel = pusher.subscribe('messages');
                    
                    // Обработчики событий Pusher
                    pusher.connection.bind('connected', function() {
                        statusDot.classList.add('connected');
                        connectionStatus.textContent = 'Подключено к веб-сокету!';
                        addSystemMessage('✅ Подключение к веб-сокету установлено!');
                    });
                    
                    pusher.connection.bind('disconnected', function() {
                        statusDot.classList.remove('connected');
                        connectionStatus.textContent = 'Отключено';
                        addSystemMessage('❌ Соединение разорвано');
                    });
                    
                    pusher.connection.bind('error', function(err) {
                        statusDot.classList.remove('connected');
                        connectionStatus.textContent = 'Ошибка подключения';
                        addSystemMessage('❌ Ошибка: ' + (err.message || 'Неизвестная ошибка'));
                    });
                    
                    // Слушаем события сообщений
                    channel.bind('message.sent', function(data) {
                        addMessage(data.user, data.message, data.timestamp);
                    });
                    
                } catch (error) {
                    console.error('Ошибка инициализации Pusher:', error);
                    statusDot.classList.remove('connected');
                    connectionStatus.textContent = 'Ошибка инициализации';
                    addSystemMessage('❌ Не удалось инициализировать веб-сокет');
                    
                    // Fallback - попробуем использовать polling
                    startPolling();
                }
            }
            
            // Альтернативный метод - polling (если веб-сокеты не работают)
            function startPolling() {
                addSystemMessage('🔄 Переключение на режим опроса...');
                // Здесь можно добавить логику polling'а при необходимости
            }
            
            // Добавление сообщения в контейнер
            function addMessage(user, message, timestamp) {
                let messageDiv = document.createElement('div');
                messageDiv.className = 'message';
                messageDiv.innerHTML = `
                    <div class="message-header">
                        <span>${user}</span>
                        <span>${timestamp}</span>
                    </div>
                    <div class="message-text">${message}</div>
                `;
                
                messagesContainer.appendChild(messageDiv);
                messagesContainer.scrollTop = messagesContainer.scrollHeight;
            }
            
            // Добавление системного сообщения
            function addSystemMessage(message) {
                let now = new Date();
                let timestamp = now.getHours().toString().padStart(2, '0') + ':' + 
                               now.getMinutes().toString().padStart(2, '0') + ':' + 
                               now.getSeconds().toString().padStart(2, '0');
                
                addMessage('Система', message, timestamp);
            }
            
            // Отправка сообщения через API
            async function sendMessage(message) {
                try {
                    showSendStatus('⏳ Отправка...', 'info');
                    
                    let response = await fetch('/send-message?message=' + encodeURIComponent(message), {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });
                    
                    let data = await response.json();
                    
                    if (data.status === 'success') {
                        showSendStatus('✅ ' + data.message, 'success');
                    } else {
                        showSendStatus('❌ Ошибка отправки', 'error');
                    }
                    
                } catch (error) {
                    console.error('Ошибка отправки:', error);
                    showSendStatus('❌ Ошибка: ' + error.message, 'error');
                }
            }
            
            // Показать статус отправки
            function showSendStatus(message, type) {
                sendStatus.textContent = message;
                sendStatus.className = 'status ' + type;
                
                setTimeout(() => {
                    sendStatus.textContent = '';
                    sendStatus.className = '';
                }, 3000);
            }
            
            // Отправка тестового сообщения
            function sendTestMessage(message) {
                sendMessage(message);
            }
            
            // Обработчик формы
            messageForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                let messageInput = document.getElementById('messageInput');
                let message = messageInput.value.trim();
                
                if (message) {
                    sendMessage(message);
                    messageInput.value = '';
                }
            });
            
            // Инициализация при загрузке страницы
            document.addEventListener('DOMContentLoaded', function() {
                initializePusher();
                
                // Добавляем приветственное сообщение
                setTimeout(() => {
                    addSystemMessage('🎉 Страница загружена! Попробуйте отправить сообщение.');
                }, 1000);
            });
        </script>
    </body>
</html>
