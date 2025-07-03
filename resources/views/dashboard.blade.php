<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Telegram Чаты</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Figtree', sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
            min-height: 100vh;
            color: #e2e8f0;
        }
        
        .header {
            background: rgba(15, 15, 35, 0.8);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .header .user-info {
            color: rgba(255, 255, 255, 0.9);
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .header a, .header button {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .header a:hover, .header button:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .container {
            padding: 2rem;
            width: 100%;
            margin: 0;
        }
        
        .telegram-controls {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: rgba(30, 30, 60, 0.9);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .telegram-controls h3 {
            color: #e2e8f0;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        
        .control-buttons {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .control-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .control-btn.primary {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: white;
        }
        
        .control-btn.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .control-btn.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .control-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .control-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .chat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .chat-window {
            background: rgba(30, 30, 60, 0.9);
            border-radius: 1rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.4);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            height: 400px;
            display: flex;
            flex-direction: column;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: white;
            padding: 1rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .chat-header.private {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
        }
        
        .chat-header.group {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }
        
        .chat-header.supergroup {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
        }
        
        .chat-header.channel {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
        }
        
        .chat-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 14px;
        }
        
        .chat-info {
            flex: 1;
        }
        
        .chat-info h3 {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .chat-info p {
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        .chat-messages {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .message {
            max-width: 80%;
            padding: 0.75rem 1rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            line-height: 1.4;
            animation: slideIn 0.3s ease-out;
        }
        
        .message.own {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0.25rem;
        }
        
        .message.other {
            background: rgba(55, 65, 81, 0.8);
            color: #e2e8f0;
            align-self: flex-start;
            border-bottom-left-radius: 0.25rem;
        }
        
        .message.telegram {
            background: rgba(0, 136, 204, 0.2);
            border-left: 3px solid #0088cc;
        }
        
        .message .time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .message .user {
            font-weight: 600;
            color: #0088cc;
            margin-bottom: 0.25rem;
        }
        
        .message-type {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            background: rgba(255, 255, 255, 0.1);
            margin-left: 0.5rem;
        }
        
        .loading {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100px;
            color: #9ca3af;
        }
        
        .no-chats {
            text-align: center;
            padding: 2rem;
            color: #9ca3af;
        }
        
        .online-indicator {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            margin-left: auto;
            animation: pulse 2s infinite;
        }
        
        @keyframes slideIn {
            from {
                transform: translateY(10px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        @keyframes pulse {
            0%, 100% {
                opacity: 1;
            }
            50% {
                opacity: 0.5;
            }
        }
        
        /* Красивые скроллбары */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 15, 35, 0.8);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            border-radius: 4px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
        }
        
        ::-webkit-scrollbar-corner {
            background: rgba(15, 15, 35, 0.8);
        }
        
        /* Firefox scrollbars */
        * {
            scrollbar-width: thin;
            scrollbar-color: #1e3a8a rgba(15, 15, 35, 0.8);
        }
        
        @media (max-width: 768px) {
            .chat-grid {
                grid-template-columns: 1fr;
            }
            
            .container {
                padding: 1rem;
            }
            
            .header {
                padding: 1rem;
            }
            
            .control-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📱 Telegram Dashboard</h1>
        <div class="user-info">
            <span>{{ auth()->user()->name }}</span>
            <a href="{{ url('/') }}">Главная</a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit">Выйти</button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <!-- Telegram Bot Controls -->
        <div class="telegram-controls">
            <h3>🤖 Управление Telegram Ботом</h3>
            <div class="control-buttons">
                <button class="control-btn success" onclick="setWebhook()">
                    ⚡ Установить Webhook
                </button>
                <button class="control-btn primary" onclick="getWebhookInfo()">
                    ℹ️ Инфо Webhook
                </button>
                <button class="control-btn warning" onclick="getBotInfo()">
                    🤖 Инфо Бота
                </button>
                <button class="control-btn danger" onclick="deleteWebhook()">
                    🗑️ Удалить Webhook
                </button>
                <button class="control-btn primary" onclick="refreshChats()">
                    🔄 Обновить Чаты
                </button>
            </div>
        </div>
        
        <!-- Chats Grid -->
        <div class="chat-grid" id="chats-grid">
            <div class="loading">
                <div>Загрузка чатов...</div>
            </div>
        </div>
    </div>
    
    <script>
        let chats = [];
        let messageIntervals = {};
        
        // Загрузка чатов
        async function loadChats() {
            try {
                const response = await fetch('/api/chats', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    chats = data.chats;
                    renderChats();
                }
            } catch (error) {
                console.error('Ошибка загрузки чатов:', error);
            }
        }
        
        // Отрисовка чатов
        function renderChats() {
            const grid = document.getElementById('chats-grid');
            
            if (chats.length === 0) {
                grid.innerHTML = `
                    <div class="no-chats">
                        <h3>📭 Нет активных чатов</h3>
                        <p>Добавьте бота в чат или отправьте ему сообщение</p>
                    </div>
                `;
                return;
            }
            
            grid.innerHTML = chats.map(chat => `
                <div class="chat-window">
                    <div class="chat-header ${chat.type}">
                        <div class="chat-avatar">${getAvatarText(chat.title || chat.username)}</div>
                        <div class="chat-info">
                            <h3>${chat.title || chat.username || 'Чат #' + chat.chat_id}</h3>
                            <p>${getChatTypeDisplay(chat.type)} • ${chat.message_count || 0} сообщений</p>
                        </div>
                        <div class="online-indicator"></div>
                    </div>
                    <div class="chat-messages" id="messages-${chat.id}">
                        <div class="loading">Загрузка сообщений...</div>
                    </div>
                </div>
            `).join('');
            
            // Загружаем сообщения для каждого чата
            chats.forEach(chat => {
                loadChatMessages(chat.id);
                startMessagePolling(chat.id);
            });
        }
        
        // Загрузка сообщений чата
        async function loadChatMessages(chatId) {
            try {
                const response = await fetch(`/api/chats/${chatId}/messages`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    renderMessages(chatId, data.messages);
                }
            } catch (error) {
                console.error('Ошибка загрузки сообщений:', error);
            }
        }
        
        // Отрисовка сообщений
        function renderMessages(chatId, messages) {
            const container = document.getElementById(`messages-${chatId}`);
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="loading">Нет сообщений</div>';
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.is_telegram ? 'telegram' : 'other'}">
                    ${msg.is_telegram ? '<div class="user">' + msg.user + '</div>' : ''}
                    <div>${msg.message}</div>
                    <div class="time">${msg.timestamp}${msg.message_type && msg.message_type !== 'text' ? '<span class="message-type">' + getMessageTypeDisplay(msg.message_type) + '</span>' : ''}</div>
                </div>
            `).join('');
            
            container.scrollTop = container.scrollHeight;
        }
        
        // Polling для новых сообщений
        function startMessagePolling(chatId) {
            if (messageIntervals[chatId]) {
                clearInterval(messageIntervals[chatId]);
            }
            
            messageIntervals[chatId] = setInterval(() => {
                loadChatMessages(chatId);
            }, 5000);
        }
        
        // Управление ботом
        async function setWebhook() {
            const webhookUrl = prompt('Введите URL для webhook:', 'https://www.deus-ex.site/api/telegram/webhook');
            if (!webhookUrl) return;
            
            try {
                const response = await fetch('/telegram/webhook/set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ url: webhookUrl })
                });
                
                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка установки webhook');
            }
        }
        
        async function getWebhookInfo() {
            try {
                const response = await fetch('/telegram/webhook/info', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                alert(JSON.stringify(data.webhook_info, null, 2));
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка получения информации о webhook');
            }
        }
        
        async function getBotInfo() {
            try {
                const response = await fetch('/telegram/bot/info', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                alert(JSON.stringify(data.bot_info, null, 2));
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка получения информации о боте');
            }
        }
        
        async function deleteWebhook() {
            if (!confirm('Удалить webhook?')) return;
            
            try {
                const response = await fetch('/telegram/webhook', {
                    method: 'DELETE',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                alert(data.message);
            } catch (error) {
                console.error('Ошибка:', error);
                alert('Ошибка удаления webhook');
            }
        }
        
        function refreshChats() {
            loadChats();
        }
        
        // Вспомогательные функции
        function getAvatarText(name) {
            if (!name) return '?';
            return name.charAt(0).toUpperCase();
        }
        
        function getChatTypeDisplay(type) {
            const types = {
                'private': 'Приватный',
                'group': 'Группа',
                'supergroup': 'Супергруппа',
                'channel': 'Канал'
            };
            return types[type] || 'Неизвестно';
        }
        
        function getMessageTypeDisplay(type) {
            const types = {
                'text': 'Текст',
                'photo': 'Фото',
                'document': 'Документ',
                'video': 'Видео',
                'audio': 'Аудио',
                'voice': 'Голосовое',
                'sticker': 'Стикер',
                'location': 'Геолокация',
                'contact': 'Контакт'
            };
            return types[type] || 'Неизвестно';
        }
        
        // Загрузка при старте
        document.addEventListener('DOMContentLoaded', function() {
            loadChats();
        });
    </script>
</body>
</html>
