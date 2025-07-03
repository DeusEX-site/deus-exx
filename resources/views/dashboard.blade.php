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
            height: 480px;
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
        
        .message.outgoing {
            background: rgba(16, 185, 129, 0.8);
            color: white;
            align-self: flex-end;
            border-bottom-right-radius: 0.25rem;
            border-left: 3px solid #10b981;
        }
        
        .message.outgoing .user {
            font-weight: 600;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.25rem;
        }
        
        .message.outgoing .time {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.8);
            margin-top: 0.25rem;
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
        
        .real-time-indicator {
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            margin-left: 0.5rem;
            animation: pulse 1s infinite;
            display: inline-block;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 15, 35, 0.8);
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.5rem;
        }
        
        .input-group textarea {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            outline: none;
            font-size: 0.875rem;
            background: rgba(30, 30, 60, 0.6);
            color: #e2e8f0;
            transition: border-color 0.3s ease;
            resize: none;
            min-height: 40px;
            max-height: 120px;
            overflow-y: auto;
            font-family: inherit;
            line-height: 1.4;
        }
        
        .input-group textarea::placeholder {
            color: rgba(226, 232, 240, 0.6);
        }
        
        .input-group textarea:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.3);
        }
        
        .input-group textarea:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .emoji-btn {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
            font-size: 1.2rem;
        }
        
        .emoji-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            transform: scale(1.1);
        }
        
        .emoji-panel {
            position: fixed;
            background: rgba(30, 30, 60, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            padding: 0.5rem;
            display: grid;
            grid-template-columns: repeat(6, 1fr);
            gap: 0.25rem;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }
        
        .emoji-option {
            background: transparent;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 0.25rem;
            font-size: 1.2rem;
            transition: background-color 0.2s ease;
        }
        
        .emoji-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .send-btn {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: white;
            border: none;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .send-btn:hover:not(:disabled) {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.4);
        }
        
        .send-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .send-btn.sending {
            animation: spin 1s linear infinite;
        }
        
        .send-status {
            font-size: 0.75rem;
            min-height: 1rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .send-status.success {
            color: #10b981;
        }
        
        .send-status.error {
            color: #ef4444;
        }
        
        .send-status.sending {
            color: #f59e0b;
        }
        
        @keyframes spin {
            from {
                transform: rotate(0deg);
            }
            to {
                transform: rotate(360deg);
            }
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
        
        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .chat-loading {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(16, 185, 129, 0.8);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
        }
        
        .chat-loading.show {
            opacity: 1;
        }
        
        .chat-window {
            position: relative;
        }
        
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutRight {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(100%);
                opacity: 0;
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
        <h1>📱 Telegram Dashboard <span class="real-time-indicator" title="Обновление в реальном времени"></span></h1>
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
            <p style="color: #9ca3af; font-size: 0.875rem; margin-bottom: 1rem;">
                📡 Сообщения обновляются каждую секунду • 🔄 Новые чаты проверяются каждые 10 секунд<br>
                💬 Отправляйте сообщения через бота прямо из каждого чата
            </p>
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
        let lastMessageIds = {}; // Отслеживаем последние ID сообщений для каждого чата
        let chatCheckInterval = null; // Интервал для проверки новых чатов
        let existingChatIds = new Set(); // Отслеживаем существующие чаты
        
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
                    
                    // Обновляем список существующих чатов
                    existingChatIds.clear();
                    chats.forEach(chat => existingChatIds.add(chat.id));
                    
                    // Запускаем проверку новых чатов если еще не запущена
                    if (!chatCheckInterval) {
                        startChatChecking();
                    }
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
            
            grid.innerHTML = chats.map(chat => createChatElement(chat)).join('');
            
            // Загружаем сообщения для каждого чата
            chats.forEach(chat => {
                loadChatMessages(chat.id, true); // Первоначальная загрузка
                startMessagePolling(chat.id);
            });
        }
        
        // Создание HTML элемента чата
        function createChatElement(chat) {
            return `
                <div class="chat-window" id="chat-window-${chat.id}">
                    <div class="chat-loading" id="loading-${chat.id}">⟳ Обновление...</div>
                    <div class="chat-header ${chat.type}">
                        <div class="chat-avatar">${getAvatarText(chat.title || chat.username)}</div>
                        <div class="chat-info">
                            <h3>${chat.title || chat.username || 'Чат #' + chat.chat_id}</h3>
                            <p>${getChatTypeDisplay(chat.type)} • ${chat.message_count || 0} сообщений</p>
                        </div>
                        <div class="online-indicator"></div>
                    </div>
                    <div class="chat-messages" id="messages-${chat.id}" onclick="focusChatInput(${chat.id})">
                        <div class="loading">Загрузка сообщений...</div>
                    </div>
                    <div class="chat-input">
                        <div class="input-group">
                            <textarea id="input-${chat.id}" 
                                     placeholder="Отправить сообщение" 
                                     onkeydown="handleChatKeyDown(event, ${chat.id}, ${chat.chat_id})"
                                     oninput="autoResizeTextarea(this)"
                                     maxlength="4000"
                                     rows="1"></textarea>
                            <button class="emoji-btn" onclick="showEmojiPanel(${chat.id}, event)" title="Добавить смайлик">
                                😀
                            </button>
                            <button class="send-btn" 
                                    onclick="sendTelegramMessage(${chat.id}, ${chat.chat_id})"
                                    id="send-btn-${chat.id}"
                                    title="Отправить сообщение">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                                </svg>
                            </button>
                        </div>
                        <div class="send-status" id="status-${chat.id}"></div>
                    </div>
                </div>
            `;
        }
        
        // Загрузка сообщений чата
        async function loadChatMessages(chatId, isInitialLoad = false) {
            try {
                // Показываем индикатор загрузки только для обновлений (не для первоначальной загрузки)
                if (!isInitialLoad) {
                    showLoadingIndicator(chatId);
                }
                
                const afterId = isInitialLoad ? 0 : (lastMessageIds[chatId] || 0);
                const response = await fetch(`/api/chats/${chatId}/messages?after=${afterId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    // Если это первая загрузка, отрисовываем все сообщения
                    if (isInitialLoad) {
                        renderMessages(chatId, data.messages);
                        updateLastMessageId(chatId, data.messages);
                    } else {
                        // Иначе только добавляем новые сообщения
                        if (data.messages && data.messages.length > 0) {
                            appendNewMessages(chatId, data.messages);
                            updateLastMessageId(chatId, data.messages);
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки сообщений:', error);
            } finally {
                // Скрываем индикатор загрузки
                if (!isInitialLoad) {
                    hideLoadingIndicator(chatId);
                }
            }
        }
        
        // Отрисовка сообщений (для первой загрузки)
        function renderMessages(chatId, messages) {
            const container = document.getElementById(`messages-${chatId}`);
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="loading">Нет сообщений</div>';
                return;
            }
            
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.is_outgoing ? 'outgoing' : (msg.is_telegram ? 'telegram' : 'other')}" data-message-id="${msg.id}">
                    ${(msg.is_telegram || msg.is_outgoing) ? '<div class="user">' + msg.user + '</div>' : ''}
                    <div>${msg.message}</div>
                    <div class="time">${msg.timestamp}${msg.message_type && msg.message_type !== 'text' ? '<span class="message-type">' + getMessageTypeDisplay(msg.message_type) + '</span>' : ''}</div>
                </div>
            `).join('');
            
            container.scrollTop = container.scrollHeight;
        }
        
        // Добавление только новых сообщений
        function appendNewMessages(chatId, messages) {
            const container = document.getElementById(`messages-${chatId}`);
            
            if (!container || messages.length === 0) {
                return;
            }
            
            // Удаляем сообщение "Нет сообщений" если оно есть
            const noMessages = container.querySelector('.loading');
            if (noMessages) {
                noMessages.remove();
            }
            
            // Добавляем новые сообщения
            messages.forEach((msg, index) => {
                const messageEl = document.createElement('div');
                messageEl.className = `message ${msg.is_outgoing ? 'outgoing' : (msg.is_telegram ? 'telegram' : 'other')}`;
                messageEl.setAttribute('data-message-id', msg.id);
                messageEl.innerHTML = `
                    ${(msg.is_telegram || msg.is_outgoing) ? '<div class="user">' + msg.user + '</div>' : ''}
                    <div>${msg.message}</div>
                    <div class="time">${msg.timestamp}${msg.message_type && msg.message_type !== 'text' ? '<span class="message-type">' + getMessageTypeDisplay(msg.message_type) + '</span>' : ''}</div>
                `;
                
                // Устанавливаем начальное состояние для анимации
                messageEl.style.opacity = '0';
                messageEl.style.transform = 'translateY(20px)';
                messageEl.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                
                container.appendChild(messageEl);
                
                // Запускаем анимацию с задержкой
                setTimeout(() => {
                    messageEl.style.opacity = '1';
                    messageEl.style.transform = 'translateY(0)';
                }, 10 + (index * 100));
                
                // Убираем inline стили после завершения анимации
                setTimeout(() => {
                    messageEl.style.removeProperty('opacity');
                    messageEl.style.removeProperty('transform');
                    messageEl.style.removeProperty('transition');
                }, 600 + (index * 100));
            });
            
            // Плавная прокрутка к новым сообщениям
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        }
        
        // Обновление последнего ID сообщения
        function updateLastMessageId(chatId, messages) {
            if (messages && messages.length > 0) {
                const lastMessage = messages[messages.length - 1];
                if (lastMessage && lastMessage.id) {
                    lastMessageIds[chatId] = lastMessage.id;
                }
            }
        }
        
        // Показать индикатор загрузки
        function showLoadingIndicator(chatId) {
            const indicator = document.getElementById(`loading-${chatId}`);
            if (indicator) {
                indicator.classList.add('show');
            }
        }
        
        // Скрыть индикатор загрузки
        function hideLoadingIndicator(chatId) {
            const indicator = document.getElementById(`loading-${chatId}`);
            if (indicator) {
                indicator.classList.remove('show');
            }
        }
        
        // Polling для новых сообщений
        function startMessagePolling(chatId) {
            if (messageIntervals[chatId]) {
                clearInterval(messageIntervals[chatId]);
            }
            
            messageIntervals[chatId] = setInterval(() => {
                loadChatMessages(chatId, false); // Загрузка только новых сообщений
            }, 1000); // Обновляем каждую секунду
        }
        
        // Запуск проверки новых чатов
        function startChatChecking() {
            chatCheckInterval = setInterval(async () => {
                await checkNewChats();
            }, 10000); // Проверяем новые чаты каждые 10 секунд
        }
        
        // Проверка новых чатов
        async function checkNewChats() {
            try {
                const response = await fetch('/api/chats', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const newChats = data.chats.filter(chat => !existingChatIds.has(chat.id));
                    
                    if (newChats.length > 0) {
                        newChats.forEach(chat => {
                            addNewChat(chat);
                            existingChatIds.add(chat.id);
                            chats.push(chat);
                        });
                    }
                }
            } catch (error) {
                console.error('Ошибка проверки новых чатов:', error);
            }
        }
        
        // Добавление нового чата без полной перерисовки
        function addNewChat(chat) {
            const grid = document.getElementById('chats-grid');
            
            // Удаляем сообщение "Нет чатов" если оно есть
            const noChats = grid.querySelector('.no-chats');
            if (noChats) {
                noChats.remove();
            }
            
            // Создаем новый элемент чата
            const chatElement = document.createElement('div');
            chatElement.innerHTML = createChatElement(chat);
            const chatWindow = chatElement.firstElementChild;
            
            // Добавляем анимацию появления
            chatWindow.style.opacity = '0';
            chatWindow.style.transform = 'scale(0.9)';
            chatWindow.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
            
            grid.appendChild(chatWindow);
            
            // Запускаем анимацию
            setTimeout(() => {
                chatWindow.style.opacity = '1';
                chatWindow.style.transform = 'scale(1)';
            }, 10);
            
            // Загружаем сообщения для нового чата
            loadChatMessages(chat.id, true);
            startMessagePolling(chat.id);
            
            // Показываем уведомление о новом чате
            showNewChatNotification(chat);
        }
        
        // Уведомление о новом чате
        function showNewChatNotification(chat) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981 0%, #059669 100%);
                color: white;
                padding: 1rem 1.5rem;
                border-radius: 0.5rem;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                animation: slideInRight 0.5s ease-out;
                max-width: 300px;
            `;
            
            notification.innerHTML = `
                <div style="font-weight: 600; margin-bottom: 0.25rem;">🎉 Новый чат!</div>
                <div>${chat.title || chat.username || 'Чат #' + chat.chat_id}</div>
            `;
            
            document.body.appendChild(notification);
            
            // Убираем уведомление через 5 секунд
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.5s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 500);
            }, 5000);
        }
        
        // Обработчик клавиш в поле ввода
        function handleChatKeyDown(event, chatId, telegramChatId) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                sendTelegramMessage(chatId, telegramChatId);
            }
            
            // Автоматическое изменение высоты textarea
            autoResizeTextarea(event.target);
        }
        
        // Автоматическое изменение размера textarea
        function autoResizeTextarea(textarea) {
            textarea.style.height = 'auto';
            const scrollHeight = textarea.scrollHeight;
            const maxHeight = 120; // Максимальная высота из CSS
            const minHeight = 40;  // Минимальная высота из CSS
            
            if (scrollHeight <= maxHeight) {
                textarea.style.height = Math.max(scrollHeight, minHeight) + 'px';
            } else {
                textarea.style.height = maxHeight + 'px';
            }
        }
        
        // Автофокус на поле ввода при клике на чат
        function focusChatInput(chatId) {
            const textarea = document.getElementById(`input-${chatId}`);
            if (textarea && !textarea.disabled) {
                textarea.focus();
            }
        }
        
        // Вставка эмодзи в поле ввода
        function insertEmoji(chatId, emoji) {
            const textarea = document.getElementById(`input-${chatId}`);
            if (textarea && !textarea.disabled) {
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                
                textarea.value = text.substring(0, start) + emoji + text.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + emoji.length;
                
                // Автоматически изменяем размер и фокусируемся
                autoResizeTextarea(textarea);
                textarea.focus();
            }
        }
        
        // Показать панель эмодзи
        function showEmojiPanel(chatId, event) {
            // Предотвращаем всплытие события
            if (event) {
                event.stopPropagation();
            }
            
            const emojis = ['😀', '😂', '😍', '🤔', '👍', '👎', '❤️', '🔥', '💯', '✅', '❌', '🎉'];
            
            // Создаем панель эмодзи
            const panel = document.createElement('div');
            panel.className = 'emoji-panel';
            panel.innerHTML = emojis.map(emoji => 
                `<button onclick="insertEmoji(${chatId}, '${emoji}'); this.parentElement.remove();" class="emoji-option">${emoji}</button>`
            ).join('');
            
            document.body.appendChild(panel);
            
            // Позиционируем панель
            const emojiBtn = event ? event.target : document.querySelector(`#chat-window-${chatId} .emoji-btn`);
            const rect = emojiBtn.getBoundingClientRect();
            panel.style.left = rect.left + 'px';
            panel.style.top = (rect.top - panel.offsetHeight - 10) + 'px';
            
            // Закрываем панель при клике вне ее
            setTimeout(() => {
                const closePanel = (e) => {
                    if (!panel.contains(e.target) && !emojiBtn.contains(e.target)) {
                        panel.remove();
                        document.removeEventListener('click', closePanel);
                    }
                };
                document.addEventListener('click', closePanel);
            }, 100);
        }
        
        // Отправка сообщения через Telegram бота
        async function sendTelegramMessage(chatId, telegramChatId) {
            const textarea = document.getElementById(`input-${chatId}`);
            const sendBtn = document.getElementById(`send-btn-${chatId}`);
            const status = document.getElementById(`status-${chatId}`);
            
            const message = textarea.value.trim();
            if (!message) return;
            
            // Блокируем интерфейс во время отправки
            textarea.disabled = true;
            sendBtn.disabled = true;
            sendBtn.classList.add('sending');
            
            // Показываем статус отправки
            showSendStatus(chatId, 'sending', '📤 Отправка...');
            
            try {
                const response = await fetch('/telegram/send-message', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        chat_id: telegramChatId,
                        text: message
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Успешная отправка
                    textarea.value = '';
                    textarea.style.height = '40px'; // Сбрасываем высоту
                    showSendStatus(chatId, 'success', '✅ Отправлено');
                    
                    // Сразу обновляем сообщения чата для быстрого отображения
                    setTimeout(() => {
                        loadChatMessages(chatId, false);
                    }, 500);
                } else {
                    // Ошибка отправки
                    showSendStatus(chatId, 'error', `❌ ${data.message || 'Ошибка отправки'}`);
                }
                
            } catch (error) {
                console.error('Ошибка отправки сообщения:', error);
                showSendStatus(chatId, 'error', '❌ Ошибка соединения');
            } finally {
                // Разблокируем интерфейс
                textarea.disabled = false;
                sendBtn.disabled = false;
                sendBtn.classList.remove('sending');
                
                // Фокусируемся обратно на поле ввода
                textarea.focus();
                
                // Очищаем статус через 3 секунды
                setTimeout(() => {
                    clearSendStatus(chatId);
                }, 3000);
            }
        }
        
        // Показать статус отправки
        function showSendStatus(chatId, type, message) {
            const status = document.getElementById(`status-${chatId}`);
            if (status) {
                status.className = `send-status ${type}`;
                status.textContent = message;
            }
        }
        
        // Очистить статус отправки
        function clearSendStatus(chatId) {
            const status = document.getElementById(`status-${chatId}`);
            if (status) {
                status.className = 'send-status';
                status.textContent = '';
            }
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
            // Останавливаем все интервалы
            Object.values(messageIntervals).forEach(interval => {
                if (interval) clearInterval(interval);
            });
            messageIntervals = {};
            
            // Останавливаем проверку чатов
            if (chatCheckInterval) {
                clearInterval(chatCheckInterval);
                chatCheckInterval = null;
            }
            
            // Очищаем данные
            existingChatIds.clear();
            lastMessageIds = {};
            
            // Перезагружаем чаты
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
            startNewChatPolling();
        });
        
        // Инициализация полей ввода для существующих чатов
        function initializeChatInputs() {
            const textareas = document.querySelectorAll('[id^="input-"]');
            textareas.forEach(textarea => {
                autoResizeTextarea(textarea);
            });
        }
        
        // Очистка всех интервалов
        function cleanupIntervals() {
            // Останавливаем все интервалы сообщений
            Object.values(messageIntervals).forEach(interval => {
                if (interval) clearInterval(interval);
            });
            
            // Останавливаем проверку чатов
            if (chatCheckInterval) {
                clearInterval(chatCheckInterval);
            }
        }
        
        // Начать проверку новых чатов
        function startNewChatPolling() {
            // Проверяем новые чаты каждые 10 секунд
            chatCheckInterval = setInterval(checkNewChats, 10000);
        }
        
        // Проверка новых чатов
        async function checkNewChats() {
            try {
                const response = await fetch('/api/telegram/chats');
                const data = await response.json();
                
                if (data.success) {
                    // Проверяем новые чаты
                    for (const chat of data.chats) {
                        if (!existingChatIds.has(chat.id)) {
                            existingChatIds.add(chat.id);
                            addNewChat(chat);
                        }
                    }
                }
            } catch (error) {
                console.error('Ошибка проверки новых чатов:', error);
            }
        }
        
        // Очистка интервалов при закрытии страницы
        window.addEventListener('beforeunload', function() {
            cleanupIntervals();
        });
    </script>
</body>
</html>
