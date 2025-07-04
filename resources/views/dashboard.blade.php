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
        
        .header-controls {
            display: flex;
            align-items: center;
            gap: 1rem;
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
            font-size: 0.875rem;
        }
        
        .header a:hover, .header button:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
        
        .broadcast-btn {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%) !important;
            color: white !important;
            font-weight: 600;
            font-size: 0.9rem !important;
            box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
        }
        
        .broadcast-btn:hover {
            background: linear-gradient(135deg, #d97706 0%, #dc2626 100%) !important;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        
        .container {
            padding: 2rem;
            width: 100%;
            margin: 0;
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
            top: 12px;
            right: 32px;
            width: 6px;
            height: 6px;
            background: #10b981;
            border-radius: 50%;
            opacity: 0;
            transition: opacity 0.3s ease;
            pointer-events: none;
            z-index: 10;
        }
        
        .chat-loading.show {
            opacity: 1;
            animation: pulse 1s infinite;
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

        @keyframes positionIndicator {
            0% { 
                opacity: 0;
                transform: scale(0.5);
            }
            20% { 
                opacity: 1;
                transform: scale(1.2);
            }
            40% { 
                transform: scale(1);
            }
            100% { 
                opacity: 0;
                transform: scale(0.8);
            }
        }

        .position-changing {
            position: relative;
            z-index: 5;
        }

        .chat-window.position-changing {
            border: 2px solid rgba(59, 130, 246, 0.5);
        }

        .chat-window.top-chat {
            border-left: 4px solid rgba(34, 197, 94, 0.8);
        }

        .chat-window.top-chat .chat-header {
            background: linear-gradient(135deg, rgba(34, 197, 94, 0.1) 0%, rgba(16, 185, 129, 0.1) 100%);
        }

        .chat-window.top-chat .chat-header::before {
            content: "🔥";
            position: absolute;
            top: 5px;
            left: 5px;
            font-size: 12px;
            opacity: 0.8;
        }

        .top-chat-indicator {
            position: absolute;
            top: 5px;
            left: 5px;
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
            color: white;
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 8px;
            font-weight: bold;
            z-index: 10;
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
        
        /* Broadcast Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(5px);
        }
        
        .modal-content {
            background: rgba(30, 30, 60, 0.95);
            margin: 10% auto;
            padding: 2rem;
            border-radius: 1rem;
            width: 90%;
            max-width: 600px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .modal-header h2 {
            color: white;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .close {
            color: rgba(255, 255, 255, 0.6);
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            border: none;
            background: none;
            transition: color 0.3s ease;
        }
        
        .close:hover {
            color: white;
        }
        
        .broadcast-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .broadcast-textarea {
            width: 100%;
            padding: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            background: rgba(15, 15, 35, 0.8);
            color: #e2e8f0;
            font-size: 1rem;
            font-family: inherit;
            resize: vertical;
            min-height: 120px;
            outline: none;
            transition: border-color 0.3s ease;
        }
        
        .broadcast-textarea:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.3);
        }
        
        .broadcast-textarea::placeholder {
            color: rgba(226, 232, 240, 0.5);
        }
        
        .broadcast-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }
        
        .btn-cancel {
            padding: 0.75rem 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 0.5rem;
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-cancel:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
        }
        
        .btn-send {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.5rem;
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-send:hover {
            background: linear-gradient(135deg, #d97706 0%, #dc2626 100%);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
        }
        
        .btn-send:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        
        .broadcast-status {
            margin-top: 1rem;
            padding: 0.75rem;
            border-radius: 0.5rem;
            display: none;
        }
        
        .broadcast-status.success {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            color: #10b981;
        }
        
        .broadcast-status.error {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #ef4444;
        }
        
        .broadcast-status.sending {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.3);
            color: #f59e0b;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📱 Telegram Dashboard <span class="real-time-indicator" title="Обновление в реальном времени"></span></h1>
        <div class="header-controls">
            <a href="{{ route('cap-analysis') }}" class="broadcast-btn" title="Анализ сообщений с капой" style="text-decoration: none; background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important;">
                📊 Анализ Капы
            </a>
            <button id="broadcast-btn" class="broadcast-btn" title="Глобальная рассылка офферов">
                📢 Рассылка
            </button>
            <div class="user-info">
                <span>{{ auth()->user()->name }}</span>
                <a href="{{ url('/') }}">Главная</a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit">Выйти</button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Broadcast Modal -->
    <div id="broadcast-modal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>📢 Глобальная рассылка офферов</h2>
                <button class="close" id="modal-close">&times;</button>
            </div>
            <form class="broadcast-form" id="broadcast-form">
                <textarea 
                    class="broadcast-textarea" 
                    id="broadcast-message" 
                    placeholder="Введите сообщение для отправки во все чаты...&#10;&#10;Пример:&#10;🔥 НОВЫЙ ОФФЕР!&#10;💰 Высокая конверсия&#10;🚀 Начинай зарабатывать сейчас!"
                    required
                ></textarea>
                <div class="broadcast-actions">
                    <button type="button" class="btn-cancel" id="cancel-broadcast">Отмена</button>
                    <button type="submit" class="btn-send" id="send-broadcast">
                        📤 Отправить во все чаты
                    </button>
                </div>
                <div class="broadcast-status" id="broadcast-status"></div>
            </form>
        </div>
    </div>

    <div class="container">
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
        let chatPositions = {}; // Отслеживаем позиции чатов для анимации
        
        // Флаги для предотвращения конкурентных запросов
        let isLoadingMessages = {}; // Флаги загрузки сообщений для каждого чата
        let isCheckingNewChats = false; // Флаг проверки новых чатов
        let isPageVisible = true; // Флаг видимости страницы
        let lastRequestTime = {}; // Время последних запросов для throttling
        let isSwappingChats = false; // Флаг для предотвращения конкурентных анимаций
        
        // Переменные для пагинации
        let isLoadingOldMessages = {}; // Флаги загрузки старых сообщений
        let hasOlderMessages = {}; // Есть ли еще старые сообщения для загрузки
        let firstMessageIds = {}; // Первые ID сообщений для каждого чата
        
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
            
            // Сохраняем текущие позиции для анимации
            updateChatPositions();
            
            grid.innerHTML = chats.map(chat => createChatElement(chat)).join('');
            
            // Загружаем сообщения для каждого чата
            chats.forEach(chat => {
                loadChatMessages(chat.id, true); // Первоначальная загрузка
                startMessagePolling(chat.id);
                // Добавляем обработчик скролла с задержкой
                setTimeout(() => {
                    setupScrollListener(chat.id);
                }, 1000);
            });
        }

        // Обновление позиций чатов для отслеживания изменений
        function updateChatPositions() {
            const newPositions = {};
            chats.forEach((chat, index) => {
                newPositions[chat.id] = index;
            });
            
            // Проверяем изменения позиций
            const positionChanges = detectPositionChanges(chatPositions, newPositions);
            
            if (positionChanges.length > 0 && !isSwappingChats) {
                animatePositionChanges(positionChanges);
            }
            
            chatPositions = newPositions;
        }

        // Проверка изменений позиций чатов (БЕЗ полной перезагрузки)
        async function checkForPositionChanges() {
            if (isSwappingChats) return;
            
            console.log('🔍 Checking position changes...');
            
            try {
                const response = await fetch('/api/chats', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    const newChats = data.chats;
                    
                    console.log('📊 Current chats (TOP-10):', chats.slice(0, 10).map(c => ({
                        id: c.id,
                        title: c.title,
                        display_order: c.display_order
                    })));
                    
                    console.log('📊 New chats (TOP-10):', newChats.slice(0, 10).map(c => ({
                        id: c.id,
                        title: c.title,
                        display_order: c.display_order
                    })));
                    
                    // Ищем изменения: только swap между топ-10 и не-топ-10
                    const swapInfo = findSwapBetweenTopAndOthers(chats, newChats);
                    
                    if (swapInfo) {
                        console.log('🔄 Swap detected:', swapInfo);
                        // Выполняем swap контента между двумя HTML элементами
                        swapChatContent(swapInfo);
                        // Обновляем массив чатов
                        chats = newChats;
                    } else {
                        console.log('✅ No position changes detected');
                    }
                }
            } catch (error) {
                console.error('❌ Error checking position changes:', error);
            }
        }

        // Поиск swap-а между топ-10 и остальными чатами
        function findSwapBetweenTopAndOthers(oldChats, newChats) {
            // Находим чаты, которые поменялись местами между топ-10 и остальными
            const oldTop10 = oldChats.slice(0, 10).map(c => c.id);
            const newTop10 = newChats.slice(0, 10).map(c => c.id);
            
            console.log('🔍 Swap detection:', {
                oldTop10: oldTop10,
                newTop10: newTop10
            });
            
            // Находим чат, который вошел в топ-10
            const chatInId = newTop10.find(id => !oldTop10.includes(id));
            // Находим чат, который вышел из топ-10
            const chatOutId = oldTop10.find(id => !newTop10.includes(id));
            
            console.log('🔍 Found changes:', {
                chatInId: chatInId,
                chatOutId: chatOutId
            });
            
            if (chatInId && chatOutId) {
                const chatIn = newChats.find(c => c.id === chatInId);
                const chatOut = oldChats.find(c => c.id === chatOutId);
                
                // Найдем позицию chatOut в старом топ-10
                const chatOutPosition = oldTop10.indexOf(chatOutId);
                // Найдем позицию chatIn в новом топ-10
                const chatInPosition = newTop10.indexOf(chatInId);
                
                console.log('🔍 Chat positions:', {
                    chatOut: { id: chatOutId, title: chatOut?.title, oldPosition: chatOutPosition },
                    chatIn: { id: chatInId, title: chatIn?.title, newPosition: chatInPosition }
                });
                
                return {
                    chatIn: chatIn,
                    chatOut: chatOut,
                    chatInId: chatInId,
                    chatOutId: chatOutId,
                    chatOutPosition: chatOutPosition,
                    chatInPosition: chatInPosition
                };
            }
            
            return null;
        }

        // Простой swap DOM элементов чатов
        function swapChatContent(swapInfo) {
            if (isSwappingChats) return;
            
            isSwappingChats = true;
            
            console.log('🔄 Starting DOM elements swap:', swapInfo);
            
            // Поиск элементов напрямую по ID (не нужен контейнер)
            
            console.log('🔍 Looking for elements by ID:', {
                chatOutId: swapInfo.chatOutId,
                chatInId: swapInfo.chatInId
            });
            
            // Найти элементы по ID чатов (более надежно, чем по названиям)
            const elementOut = document.getElementById(`chat-window-${swapInfo.chatOutId}`);
            const elementIn = document.getElementById(`chat-window-${swapInfo.chatInId}`);
            
            console.log('🔍 Found elements:', {
                elementOut: elementOut ? elementOut.id : 'NOT FOUND',
                elementIn: elementIn ? elementIn.id : 'NOT FOUND',
                chatOutData: swapInfo.chatOut.title || swapInfo.chatOut.username,
                chatInData: swapInfo.chatIn.title || swapInfo.chatIn.username
            });
            
            if (!elementOut) {
                console.error('❌ Element for chatOut not found:', `chat-window-${swapInfo.chatOutId}`);
                isSwappingChats = false;
                return;
            }
            
            if (!elementIn) {
                console.log('⚠️ Element for chatIn not found - skipping swap:', `chat-window-${swapInfo.chatInId}`);
                isSwappingChats = false;
                return;
            }
            
            // Оба элемента найдены - меняем их местами в DOM
            swapDOMElements(elementOut, elementIn, swapInfo);
        }
        

        
        // Swap DOM элементов местами
        function swapDOMElements(elementOut, elementIn, swapInfo) {
            console.log('🔄 Swapping DOM elements:', {
                elementOut: elementOut.id,
                elementIn: elementIn.id
            });
            
            // Анимация начала swap
            elementOut.style.transform = 'scale(0.95)';
            elementIn.style.transform = 'scale(0.95)';
            elementOut.style.transition = 'transform 0.3s ease';
            elementIn.style.transition = 'transform 0.3s ease';
            
            setTimeout(() => {
                // Сохраняем ссылки на соседние элементы
                const outNextSibling = elementOut.nextSibling;
                const outParent = elementOut.parentNode;
                const inNextSibling = elementIn.nextSibling;
                const inParent = elementIn.parentNode;
                
                // Меняем элементы местами в DOM
                if (outNextSibling) {
                    inParent.insertBefore(elementOut, outNextSibling);
                } else {
                    inParent.appendChild(elementOut);
                }
                
                if (inNextSibling) {
                    outParent.insertBefore(elementIn, inNextSibling);
                } else {
                    outParent.appendChild(elementIn);
                }
                
                // Обновляем ID элементов в соответствии с новыми данными
                elementOut.id = `chat-window-${swapInfo.chatIn.id}`;
                elementIn.id = `chat-window-${swapInfo.chatOut.id}`;
                
                // Обновляем заголовки
                updateChatTitleOnly(elementOut, swapInfo.chatIn);
                updateChatTitleOnly(elementIn, swapInfo.chatOut);
                
                // Обновляем внутренние ID
                updateInternalIds(elementOut, swapInfo.chatIn);
                updateInternalIds(elementIn, swapInfo.chatOut);
                
                // ВАЖНО: Останавливаем старые polling
                stopMessagePolling(swapInfo.chatOut.id);
                stopMessagePolling(swapInfo.chatIn.id);
                
                // ВАЖНО: Загружаем новые сообщения для каждого чата
                loadNewMessagesForSwappedChats(elementOut, swapInfo.chatIn);
                loadNewMessagesForSwappedChats(elementIn, swapInfo.chatOut);
                
                // Перезапускаем polling
                startMessagePolling(swapInfo.chatIn.id);
                startMessagePolling(swapInfo.chatOut.id);
                
                // Переустанавливаем обработчики скролла для swapped чатов с задержкой
                setTimeout(() => {
                    setupScrollListener(swapInfo.chatIn.id);
                    setupScrollListener(swapInfo.chatOut.id);
                }, 1000);
                
                // Показываем индикаторы
                showSwapIndicators(elementOut, elementIn);
                
                // Автоскролл вниз после swap (с задержкой для загрузки сообщений)
                setTimeout(() => {
                    scrollChatToBottom(swapInfo.chatIn.id);
                    scrollChatToBottom(swapInfo.chatOut.id);
                }, 1000);
                
                // Возвращаем нормальный размер
                elementOut.style.transform = 'scale(1)';
                elementIn.style.transform = 'scale(1)';
                
                console.log('✅ DOM elements swapped successfully');
                
                setTimeout(() => {
                    isSwappingChats = false;
                }, 500);
            }, 300);
        }
        


        // Обмен контентом между элементами чатов
        function swapChatElementContent(elementIn, elementOut, chatIn, chatOut) {
            console.log('🔄 Starting content swap:', {
                elementIn: elementIn.id,
                elementOut: elementOut.id,
                chatIn: chatIn.title,
                chatOut: chatOut.title
            });
            
            // Сохраняем весь HTML контент обоих элементов
            const contentIn = elementIn.innerHTML;
            const contentOut = elementOut.innerHTML;
            
            console.log('💾 Content saved, swapping...');
            
            // Меняем местами весь контент
            elementIn.innerHTML = contentOut;
            elementOut.innerHTML = contentIn;
            
            // Обновляем только ID основных элементов
            elementIn.id = `chat-window-${chatIn.id}`;
            elementOut.id = `chat-window-${chatOut.id}`;
            
            // Обновляем ID внутренних элементов в elementIn (теперь содержит контент chatIn)
            updateInternalIds(elementIn, chatIn);
            
            // Обновляем ID внутренних элементов в elementOut (теперь содержит контент chatOut)
            updateInternalIds(elementOut, chatOut);
            
            // Обновляем только заголовки (названия чатов)
            updateChatTitleOnly(elementIn, chatIn);
            updateChatTitleOnly(elementOut, chatOut);
            
            // Перезапускаем polling для новых чатов
            startMessagePolling(chatIn.id);
            startMessagePolling(chatOut.id);
            
            console.log('✅ Content swap completed');
        }

        // Обновление ID внутренних элементов после swap-а
        function updateInternalIds(element, chat) {
            // Обновляем ID контейнера сообщений
            const messagesContainer = element.querySelector('.chat-messages');
            if (messagesContainer) {
                messagesContainer.id = `messages-${chat.id}`;
                messagesContainer.setAttribute('onclick', `focusChatInput(${chat.id})`);
            }
            
            // Обновляем ID поля ввода
            const inputField = element.querySelector('textarea');
            if (inputField) {
                inputField.id = `input-${chat.id}`;
                inputField.setAttribute('onkeydown', `handleChatKeyDown(event, ${chat.id}, ${chat.chat_id})`);
            }
            
            // Обновляем ID кнопки отправки
            const sendButton = element.querySelector('.send-btn');
            if (sendButton) {
                sendButton.id = `send-btn-${chat.id}`;
                sendButton.setAttribute('onclick', `sendTelegramMessage(${chat.id}, ${chat.chat_id})`);
            }
            
            // Обновляем ID кнопки эмодзи
            const emojiButton = element.querySelector('.emoji-btn');
            if (emojiButton) {
                emojiButton.setAttribute('onclick', `showEmojiPanel(${chat.id}, event)`);
            }
            
            // Обновляем ID статуса отправки
            const sendStatus = element.querySelector('.send-status');
            if (sendStatus) {
                sendStatus.id = `send-status-${chat.id}`;
            }
        }
        
        // Обновление только заголовка чата (без замены всего контента)
        function updateChatTitleOnly(element, chat) {
            const header = element.querySelector('.chat-header');
            if (header) {
                const displayName = chat.title || chat.username || 'Чат #' + chat.chat_id;
                
                // Обновляем только текст заголовка
                const titleElement = header.querySelector('h3');
                if (titleElement) {
                    titleElement.textContent = displayName;
                }
                
                // Обновляем аватар
                const avatarElement = header.querySelector('.chat-avatar');
                if (avatarElement) {
                    avatarElement.textContent = getAvatarText(displayName);
                }
                
                // Обновляем подпись
                const infoElement = header.querySelector('.chat-info p');
                if (infoElement) {
                    infoElement.textContent = `${getChatTypeDisplay(chat.type)} • ${chat.message_count || 0} сообщений`;
                }
                
                // Обновляем класс заголовка
                header.className = `chat-header ${chat.type}`;
                
                console.log('📝 Updated title to:', displayName);
            }
        }

        // Обновление заголовка чата (полная замена - используется для новых чатов)
        function updateChatHeader(element, chat) {
            const header = element.querySelector('.chat-header');
            if (header) {
                header.className = `chat-header ${chat.type}`;
                header.innerHTML = `
                    <div class="chat-avatar">${getAvatarText(chat.title || chat.username)}</div>
                    <div class="chat-info">
                        <h3>${chat.title || chat.username || 'Чат #' + chat.chat_id}</h3>
                        <p>${getChatTypeDisplay(chat.type)} • ${chat.message_count || 0} сообщений</p>
                    </div>
                    <div class="online-indicator"></div>
                `;
            }
        }

        // Обновление контейнера сообщений
        function updateChatMessages(element, chat) {
            const messagesContainer = element.querySelector('.chat-messages');
            if (messagesContainer) {
                messagesContainer.id = `messages-${chat.id}`;
                messagesContainer.setAttribute('onclick', `focusChatInput(${chat.id})`);
                messagesContainer.innerHTML = '<div class="loading">Загрузка сообщений...</div>';
                
                // Загружаем сообщения для этого чата
                loadChatMessages(chat.id, true);
            }
        }

        // Обновление поля ввода
        function updateChatInput(element, chat) {
            const inputContainer = element.querySelector('.chat-input');
            if (inputContainer) {
                inputContainer.innerHTML = `
                    <div class="input-group">
                        <textarea id="input-${chat.id}" 
                                 placeholder="Отправить сообщение через бота..." 
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
                    <div class="send-status" id="send-status-${chat.id}"></div>
                `;
            }
        }

        // Показ индикатора для одного элемента (вошел в топ-10)
        function showPromotionIndicator(element) {
            const indicator = document.createElement('div');
            indicator.className = 'promotion-indicator';
            indicator.innerHTML = '⬆️ ТОП';
            indicator.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(34, 197, 94, 0.9);
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 10px;
                z-index: 10;
                animation: positionIndicator 2s ease-out forwards;
            `;
            
            element.style.position = 'relative';
            element.appendChild(indicator);
            
            // Удаляем индикатор через время
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 2000);
        }

        // Показ индикаторов swap-а
        function showSwapIndicators(elementIn, elementOut) {
            // Индикатор для чата, который вошел в топ-10
            const indicatorIn = document.createElement('div');
            indicatorIn.className = 'swap-indicator';
            indicatorIn.innerHTML = '⬆️ ТОП';
            indicatorIn.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(34, 197, 94, 0.9);
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 10px;
                z-index: 10;
                animation: positionIndicator 2s ease-out forwards;
            `;
            
            // Индикатор для чата, который вышел из топ-10
            const indicatorOut = document.createElement('div');
            indicatorOut.className = 'swap-indicator';
            indicatorOut.innerHTML = '⬇️';
            indicatorOut.style.cssText = `
                position: absolute;
                top: 10px;
                right: 10px;
                background: rgba(239, 68, 68, 0.9);
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                font-weight: bold;
                font-size: 10px;
                z-index: 10;
                animation: positionIndicator 2s ease-out forwards;
            `;
            
            elementIn.style.position = 'relative';
            elementOut.style.position = 'relative';
            elementIn.appendChild(indicatorIn);
            elementOut.appendChild(indicatorOut);
            
            // Удаляем индикаторы через время
            setTimeout(() => {
                if (indicatorIn.parentNode) indicatorIn.parentNode.removeChild(indicatorIn);
                if (indicatorOut.parentNode) indicatorOut.parentNode.removeChild(indicatorOut);
            }, 2000);
        }

        // Определение изменений позиций
        function detectPositionChanges(oldPositions, newPositions) {
            const changes = [];
            
            for (const [chatId, newPos] of Object.entries(newPositions)) {
                const oldPos = oldPositions[chatId];
                if (oldPos !== undefined && oldPos !== newPos) {
                                    const wasInTopTen = oldPos < 10;
                const isInTopTen = newPos < 10;
                
                // Показываем анимацию только при входе/выходе из топ-10
                if (wasInTopTen !== isInTopTen) {
                        changes.push({
                            chatId: parseInt(chatId),
                            oldPosition: oldPos,
                            newPosition: newPos,
                            type: isInTopTen ? 'promoted' : 'demoted'
                        });
                    }
                }
            }
            
            return changes;
        }

        // Анимация изменений позиций
        function animatePositionChanges(changes) {
            if (isSwappingChats) return;
            
            isSwappingChats = true;
            
            const grid = document.getElementById('chats-grid');
            const chatElements = Array.from(grid.children);
            
            // Применяем анимацию для каждого изменения
            changes.forEach(change => {
                const chatElement = document.getElementById(`chat-window-${change.chatId}`);
                if (chatElement) {
                    // Добавляем класс для анимации
                    chatElement.classList.add('position-changing');
                    
                    // Анимация масштабирования
                    chatElement.style.transform = 'scale(0.95)';
                    chatElement.style.transition = 'transform 0.3s ease, box-shadow 0.3s ease';
                    chatElement.style.boxShadow = '0 8px 25px rgba(59, 130, 246, 0.3)';
                    
                    // Показываем индикатор изменения позиции
                    showPositionChangeIndicator(change);
                }
            });
            
            // Снимаем анимацию через время
            setTimeout(() => {
                changes.forEach(change => {
                    const chatElement = document.getElementById(`chat-window-${change.chatId}`);
                    if (chatElement) {
                        chatElement.classList.remove('position-changing');
                        chatElement.style.transform = 'scale(1)';
                        chatElement.style.boxShadow = '';
                    }
                });
                
                isSwappingChats = false;
            }, 500);
        }

        // Показ индикатора изменения позиции
        function showPositionChangeIndicator(change) {
            const chatElement = document.getElementById(`chat-window-${change.chatId}`);
            if (!chatElement) return;
            
            const indicator = document.createElement('div');
            indicator.className = 'position-change-indicator';
            
            // Определяем содержимое и цвет индикатора
            if (change.type === 'promoted') {
                indicator.innerHTML = '⬆️ ТОП';
                indicator.style.background = 'rgba(34, 197, 94, 0.9)'; // Зеленый для повышения
            } else {
                indicator.innerHTML = '⬇️';
                indicator.style.background = 'rgba(239, 68, 68, 0.9)'; // Красный для понижения
            }
            
            indicator.style.cssText += `
                position: absolute;
                top: 10px;
                right: 10px;
                color: white;
                padding: 4px 8px;
                border-radius: 12px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-weight: bold;
                font-size: 10px;
                z-index: 10;
                animation: positionIndicator 2s ease-out forwards;
                white-space: nowrap;
            `;
            
            chatElement.style.position = 'relative';
            chatElement.appendChild(indicator);
            
            // Удаляем индикатор через время
            setTimeout(() => {
                if (indicator.parentNode) {
                    indicator.parentNode.removeChild(indicator);
                }
            }, 2000);
        }
        
        // Создание HTML элемента чата
        function createChatElement(chat) {
            const chatIndex = chats.findIndex(c => c.id === chat.id);
                            const isTopChat = chatIndex >= 0 && chatIndex < 10;
                const topChatClass = isTopChat ? 'top-chat' : '';
                const topChatIndicator = '';
            
            return `
                <div class="chat-window ${topChatClass}" id="chat-window-${chat.id}">
                    ${topChatIndicator}
                    <div class="chat-loading" id="loading-${chat.id}"></div>
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
                                     placeholder="Отправить сообщение через бота..." 
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
            // Предотвращаем конкурентные запросы
            if (isLoadingMessages[chatId]) {
                return;
            }
            
            // Не загружаем если страница скрыта (кроме первоначальной загрузки)
            if (!isPageVisible && !isInitialLoad) {
                return;
            }
            
            // Throttling: не делаем запросы чаще чем раз в 800мс (кроме первоначальной загрузки)
            const now = Date.now();
            const lastTime = lastRequestTime[`messages_${chatId}`] || 0;
            if (!isInitialLoad && (now - lastTime) < 800) {
                return;
            }
            lastRequestTime[`messages_${chatId}`] = now;
            
            isLoadingMessages[chatId] = true;
            
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
                            
                            // Проверяем изменения позиций для всех чатов
                            // (бекенд сам определит, нужно ли что-то менять)
                            setTimeout(() => {
                                checkForPositionChanges();
                            }, 1000);
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
                
                // Освобождаем флаг загрузки
                isLoadingMessages[chatId] = false;
            }
        }
        
        // Отрисовка сообщений (для первой загрузки)
        function renderMessages(chatId, messages) {
            const container = document.getElementById(`messages-${chatId}`);
            
            if (messages.length === 0) {
                container.innerHTML = '<div class="loading">Нет сообщений</div>';
                hasOlderMessages[chatId] = false;
                return;
            }
            
            // Сообщения уже в правильном порядке (от старых к новым) благодаря исправлениям в модели
            container.innerHTML = messages.map(msg => `
                <div class="message ${msg.is_outgoing ? 'outgoing' : (msg.is_telegram ? 'telegram' : 'other')}" data-message-id="${msg.id}">
                    ${(msg.is_telegram || msg.is_outgoing) ? '<div class="user">' + msg.user + '</div>' : ''}
                    <div>${msg.message}</div>
                    <div class="time">${msg.timestamp}${msg.message_type && msg.message_type !== 'text' ? '<span class="message-type">' + getMessageTypeDisplay(msg.message_type) + '</span>' : ''}</div>
                </div>
            `).join('');
            
            // Устанавливаем ID первого и последнего сообщения для пагинации
            if (messages.length > 0) {
                firstMessageIds[chatId] = messages[0].id;
                lastMessageIds[chatId] = messages[messages.length - 1].id;
                hasOlderMessages[chatId] = messages.length >= 20; // Если загрузили полных 20, то есть еще
            }
            
            // Прокручиваем к низу после загрузки - используем несколько попыток для надежности
            setTimeout(() => {
                scrollChatToBottom(chatId);
                // Дополнительная попытка через 500мс для медленных устройств
                setTimeout(() => {
                    scrollChatToBottom(chatId);
                }, 500);
            }, 100);
        }
        
        // Прокрутка чата вниз (с анимацией)
        function scrollChatToBottom(chatId) {
            const container = document.getElementById(`messages-${chatId}`);
            if (container) {
                // Используем requestAnimationFrame для более стабильной прокрутки
                requestAnimationFrame(() => {
                    const scrollHeight = container.scrollHeight;
                    const clientHeight = container.clientHeight;
                    
                    // Проверяем, что есть контент для прокрутки
                    if (scrollHeight > clientHeight) {
                        container.scrollTo({
                            top: scrollHeight,
                            behavior: 'smooth'
                        });
                    }
                });
            }
        }
        
        // Загрузка новых сообщений для swapped чатов
        function loadNewMessagesForSwappedChats(element, chat) {
            const messagesContainer = element.querySelector('.chat-messages');
            if (messagesContainer) {
                // Очищаем контейнер и показываем загрузку
                messagesContainer.innerHTML = '<div class="loading">Загрузка сообщений...</div>';
                
                // Сбрасываем переменные для пагинации
                delete lastMessageIds[chat.id];
                delete firstMessageIds[chat.id];
                delete hasOlderMessages[chat.id];
                delete isLoadingMessages[chat.id];
                delete isLoadingOldMessages[chat.id];
                
                // Загружаем новые сообщения
                loadChatMessages(chat.id, true);
                
                console.log('🔄 Loading new messages for swapped chat:', chat.title || chat.username);
            }
        }
        
        // Настройка обработчика скролла для загрузки старых сообщений
        function setupScrollListener(chatId) {
            const container = document.getElementById(`messages-${chatId}`);
            if (container) {
                // Добавляем небольшую задержку чтобы избежать срабатывания при первой загрузке
                let scrollListenerActive = false;
                
                // Активируем слушатель скролла через 2 секунды после загрузки чата
                setTimeout(() => {
                    scrollListenerActive = true;
                }, 2000);
                
                // Функция проверки необходимости загрузки старых сообщений
                const checkLoadOldMessages = () => {
                    // Загружаем старые сообщения только если:
                    // 1. Слушатель активирован
                    // 2. Прокрутили в самый верх (с небольшим отступом) 
                    // 3. Не загружаем сейчас
                    // 4. Есть старые сообщения для загрузки
                    if (scrollListenerActive && 
                        container.scrollTop <= 20 && 
                        !isLoadingOldMessages[chatId] && 
                        hasOlderMessages[chatId] !== false) {
                        
                        console.log(`🔄 Loading old messages for chat ${chatId}, scrollTop: ${container.scrollTop}`);
                        loadOldMessages(chatId);
                    }
                };
                
                // Обрабатываем событие scroll (колесико мыши, клавиши)
                container.addEventListener('scroll', checkLoadOldMessages);
                
                // Обрабатываем событие scrollend (когда завершается программный скролл)
                if ('onscrollend' in container) {
                    container.addEventListener('scrollend', checkLoadOldMessages);
                }
                
                // Для старых браузеров - используем throttled версию
                let scrollTimeout;
                const throttledCheck = () => {
                    if (scrollTimeout) clearTimeout(scrollTimeout);
                    scrollTimeout = setTimeout(checkLoadOldMessages, 100);
                };
                
                // Дополнительные события для перемещения ползунка мышкой
                container.addEventListener('mouseup', throttledCheck);
                container.addEventListener('touchend', throttledCheck);
                
                // Периодическая проверка позиции скролла (для случаев когда события не срабатывают)
                let lastScrollTop = container.scrollTop;
                const scrollChecker = setInterval(() => {
                    if (container.scrollTop !== lastScrollTop) {
                        lastScrollTop = container.scrollTop;
                        throttledCheck();
                    }
                }, 200); // Проверяем каждые 200мс
                
                // Сохраняем интервал для очистки при необходимости
                if (!window.scrollCheckers) window.scrollCheckers = {};
                window.scrollCheckers[chatId] = scrollChecker;
                
                // Дополнительные события для мобильных устройств
                container.addEventListener('touchmove', throttledCheck);
                container.addEventListener('touchcancel', throttledCheck);
                
                // Событие для отслеживания drag операций на скроллбаре
                container.addEventListener('mousedown', () => {
                    const checkWhileDragging = () => {
                        throttledCheck();
                        if (container.scrollTop !== lastScrollTop) {
                            requestAnimationFrame(checkWhileDragging);
                        }
                    };
                    requestAnimationFrame(checkWhileDragging);
                });
            }
        }
        
        // Загрузка старых сообщений при скролле вверх
        async function loadOldMessages(chatId) {
            if (isLoadingOldMessages[chatId]) return;
            
            const firstMessageId = firstMessageIds[chatId];
            if (!firstMessageId) return;
            
            isLoadingOldMessages[chatId] = true;
            
            try {
                const response = await fetch(`/api/chats/${chatId}/messages?before=${firstMessageId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.messages && data.messages.length > 0) {
                        prependOldMessages(chatId, data.messages);
                        hasOlderMessages[chatId] = data.has_older;
                    } else {
                        hasOlderMessages[chatId] = false;
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки старых сообщений:', error);
            } finally {
                isLoadingOldMessages[chatId] = false;
            }
        }
        
        // Добавление старых сообщений в начало контейнера
        function prependOldMessages(chatId, messages) {
            const container = document.getElementById(`messages-${chatId}`);
            if (!container || messages.length === 0) return;
            
            const oldScrollHeight = container.scrollHeight;
            const oldScrollTop = container.scrollTop;
            
            // Создаем документный фрагмент для лучшей производительности
            const fragment = document.createDocumentFragment();
            
            // Добавляем сообщения в правильном порядке (они уже отсортированы от старых к новым)
            messages.forEach((msg, index) => {
                const messageEl = document.createElement('div');
                messageEl.className = `message ${msg.is_outgoing ? 'outgoing' : (msg.is_telegram ? 'telegram' : 'other')}`;
                messageEl.setAttribute('data-message-id', msg.id);
                messageEl.innerHTML = `
                    ${(msg.is_telegram || msg.is_outgoing) ? '<div class="user">' + msg.user + '</div>' : ''}
                    <div>${msg.message}</div>
                    <div class="time">${msg.timestamp}${msg.message_type && msg.message_type !== 'text' ? '<span class="message-type">' + getMessageTypeDisplay(msg.message_type) + '</span>' : ''}</div>
                `;
                
                fragment.appendChild(messageEl);
            });
            
            // Вставляем все сообщения одним блоком в начало контейнера
            container.insertBefore(fragment, container.firstChild);
            
            // Обновляем firstMessageId
            if (messages.length > 0) {
                firstMessageIds[chatId] = messages[0].id;
            }
            
            // Сохраняем позицию скролла более точно
            const newScrollHeight = container.scrollHeight;
            const scrollDiff = newScrollHeight - oldScrollHeight;
            container.scrollTop = oldScrollTop + scrollDiff;
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
            
            let addedMessages = 0;
            
            // Добавляем новые сообщения (уже в правильном порядке от старых к новым)
            messages.forEach((msg, index) => {
                // Проверяем, не существует ли уже это сообщение
                const existingMessage = container.querySelector(`[data-message-id="${msg.id}"]`);
                if (existingMessage) {
                    console.log(`Сообщение ${msg.id} уже существует, пропускаем`);
                    return; // Пропускаем дублирующее сообщение
                }
                
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
                addedMessages++;
                
                // Запускаем анимацию с задержкой
                setTimeout(() => {
                    messageEl.style.opacity = '1';
                    messageEl.style.transform = 'translateY(0)';
                }, 10 + (addedMessages * 50));
                
                // Убираем inline стили после завершения анимации
                setTimeout(() => {
                    if (messageEl.parentNode) {
                        messageEl.style.removeProperty('opacity');
                        messageEl.style.removeProperty('transform');
                        messageEl.style.removeProperty('transition');
                    }
                }, 600 + (addedMessages * 50));
            });
            
            // Прокрутка к новым сообщениям только если были добавлены новые
            if (addedMessages > 0) {
                setTimeout(() => {
                    scrollChatToBottom(chatId);
                }, 100);
            }
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
        
        // Остановка polling для чата
        function stopMessagePolling(chatId) {
            if (messageIntervals[chatId]) {
                clearInterval(messageIntervals[chatId]);
                delete messageIntervals[chatId];
                console.log('🔴 Stopped polling for chat:', chatId);
            }
            
            // Очищаем scroll checker для этого чата
            if (window.scrollCheckers && window.scrollCheckers[chatId]) {
                clearInterval(window.scrollCheckers[chatId]);
                delete window.scrollCheckers[chatId];
                console.log('🔴 Stopped scroll checker for chat:', chatId);
            }
        }
        
        // Запуск проверки новых чатов и позиций
        function startChatChecking() {
            chatCheckInterval = setInterval(async () => {
                await checkNewChats();
                // Проверяем изменения позиций каждые 3 секунды
                await checkForPositionChanges();
            }, 3000); // Проверяем каждые 3 секунды
        }
        
        // Проверка новых чатов
        async function checkNewChats() {
            // Предотвращаем конкурентные запросы
            if (isCheckingNewChats) {
                return;
            }
            
            // Не проверяем если страница скрыта
            if (!isPageVisible) {
                return;
            }
            
            // Throttling: не делаем запросы чаще чем раз в 800мс
            const now = Date.now();
            const lastTime = lastRequestTime['newChats'] || 0;
            if ((now - lastTime) < 800) {
                return;
            }
            lastRequestTime['newChats'] = now;
            
            isCheckingNewChats = true;
            
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
            } finally {
                // Освобождаем флаг проверки
                isCheckingNewChats = false;
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
            // Добавляем обработчик скролла для нового чата с задержкой
            setTimeout(() => {
                setupScrollListener(chat.id);
            }, 1000);
            
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
                    
                    // Не нужно дополнительно загружать сообщения - polling сделает это автоматически
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
            
            // Очищаем флаги загрузки
            isLoadingMessages = {};
            isCheckingNewChats = false;
            lastRequestTime = {};
            
            // Обновляем только список чатов БЕЗ полной перезагрузки HTML
            updateChatsList();
            
            // Запускаем проверку позиций и новых чатов
            if (!chatCheckInterval) {
                startChatChecking();
            }
        }

        // Обновление списка чатов без перезагрузки HTML
        async function updateChatsList() {
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
                    
                    // Обновляем список существующих чатов
                    existingChatIds.clear();
                    chats.forEach(chat => existingChatIds.add(chat.id));
                    
                    // Перезапускаем polling для существующих чатов
                    chats.forEach(chat => {
                        if (!messageIntervals[chat.id]) {
                            startMessagePolling(chat.id);
                        }
                    });
                }
            } catch (error) {
                console.error('Ошибка обновления списка чатов:', error);
            }
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
        
        // Обработка видимости страницы для предотвращения проблем на мобильных
        let resumeTimeout = null;
        document.addEventListener('visibilitychange', function() {
            isPageVisible = !document.hidden;
            
            if (isPageVisible) {
                console.log('Страница стала видимой - возобновляем обновления');
                // Debounce возобновления на 1 секунду для стабильности
                if (resumeTimeout) clearTimeout(resumeTimeout);
                resumeTimeout = setTimeout(() => {
                    resumePolling();
                }, 1000);
            } else {
                console.log('Страница скрыта - приостанавливаем обновления');
                // Отменяем запланированное возобновление
                if (resumeTimeout) {
                    clearTimeout(resumeTimeout);
                    resumeTimeout = null;
                }
            }
        });
        
        // Возобновление polling после возвращения видимости
        function resumePolling() {
            // Если интервалы не работают - перезапускаем
            if (!chatCheckInterval) {
                startChatChecking();
            }
            
            // Проверяем интервалы сообщений для каждого чата
            chats.forEach(chat => {
                if (!messageIntervals[chat.id]) {
                    startMessagePolling(chat.id);
                }
            });
        }
        
        // Broadcast Modal Functionality
        const broadcastModal = document.getElementById('broadcast-modal');
        const broadcastBtn = document.getElementById('broadcast-btn');
        const modalClose = document.getElementById('modal-close');
        const cancelBroadcast = document.getElementById('cancel-broadcast');
        const broadcastForm = document.getElementById('broadcast-form');
        const broadcastMessage = document.getElementById('broadcast-message');
        const sendBroadcast = document.getElementById('send-broadcast');
        const broadcastStatus = document.getElementById('broadcast-status');

        // Открытие модального окна
        broadcastBtn.addEventListener('click', function() {
            broadcastModal.style.display = 'block';
            broadcastMessage.focus();
        });

        // Закрытие модального окна
        function closeBroadcastModal() {
            broadcastModal.style.display = 'none';
            broadcastMessage.value = '';
            hideBroadcastStatus();
        }

        modalClose.addEventListener('click', closeBroadcastModal);
        cancelBroadcast.addEventListener('click', closeBroadcastModal);

        // Закрытие по клику вне модала
        window.addEventListener('click', function(event) {
            if (event.target === broadcastModal) {
                closeBroadcastModal();
            }
        });

        // Отправка рассылки
        broadcastForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const message = broadcastMessage.value.trim();
            if (!message) {
                showBroadcastStatus('error', '❌ Введите сообщение для рассылки');
                return;
            }

            // Блокируем интерфейс
            sendBroadcast.disabled = true;
            broadcastMessage.disabled = true;
            
            showBroadcastStatus('sending', '📤 Отправка рассылки...');

            try {
                const response = await fetch('/api/broadcast', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        message: message
                    })
                });

                const data = await response.json();

                if (data.success) {
                    showBroadcastStatus('success', `✅ Рассылка отправлена в ${data.sent_count} чатов`);
                    
                    // Закрываем модал через 2 секунды
                    setTimeout(() => {
                        closeBroadcastModal();
                    }, 2000);
                } else {
                    showBroadcastStatus('error', `❌ ${data.message || 'Ошибка рассылки'}`);
                }

            } catch (error) {
                console.error('Ошибка рассылки:', error);
                showBroadcastStatus('error', '❌ Ошибка соединения');
            } finally {
                // Разблокируем интерфейс
                sendBroadcast.disabled = false;
                broadcastMessage.disabled = false;
            }
        });

        // Показать статус рассылки
        function showBroadcastStatus(type, message) {
            broadcastStatus.className = `broadcast-status ${type}`;
            broadcastStatus.textContent = message;
            broadcastStatus.style.display = 'block';
        }

        // Скрыть статус рассылки
        function hideBroadcastStatus() {
            broadcastStatus.style.display = 'none';
            broadcastStatus.className = 'broadcast-status';
            broadcastStatus.textContent = '';
        }

        // Загрузка при старте
        document.addEventListener('DOMContentLoaded', function() {
            loadChats();
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
            
            // Очищаем все scroll checkers
            if (window.scrollCheckers) {
                Object.values(window.scrollCheckers).forEach(checker => {
                    if (checker) clearInterval(checker);
                });
                window.scrollCheckers = {};
            }
            
            // Очищаем флаги
            isLoadingMessages = {};
            isCheckingNewChats = false;
            lastRequestTime = {};
        }
        

        
        // Очистка интервалов при закрытии страницы
        window.addEventListener('beforeunload', function() {
            cleanupIntervals();
        });
    </script>
</body>
</html>
