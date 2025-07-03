<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard - Чат</title>
    
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
        
        .header a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            background: rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
        }
        
        .header a:hover {
            background: rgba(255, 255, 255, 0.2);
            color: white;
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
        
        .chat-header:nth-child(2n) {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
        }
        
        .chat-header:nth-child(3n) {
            background: linear-gradient(135deg, #f59e0b 0%, #ef4444 100%);
            color: white;
        }
        
        .chat-header:nth-child(4n) {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
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
        
        .message .time {
            font-size: 0.75rem;
            opacity: 0.7;
            margin-top: 0.25rem;
        }
        
        .chat-input {
            padding: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(15, 15, 35, 0.8);
        }
        
        .input-group {
            display: flex;
            gap: 0.5rem;
        }
        
        .input-group input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 1.5rem;
            outline: none;
            font-size: 0.875rem;
            background: rgba(30, 30, 60, 0.6);
            color: #e2e8f0;
            transition: border-color 0.3s ease;
        }
        
        .input-group input::placeholder {
            color: rgba(226, 232, 240, 0.6);
        }
        
        .input-group input:focus {
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.3);
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
        }
        
        .send-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(30, 58, 138, 0.4);
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
        }
        
        @media (min-width: 1920px) {
            .chat-grid {
                grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            }
        }
        
        @media (min-width: 2560px) {
            .chat-grid {
                grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>💬 Dashboard - Чат</h1>
        <div class="user-info">
            <span>{{ auth()->user()->name }}</span>
            <a href="{{ url('/') }}">Главная</a>
            <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                @csrf
                <button type="submit" style="background: rgba(255, 255, 255, 0.1); border: none; color: rgba(255, 255, 255, 0.8); cursor: pointer; padding: 0.5rem 1rem; border-radius: 0.5rem; transition: all 0.3s ease;" onmouseover="this.style.background='rgba(255, 255, 255, 0.2)'" onmouseout="this.style.background='rgba(255, 255, 255, 0.1)'">
                    Выйти
                </button>
            </form>
        </div>
    </div>
    
    <div class="container">
        <div class="chat-grid">
            <!-- Чат 1 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">А</div>
                    <div class="chat-info">
                        <h3>Алексей Смирнов</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-1">
                    <div class="message other">
                        <div>Привет! Как дела?</div>
                        <div class="time">14:30</div>
                    </div>
                    <div class="message own">
                        <div>Привет! Всё отлично, спасибо!</div>
                        <div class="time">14:32</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 1)">
                        <button class="send-btn" onclick="sendMessage(1)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Чат 2 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">М</div>
                    <div class="chat-info">
                        <h3>Мария Петрова</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-2">
                    <div class="message other">
                        <div>Добро пожаловать в наш чат!</div>
                        <div class="time">15:45</div>
                    </div>
                    <div class="message own">
                        <div>Спасибо! Рад быть здесь</div>
                        <div class="time">15:46</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 2)">
                        <button class="send-btn" onclick="sendMessage(2)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Чат 3 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">Д</div>
                    <div class="chat-info">
                        <h3>Дмитрий Козлов</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-3">
                    <div class="message other">
                        <div>Laravel отличный фреймворк!</div>
                        <div class="time">16:20</div>
                    </div>
                    <div class="message own">
                        <div>Да, согласен! Очень удобный</div>
                        <div class="time">16:22</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 3)">
                        <button class="send-btn" onclick="sendMessage(3)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Чат 4 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">Е</div>
                    <div class="chat-info">
                        <h3>Елена Волкова</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-4">
                    <div class="message other">
                        <div>Как проект продвигается?</div>
                        <div class="time">17:10</div>
                    </div>
                    <div class="message own">
                        <div>Всё идёт по плану! 🚀</div>
                        <div class="time">17:12</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 4)">
                        <button class="send-btn" onclick="sendMessage(4)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Чат 5 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">И</div>
                    <div class="chat-info">
                        <h3>Иван Соколов</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-5">
                    <div class="message other">
                        <div>Отличная работа с дизайном!</div>
                        <div class="time">18:00</div>
                    </div>
                    <div class="message own">
                        <div>Спасибо! Стараюсь 😊</div>
                        <div class="time">18:02</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 5)">
                        <button class="send-btn" onclick="sendMessage(5)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Чат 6 -->
            <div class="chat-window">
                <div class="chat-header">
                    <div class="chat-avatar">С</div>
                    <div class="chat-info">
                        <h3>Светлана Орлова</h3>
                        <p>В сети</p>
                    </div>
                    <div class="online-indicator"></div>
                </div>
                <div class="chat-messages" id="messages-6">
                    <div class="message other">
                        <div>Увидимся завтра на встрече!</div>
                        <div class="time">19:30</div>
                    </div>
                    <div class="message own">
                        <div>Конечно! До встречи 👋</div>
                        <div class="time">19:32</div>
                    </div>
                </div>
                <div class="chat-input">
                    <div class="input-group">
                        <input type="text" placeholder="Введите сообщение..." onkeypress="handleKeyPress(event, 6)">
                        <button class="send-btn" onclick="sendMessage(6)">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let lastMessageId = 0;
        
        // Функция для отправки сообщения
        async function sendMessage(chatId) {
            const input = document.querySelector(`#messages-${chatId}`).parentElement.querySelector('input');
            const message = input.value.trim();
            
            if (!message) return;
            
            // Добавляем сообщение локально
            addMessage(chatId, message, true);
            input.value = '';
            
            // Отправляем на сервер
            try {
                const response = await fetch('/send-message?message=' + encodeURIComponent(message), {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    console.log('Сообщение отправлено');
                }
            } catch (error) {
                console.error('Ошибка отправки:', error);
            }
        }
        
        // Функция для добавления сообщения в чат
        function addMessage(chatId, message, isOwn = false) {
            const messagesContainer = document.getElementById(`messages-${chatId}`);
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${isOwn ? 'own' : 'other'}`;
            
            const now = new Date();
            const time = now.getHours().toString().padStart(2, '0') + ':' + 
                        now.getMinutes().toString().padStart(2, '0');
            
            messageDiv.innerHTML = `
                <div>${message}</div>
                <div class="time">${time}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }
        
        // Обработчик Enter
        function handleKeyPress(event, chatId) {
            if (event.key === 'Enter') {
                sendMessage(chatId);
            }
        }
        
        // Функция для получения новых сообщений
        async function fetchMessages() {
            try {
                const response = await fetch('/api/messages/latest?after=' + lastMessageId, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.messages && data.messages.length > 0) {
                        data.messages.forEach(msg => {
                            // Добавляем сообщение в случайный чат (от 1 до 6)
                            const randomChatId = Math.floor(Math.random() * 6) + 1;
                            addMessage(randomChatId, `${msg.user}: ${msg.message}`, false);
                            lastMessageId = Math.max(lastMessageId, msg.id || 0);
                        });
                    }
                }
            } catch (error) {
                console.error('Ошибка получения сообщений:', error);
            }
        }
        
        // Запускаем polling каждые 3 секунды
        setInterval(fetchMessages, 3000);
        fetchMessages(); // Первая загрузка
        
        // Имитация входящих сообщений
        const sampleMessages = [
            'Привет! Как дела?',
            'Отличная погода сегодня!',
            'Что делаешь?',
            'Laravel - супер!',
            'Хорошего дня! 😊',
            'Как проект?',
            'Увидимся позже',
            'Спасибо за помощь!',
            'Всё получится! 💪',
            'До свидания!'
        ];
        
        // Добавляем случайные сообщения каждые 10-20 секунд
        setInterval(() => {
            const randomChat = Math.floor(Math.random() * 6) + 1;
            const randomMessage = sampleMessages[Math.floor(Math.random() * sampleMessages.length)];
            addMessage(randomChat, randomMessage, false);
        }, Math.random() * 10000 + 10000);
    </script>
</body>
</html>
