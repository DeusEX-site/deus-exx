<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Анализ Капы - Telegram Чаты</title>
    
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
        
        .container {
            padding: 2rem;
        }
        
        .search-section {
            background: rgba(30, 30, 60, 0.9);
            border-radius: 1rem;
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .search-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            align-items: end;
            margin-bottom: 2rem;
        }
        
        .form-buttons {
            grid-column: 1 / -1;
            display: flex;
            gap: 1rem;
            justify-content: flex-start;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
            margin-bottom: 0.5rem;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 0.5rem;
            background: rgba(15, 15, 35, 0.8);
            color: #e2e8f0;
            font-size: 0.875rem;
        }
        
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #1e3a8a;
            box-shadow: 0 0 0 3px rgba(30, 58, 138, 0.3);
        }
        
        .search-btn {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .search-btn:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
            transform: translateY(-1px);
        }
        
        .search-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .stats-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: rgba(30, 30, 60, 0.9);
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .stat-card h3 {
            color: #10b981;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .stat-card p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
        }
        
        .results-section {
            background: rgba(30, 30, 60, 0.9);
            border-radius: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
        }
        
        .results-header {
            background: rgba(15, 15, 35, 0.8);
            padding: 1rem 2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .results-header h2 {
            color: white;
            font-size: 1.25rem;
            font-weight: 600;
        }
        
        .export-btn {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
            border: 1px solid #10b981;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        
        .export-btn:hover {
            background: rgba(16, 185, 129, 0.3);
        }
        
        .message-list {
            /* Убираем ограничение высоты - пусть страница будет длинной */
        }
        
        .message-item {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            transition: background-color 0.3s ease;
        }
        
        .message-item:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .message-item:last-child {
            border-bottom: none;
        }
        
        .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.75rem;
        }
        
        .message-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .chat-name {
            background: rgba(30, 58, 138, 0.3);
            color: #60a5fa;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .message-author {
            background: rgba(16, 185, 129, 0.3);
            color: #10b981;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .message-date {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.75rem;
        }
        
        .message-text {
            background: rgba(15, 15, 35, 0.6);
            padding: 1rem;
            border-radius: 0.5rem;
            border-left: 3px solid #10b981;
            margin-bottom: 1rem;
            word-wrap: break-word;
            line-height: 1.5;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        
        .analysis-section {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 1rem;
        }
        
        .analysis-item {
            background: rgba(55, 65, 81, 0.5);
            padding: 0.75rem;
            border-radius: 0.5rem;
            text-align: center;
        }
        
        .analysis-item.positive {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
        }
        
        .analysis-item.negative {
            background: rgba(239, 68, 68, 0.2);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }
        
        .analysis-item.warning {
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.3);
        }
        
        .analysis-item.critical {
            background: rgba(239, 68, 68, 0.3);
            border: 1px solid rgba(239, 68, 68, 0.5);
        }
        
        .analysis-item .label {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 0.25rem;
        }
        
        .analysis-item .value {
            font-weight: 600;
            color: white;
        }
        
        .infinity-indicator {
            font-style: italic;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .loading {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .no-results {
            text-align: center;
            padding: 3rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .highlight {
            background: rgba(245, 158, 11, 0.3);
            padding: 0.125rem 0.25rem;
            border-radius: 0.25rem;
            font-weight: 600;
        }
        
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: rgba(15, 15, 35, 0.8);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #1e3a8a 0%, #0f172a 100%);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #1d4ed8 0%, #1e3a8a 100%);
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .form-group {
                min-width: 100%;
            }
            
            .stats-section {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .analysis-section {
                grid-template-columns: 1fr;
            }
        }
        
        /* Стили для истории капы */
        .cap-history-toggle {
            background: rgba(59, 130, 246, 0.2);
            border: 1px solid rgba(59, 130, 246, 0.3);
            color: #60a5fa;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            margin-top: 1rem;
            transition: all 0.3s ease;
            display: block;
            width: 100%;
            text-align: center;
        }
        
        .cap-history-toggle:hover {
            background: rgba(59, 130, 246, 0.3);
            border-color: rgba(59, 130, 246, 0.5);
        }
        
        .cap-history-toggle:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .cap-history-content {
            margin-top: 1rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding-top: 1rem;
            display: none;
        }
        
        .cap-history-content.show {
            display: block;
        }
        
        .history-item {
            background: rgba(55, 65, 81, 0.5);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .history-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .history-item-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.7);
            display: flex;
            gap: 1rem;
        }
        
        .history-item-text {
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
            line-height: 1.4;
        }
        
        .history-item-analysis {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.5rem;
            font-size: 0.75rem;
        }
        
        .history-analysis-item {
            background: rgba(30, 30, 60, 0.5);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            text-align: center;
        }
        
        .history-analysis-item .label {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.625rem;
            margin-bottom: 0.125rem;
        }
        
        .history-analysis-item .value {
            color: rgba(255, 255, 255, 0.9);
            font-weight: 500;
        }
        
        .history-loading {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .history-empty {
            text-align: center;
            padding: 2rem;
            color: rgba(255, 255, 255, 0.6);
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>📊 Анализ Капы</h1>
        <div class="header-controls">
            <div class="user-info">
                <span>{{ auth()->user()->name }}</span>
                <a href="{{ route('dashboard') }}">Главная</a>
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit">Выйти</button>
                </form>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Search Section -->
        <div class="search-section">
            <form class="search-form" id="search-form">
                <div class="form-group">
                    <label for="search">Дополнительный поиск</label>
                    <input type="text" id="search" placeholder="Введите текст для поиска...">
                </div>
                <div class="form-group">
                    <label for="chat-select">Чат</label>
                    <select id="chat-select">
                        <option value="">Все чаты</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="geo-filter">Гео</label>
                    <select id="geo-filter">
                        <option value="">Все гео</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="broker-filter">Брокер</label>
                    <select id="broker-filter">
                        <option value="">Все брокеры</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="affiliate-filter">Аффилейт</label>
                    <select id="affiliate-filter">
                        <option value="">Все аффилейты</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="language-filter">Язык</label>
                    <select id="language-filter">
                        <option value="">Все языки</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="funnel-filter">Воронка</label>
                    <select id="funnel-filter">
                        <option value="">Все воронки</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="schedule-filter">Расписание</label>
                    <select id="schedule-filter">
                        <option value="">Все</option>
                        <option value="24_7">24/7</option>
                        <option value="has_schedule">Есть расписание</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="total-filter">Общий лимит</label>
                    <select id="total-filter">
                        <option value="">Все</option>
                        <option value="has_total">Есть лимит</option>
                        <option value="infinity">Бесконечность</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="status-filter">Статус</label>
                    <select id="status-filter">
                        <option value="">Все (кроме удаленных)</option>
                        <option value="RUN">Активные</option>
                        <option value="STOP">Остановленные</option>
                        <option value="DELETE">Корзина (удаленные)</option>
                        <option value="all">Все (включая удаленные)</option>
                    </select>
                </div>
                <div class="form-buttons">
                    <button type="submit" class="search-btn" id="search-btn">
                        🔍 Найти
                    </button>
                    <button type="button" class="search-btn" id="clear-filters-btn" style="background: rgba(239, 68, 68, 0.3);">
                        🗑️ Очистить фильтры
                    </button>
                </div>
            </form>
        </div>

        <!-- Stats Section -->
        <div class="stats-section" id="stats-section" style="display: none;">
            <div class="stat-card">
                <h3 id="total-cap-amount">0</h3>
                <p>Общая капа</p>
            </div>
            <div class="stat-card">
                <h3 id="unique-geos">0</h3>
                <p>Количество гео</p>
            </div>
            <div class="stat-card">
                <h3 id="unique-affiliates">0</h3>
                <p>Количество аффилейтов</p>
            </div>
            <div class="stat-card">
                <h3 id="unique-brokers">0</h3>
                <p>Количество брокеров</p>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <h2>Результаты анализа</h2>
                <button class="export-btn" id="export-btn" style="display: none;">
                    💾 Экспорт CSV
                </button>
            </div>
            
            <div class="message-list" id="message-list">
                <div class="loading" id="loading">
                    🔍 Введите поисковый запрос и нажмите "Найти"
                </div>
            </div>
        </div>
    </div>

    <script>
        let currentMessages = [];
        let chats = [];
        
        // Загрузка списка чатов
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
                    
                    const chatSelect = document.getElementById('chat-select');
                    if (chatSelect) {
                        chatSelect.innerHTML = '<option value="">Все чаты</option>';
                        
                        chats.forEach(chat => {
                            const option = document.createElement('option');
                            option.value = chat.id;
                            option.textContent = chat.title || chat.username || `Чат #${chat.chat_id}`;
                            chatSelect.appendChild(option);
                        });
                    } else {
                        console.error('Элемент chat-select не найден');
                    }
                }
            } catch (error) {
                console.error('Ошибка загрузки чатов:', error);
            }
        }

        // Загрузка списков для фильтров
        async function loadFilterOptions() {
            console.log('Загрузка опций фильтров...');
            
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]');
                if (!csrfToken) {
                    console.warn('CSRF токен не найден');
                    return;
                }

                const response = await fetch('/api/cap-analysis-filters', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': csrfToken.getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    console.log('Данные фильтров получены:', data);
                    
                    if (data.success) {
                        // Загрузка гео
                        const geoFilter = document.getElementById('geo-filter');
                        if (geoFilter && data.geos && data.geos.length > 0) {
                            geoFilter.innerHTML = '<option value="">Все гео</option>';
                            data.geos.forEach(geo => {
                                const option = document.createElement('option');
                                option.value = geo;
                                option.textContent = geo;
                                geoFilter.appendChild(option);
                            });
                            console.log(`Загружено ${data.geos.length} гео`);
                        } else if (!geoFilter) {
                            console.warn('Элемент geo-filter не найден');
                        }

                        // Загрузка брокеров
                        const brokerFilter = document.getElementById('broker-filter');
                        if (brokerFilter && data.brokers && data.brokers.length > 0) {
                            brokerFilter.innerHTML = '<option value="">Все брокеры</option>';
                            data.brokers.forEach(broker => {
                                const option = document.createElement('option');
                                option.value = broker;
                                option.textContent = broker;
                                brokerFilter.appendChild(option);
                            });
                            console.log(`Загружено ${data.brokers.length} брокеров`);
                        } else if (!brokerFilter) {
                            console.warn('Элемент broker-filter не найден');
                        }

                        // Загрузка аффилейтов
                        const affiliateFilter = document.getElementById('affiliate-filter');
                        if (affiliateFilter && data.affiliates && data.affiliates.length > 0) {
                            affiliateFilter.innerHTML = '<option value="">Все аффилейты</option>';
                            data.affiliates.forEach(affiliate => {
                                const option = document.createElement('option');
                                option.value = affiliate;
                                option.textContent = affiliate;
                                affiliateFilter.appendChild(option);
                            });
                            console.log(`Загружено ${data.affiliates.length} аффилейтов`);
                        } else if (!affiliateFilter) {
                            console.warn('Элемент affiliate-filter не найден');
                        }

                        // Загрузка языков
                        const languageFilter = document.getElementById('language-filter');
                        if (languageFilter && data.languages && data.languages.length > 0) {
                            languageFilter.innerHTML = '<option value="">Все языки</option>';
                            data.languages.forEach(language => {
                                const option = document.createElement('option');
                                option.value = language;
                                option.textContent = language;
                                languageFilter.appendChild(option);
                            });
                            console.log(`Загружено ${data.languages.length} языков`);
                        } else if (!languageFilter) {
                            console.warn('Элемент language-filter не найден');
                        }

                        // Загрузка воронок
                        const funnelFilter = document.getElementById('funnel-filter');
                        if (funnelFilter && data.funnels && data.funnels.length > 0) {
                            funnelFilter.innerHTML = '<option value="">Все воронки</option>';
                            data.funnels.forEach(funnel => {
                                const option = document.createElement('option');
                                option.value = funnel;
                                option.textContent = funnel;
                                funnelFilter.appendChild(option);
                            });
                            console.log(`Загружено ${data.funnels.length} воронок`);
                        } else if (!funnelFilter) {
                            console.warn('Элемент funnel-filter не найден');
                        }
                        
                        console.log('Опции фильтров загружены успешно');
                    } else {
                        console.error('Ошибка в ответе сервера:', data.message);
                    }
                } else {
                    console.error('Ошибка HTTP:', response.status, response.statusText);
                }
            } catch (error) {
                console.error('Ошибка загрузки фильтров:', error);
            }
        }

        // Очистка всех фильтров
        function clearFilters() {
            // Очищаем элементы безопасно
            const elements = [
                'search', 'chat-select', 'geo-filter', 'broker-filter', 
                'affiliate-filter', 'language-filter', 'funnel-filter', 
                'schedule-filter', 'total-filter', 'status-filter'
            ];
            
            elements.forEach(id => {
                const element = document.getElementById(id);
                if (element) {
                    element.value = '';
                } else {
                    console.warn(`Элемент ${id} не найден при очистке фильтров`);
                }
            });
            
            // Очистка результатов
            const messageList = document.getElementById('message-list');
            const statsSection = document.getElementById('stats-section');
            if (messageList) messageList.innerHTML = '<div class="loading">🔍 Введите поисковый запрос и нажмите "Найти"</div>';
            if (statsSection) statsSection.style.display = 'none';
            
            currentMessages = [];
            
            console.log('Фильтры очищены');
        }
        
        // Поиск сообщений
        async function searchMessages() {
            // Получаем основные элементы (обязательные)
            const searchBtn = document.getElementById('search-btn');
            const messageList = document.getElementById('message-list');
            const statsSection = document.getElementById('stats-section');
            const searchInput = document.getElementById('search');
            const chatSelect = document.getElementById('chat-select');
            
            // Проверяем наличие основных элементов
            if (!searchBtn || !messageList || !statsSection || !searchInput || !chatSelect) {
                console.error('Ошибка: не найдены основные элементы DOM', {
                    searchBtn: !!searchBtn,
                    messageList: !!messageList,
                    statsSection: !!statsSection,
                    searchInput: !!searchInput,
                    chatSelect: !!chatSelect
                });
                return;
            }

            // Получаем элементы фильтров (необязательные)
            const geoFilter = document.getElementById('geo-filter');
            const brokerFilter = document.getElementById('broker-filter');
            const affiliateFilter = document.getElementById('affiliate-filter');
            const languageFilter = document.getElementById('language-filter');
            const funnelFilter = document.getElementById('funnel-filter');
            const scheduleFilter = document.getElementById('schedule-filter');
            const totalFilter = document.getElementById('total-filter');
            const statusFilter = document.getElementById('status-filter');
            
            const search = searchInput.value;
            const chatId = chatSelect.value;
            const geo = geoFilter?.value || '';
            const broker = brokerFilter?.value || '';
            const affiliate = affiliateFilter?.value || '';
            const language = languageFilter?.value || '';
            const funnel = funnelFilter?.value || '';
            const schedule = scheduleFilter?.value || '';
            const total = totalFilter?.value || '';
            const status = statusFilter?.value || '';

            console.log('Параметры поиска:', {
                search, chatId, geo, broker, affiliate, language, funnel, schedule, total, status
            });
            
            searchBtn.disabled = true;
            searchBtn.textContent = '🔍 Поиск...';
            
            // Показываем loading
            messageList.innerHTML = '<div class="loading">🔍 Поиск сообщений...</div>';
            
            try {
                const params = new URLSearchParams();
                if (search) params.append('search', search);
                if (chatId) params.append('chat_id', chatId);
                if (geo) params.append('geo', geo);
                if (broker) params.append('broker', broker);
                if (affiliate) params.append('affiliate', affiliate);
                if (language) params.append('language', language);
                if (funnel) params.append('funnel', funnel);
                if (schedule) params.append('schedule', schedule);
                if (total) params.append('total', total);
                if (status) params.append('status', status);
                
                const response = await fetch(`/api/cap-analysis?${params}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success) {
                        currentMessages = data.messages;
                        renderResults(data.messages);
                        updateStats(data.messages);
                        statsSection.style.display = 'grid';
                        const exportBtn = document.getElementById('export-btn');
                        if (exportBtn) {
                            exportBtn.style.display = 'block';
                        }
                    } else {
                        showError(data.message || 'Ошибка поиска');
                    }
                } else {
                    showError('Ошибка сервера');
                }
                
            } catch (error) {
                console.error('Ошибка поиска:', error);
                showError('Ошибка соединения');
            } finally {
                // Убеждаемся, что кнопка сбрасывается корректно
                if (searchBtn) {
                    searchBtn.disabled = false;
                    searchBtn.textContent = '🔍 Найти';
                }
                
                console.log('Поиск завершен');
            }
        }
        
        // Отрисовка результатов
        function renderResults(messages) {
            const messageList = document.getElementById('message-list');
            
            if (!messageList) {
                console.error('Элемент message-list не найден для отрисовки результатов');
                return;
            }
            
            if (messages.length === 0) {
                messageList.innerHTML = '<div class="no-results">📭 Сообщения не найдены</div>';
                return;
            }
            
            // Группируем сообщения по message_id
            const groupedMessages = {};
            messages.forEach(msg => {
                const messageId = msg.id.split('_')[0];
                if (!groupedMessages[messageId]) {
                    groupedMessages[messageId] = {
                        message: msg,
                        caps: []
                    };
                }
                groupedMessages[messageId].caps.push(msg.analysis);
            });
            
                        messageList.innerHTML = Object.values(groupedMessages).map(group => {
                const msg = group.message;
                const caps = group.caps;
                const highlightedText = highlightCapWords(msg.message);
                
                return `
                    <div class="message-item">
                        <div class="message-header">
                            <div class="message-info">
                                <span class="chat-name">${msg.chat_name}</span>
                                <span class="message-author">👤 ${msg.user || 'Unknown'}</span>
                                <span class="message-date">${msg.timestamp}</span>
                                ${caps.length > 0 && caps[0].status ? `
                                    <span style="display: inline-block; padding: 0.25rem 0.5rem; border-radius: 0.25rem; font-weight: 500; font-size: 0.75rem;
                                        background: ${caps[0].status === 'RUN' ? 'rgba(16, 185, 129, 0.3)' : caps[0].status === 'STOP' ? 'rgba(251, 146, 60, 0.3)' : 'rgba(239, 68, 68, 0.3)'}; 
                                        color: ${caps[0].status === 'RUN' ? '#10b981' : caps[0].status === 'STOP' ? '#f59e0b' : '#ef4444'};">
                                        ${caps[0].status === 'RUN' ? '✅ АКТИВНАЯ' : caps[0].status === 'STOP' ? '⏸️ ОСТАНОВЛЕНА' : '🗑️ УДАЛЕНА'}
                                    </span>
                                ` : ''}
                            </div>
                        </div>
                        
                        <div class="message-text" style="word-wrap: break-word; line-height: 1.5;">${highlightedText}</div>
                        
                        ${caps.map((cap, capIndex) => `
                        <div class="analysis-section" style="margin-top: ${capIndex > 0 ? '1rem' : '0'}; ${capIndex > 0 ? 'border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1rem;' : ''}">
                            <div class="analysis-item ${cap.cap_amounts && cap.cap_amounts.length > 0 ? 'positive' : ''}">
                                <div class="label">Капа ${caps.length > 1 ? `#${capIndex + 1}` : ''}</div>
                                <div class="value">${cap.cap_amounts && cap.cap_amounts.length > 0 ? cap.cap_amounts.map(capAmt => `<span style="display: inline-block; margin: 0 0.25rem; padding: 0.125rem 0.5rem; background: rgba(16, 185, 129, 0.3); border-radius: 0.25rem;">${capAmt}</span>`).join('') : '—'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.total_amount === -1 || cap.total_amount > 0 ? 'positive' : 'negative'}">
                                <div class="label">Общий лимит</div>
                                <div class="value">${cap.total_amount === -1 ? '∞' : (cap.total_amount > 0 ? cap.total_amount : '—')}</div>
                            </div>

                            <div class="analysis-item ${cap.affiliate_name ? 'positive' : 'critical'}">
                                <div class="label">Аффилейт</div>
                                <div class="value">${cap.affiliate_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.recipient_name ? 'positive' : 'critical'}">
                                <div class="label">Получатель</div>
                                <div class="value">${cap.recipient_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.geos && cap.geos.length > 0 ? 'positive' : 'critical'}">
                                <div class="label">Гео</div>
                                <div class="value">${cap.geos && cap.geos.length > 0 ? cap.geos.join(', ') : '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.schedule && cap.schedule.trim() ? 'positive' : 'positive'}">
                                <div class="label">Расписание</div>
                                <div class="value">${(cap.schedule && cap.schedule.trim()) ? cap.schedule : '24/7'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.date && cap.date.trim() ? 'positive' : 'positive'}">
                                <div class="label">Дата</div>
                                <div class="value">${(cap.date && cap.date.trim()) ? cap.date : '∞'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.language && cap.language.trim() ? 'positive' : 'neutral'}">
                                <div class="label">Язык</div>
                                <div class="value">${(cap.language && cap.language.trim()) ? cap.language : '—'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.funnel && cap.funnel.trim() ? 'positive' : 'neutral'}">
                                <div class="label">Воронка</div>
                                <div class="value">${(cap.funnel && cap.funnel.trim()) ? cap.funnel : '—'}</div>
                            </div>
                        </div>
                        `).join('')}
                        
                        <!-- Кнопка для показа истории капы -->
                        ${caps.length > 0 && caps[0] ? `
                            <button class="cap-history-toggle" onclick="toggleCapHistory('${msg.id.split('_')[1]}', this)">
                                📜 Показать историю изменений
                            </button>
                            <div class="cap-history-content" id="history-${msg.id.split('_')[1]}">
                                <!-- История будет загружена сюда -->
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
        }
        
        // Подсветка слов cap
        function highlightCapWords(text) {
            const capWords = ['cap', 'сар', 'сар', 'кап', 'CAP', 'САР', 'САР', 'КАП'];
            // Экранируем HTML символы и заменяем переносы строк на <br>
            let highlightedText = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;')
                .replace(/\n/g, '<br>') // Заменяем переносы строк на <br>
                .replace(/\r/g, ''); // Убираем возврат каретки
            
            capWords.forEach(word => {
                const regex = new RegExp(`\\b${word}\\b`, 'gi');
                highlightedText = highlightedText.replace(regex, `<span class="highlight">${word}</span>`);
            });
            
            return highlightedText;
        }
        
        // Обновление статистики
        function updateStats(messages) {
            let totalCapAmount = 0;
            const uniqueGeos = new Set();
            const uniqueAffiliates = new Set();
            const uniqueBrokers = new Set();
            
            // Обрабатываем каждую капу отдельно (messages содержит отдельные записи для каждой капы)
            messages.forEach(msg => {
                const analysis = msg.analysis;
                
                // Суммируем капы
                if (analysis.cap_amounts && analysis.cap_amounts.length > 0) {
                    analysis.cap_amounts.forEach(capAmount => {
                        if (capAmount && !isNaN(capAmount)) {
                            totalCapAmount += parseInt(capAmount);
                        }
                    });
                }
                
                // Собираем уникальные гео
                if (analysis.geos && analysis.geos.length > 0) {
                    analysis.geos.forEach(geo => {
                        if (geo) uniqueGeos.add(geo);
                    });
                }
                
                // Собираем уникальных аффилейтов
                if (analysis.affiliate_name) {
                    uniqueAffiliates.add(analysis.affiliate_name);
                }
                
                // Собираем уникальных брокеров
                if (analysis.recipient_name) {
                    uniqueBrokers.add(analysis.recipient_name);
                }
            });
            
            // Обновляем элементы
            const totalCapEl = document.getElementById('total-cap-amount');
            const geosEl = document.getElementById('unique-geos');
            const affiliatesEl = document.getElementById('unique-affiliates');
            const brokersEl = document.getElementById('unique-brokers');
            
            if (totalCapEl) totalCapEl.textContent = totalCapAmount;
            if (geosEl) geosEl.textContent = uniqueGeos.size;
            if (affiliatesEl) affiliatesEl.textContent = uniqueAffiliates.size;
            if (brokersEl) brokersEl.textContent = uniqueBrokers.size;
        }
        
        // Показать ошибку
        function showError(message) {
            const messageList = document.getElementById('message-list');
            if (messageList) {
                messageList.innerHTML = `<div class="no-results">❌ ${message}</div>`;
            } else {
                console.error('Не найден элемент message-list для показа ошибки:', message);
            }
        }
        
        // Экспорт в CSV
        function exportToCSV() {
            if (currentMessages.length === 0) {
                alert('Нет данных для экспорта');
                return;
            }
            
            // Группируем сообщения по message_id для экспорта
            const groupedMessages = {};
            currentMessages.forEach(msg => {
                const messageId = msg.id.split('_')[0];
                if (!groupedMessages[messageId]) {
                    groupedMessages[messageId] = {
                        message: msg,
                        caps: []
                    };
                }
                groupedMessages[messageId].caps.push(msg.analysis);
            });
            
            const headers = [
                'Дата',
                'Чат',
                'Автор',
                'Сообщение',
                'Капа (все)',
                'Общий лимит',
                'Расписание',
                'Время начала',
                'Время окончания',
                'Часовой пояс',
                'Дата работы',
                'Аффилейт',
                'Получатель',
                'Гео',
                'Язык',
                'Воронка',
                'Pending ACQ',
                'Freeze Status'
            ];
            
            const csvContent = [
                headers.join(','),
                ...Object.values(groupedMessages).map(group => {
                    const msg = group.message;
                    const caps = group.caps;
                    const firstCap = caps[0];
                    
                    // Собираем все уникальные значения
                    const allCaps = caps.flatMap(cap => cap.cap_amounts || []);
                    const allAffiliates = [...new Set(caps.map(cap => cap.affiliate_name).filter(Boolean))];
                    const allRecipients = [...new Set(caps.map(cap => cap.recipient_name).filter(Boolean))];
                    const allGeos = [...new Set(caps.flatMap(cap => cap.geos || []))];
                    const allSchedules = [...new Set(caps.map(cap => cap.schedule).filter(Boolean))];
                    const allStartTimes = [...new Set(caps.map(cap => cap.start_time).filter(Boolean))];
                    const allEndTimes = [...new Set(caps.map(cap => cap.end_time).filter(Boolean))];
                    const allTimezones = [...new Set(caps.map(cap => cap.timezone).filter(Boolean))];
                    const allDates = [...new Set(caps.map(cap => cap.date).filter(Boolean))];
                    const allTotalAmounts = [...new Set(caps.map(cap => cap.total_amount).filter(t => t !== null && t !== undefined))];
                    const allLanguages = [...new Set(caps.map(cap => cap.language).filter(Boolean))];
                    const allFunnels = [...new Set(caps.map(cap => cap.funnel).filter(Boolean))];
                    const allPendingACQ = [...new Set(caps.map(cap => cap.pending_acq))];
                    const allFreezeStatus = [...new Set(caps.map(cap => cap.freeze_status_on_acq))];
                    
                    return [
                        `"${msg.timestamp}"`,
                        `"${msg.chat_name}"`,
                        `"${msg.user || 'Unknown'}"`,
                        `"${msg.message.replace(/"/g, '""')}"`,
                        `"${allCaps.join(', ')}"`,
                        `"${allTotalAmounts.map(total => total === -1 ? '∞' : total).join(', ')}"`,
                        `"${allSchedules.length > 0 && allSchedules.some(s => s && s.trim()) ? allSchedules.join(', ') : '24/7'}"`,
                        `"${allStartTimes.join(', ') || ''}"`,
                        `"${allEndTimes.join(', ') || ''}"`,
                        `"${allTimezones.join(', ') || ''}"`,
                        `"${allDates.length > 0 && allDates.some(d => d && d.trim()) ? allDates.join(', ') : '∞'}"`,
                        `"${allAffiliates.join(', ')}"`,
                        `"${allRecipients.join(', ')}"`,
                        `"${allGeos.join(', ')}"`,
                        `"${allLanguages.length > 0 && allLanguages.some(l => l && l.trim()) ? allLanguages.join(', ') : ''}"`,
                        `"${allFunnels.length > 0 && allFunnels.some(f => f && f.trim()) ? allFunnels.join(', ') : ''}"`,
                        `"${allPendingACQ.includes(true) ? 'Yes' : 'No'}"`,
                        `"${allFreezeStatus.includes(true) ? 'Yes' : 'No'}"`
                    ].join(',');
                })
            ].join('\n');
            
            const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `cap_analysis_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }
        
        // Инициализация после загрузки DOM
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM loaded, initializing cap analysis...');
            
            // Проверяем наличие всех необходимых элементов
            const searchForm = document.getElementById('search-form');
            const exportBtn = document.getElementById('export-btn');
            const searchBtn = document.getElementById('search-btn');
            const loading = document.getElementById('loading');
            const messageList = document.getElementById('message-list');
            const statsSection = document.getElementById('stats-section');
            
            console.log('Elements found:', {
                searchForm: !!searchForm,
                exportBtn: !!exportBtn,
                searchBtn: !!searchBtn,
                loading: !!loading,
                messageList: !!messageList,
                statsSection: !!statsSection
            });
            
            // Устанавливаем обработчики событий только если элементы найдены
            if (searchForm) {
                searchForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    searchMessages();
                });
            } else {
                console.error('Элемент search-form не найден');
            }
            
            if (exportBtn) {
                exportBtn.addEventListener('click', exportToCSV);
            } else {
                console.error('Элемент export-btn не найден');
            }
            
            // Обработчик кнопки очистки фильтров
            const clearFiltersBtn = document.getElementById('clear-filters-btn');
            if (clearFiltersBtn) {
                clearFiltersBtn.addEventListener('click', clearFilters);
            } else {
                console.error('Элемент clear-filters-btn не найден');
            }

            // Загружаем чаты и фильтры
            loadChats();
            loadFilterOptions();
        });
        
        // Функции для работы с историей капы
        async function toggleCapHistory(capId, button) {
            const historyContainer = document.getElementById(`history-${capId}`);
            
            if (!historyContainer) {
                console.error(`История контейнер не найден для капы ${capId}`);
                return;
            }
            
            // Если история уже показана, скрываем её
            if (historyContainer.classList.contains('show')) {
                historyContainer.classList.remove('show');
                button.textContent = '📜 Показать историю изменений';
                return;
            }
            
            // Показываем историю
            historyContainer.classList.add('show');
            button.textContent = '📜 Скрыть историю изменений';
            button.disabled = true;
            
            // Если история еще не загружена, загружаем её
            if (!historyContainer.dataset.loaded) {
                await loadCapHistory(capId, historyContainer);
                historyContainer.dataset.loaded = 'true';
            }
            
            button.disabled = false;
        }
        
        async function loadCapHistory(capId, container) {
            container.innerHTML = '<div class="history-loading">⏳ Загрузка истории...</div>';
            
            try {
                const response = await fetch(`/api/cap-history/${capId}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.success) {
                        renderCapHistory(data.history, container);
                    } else {
                        container.innerHTML = `<div class="history-empty">❌ Ошибка: ${data.message}</div>`;
                    }
                } else {
                    container.innerHTML = '<div class="history-empty">❌ Ошибка загрузки истории</div>';
                }
                
            } catch (error) {
                console.error('Ошибка загрузки истории капы:', error);
                container.innerHTML = '<div class="history-empty">❌ Ошибка соединения</div>';
            }
        }
        
        function renderCapHistory(history, container) {
            if (!history || history.length === 0) {
                container.innerHTML = '<div class="history-empty">📭 История изменений пуста</div>';
                return;
            }
            
            const historyHtml = history.map(item => {
                const analysis = item.analysis;
                
                return `
                    <div class="history-item">
                        <div class="history-item-header">
                            <div class="history-item-meta">
                                <span>👤 ${item.user}</span>
                                <span>💬 ${item.chat_name}</span>
                                <span>📅 ${item.timestamp}</span>
                                <span>🗂️ Архивировано: ${item.archived_at}</span>
                                <span style="padding: 0.125rem 0.25rem; border-radius: 0.25rem; font-weight: 500; font-size: 0.625rem;
                                    background: ${analysis.status === 'RUN' ? 'rgba(16, 185, 129, 0.3)' : analysis.status === 'STOP' ? 'rgba(251, 146, 60, 0.3)' : 'rgba(239, 68, 68, 0.3)'}; 
                                    color: ${analysis.status === 'RUN' ? '#10b981' : analysis.status === 'STOP' ? '#f59e0b' : '#ef4444'};">
                                    ${analysis.status === 'RUN' ? '✅ АКТИВНАЯ' : analysis.status === 'STOP' ? '⏸️ ОСТАНОВЛЕНА' : '🗑️ УДАЛЕНА'}
                                </span>
                            </div>
                        </div>
                        
                        <div class="history-item-text">${highlightCapWords(item.message)}</div>
                        
                        <div class="history-item-analysis">
                            <div class="history-analysis-item">
                                <div class="label">Капа</div>
                                <div class="value">${analysis.cap_amounts && analysis.cap_amounts.length > 0 ? analysis.cap_amounts.join(', ') : '—'}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Лимит</div>
                                <div class="value">${analysis.total_amount === -1 ? '∞' : (analysis.total_amount || '—')}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Аффилейт</div>
                                <div class="value">${analysis.affiliate_name || '—'}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Получатель</div>
                                <div class="value">${analysis.recipient_name || '—'}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Гео</div>
                                <div class="value">${analysis.geos && analysis.geos.length > 0 ? analysis.geos.join(', ') : '—'}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Расписание</div>
                                <div class="value">${analysis.schedule || '24/7'}</div>
                            </div>
                            
                            <div class="history-analysis-item">
                                <div class="label">Дата</div>
                                <div class="value">${analysis.date || '∞'}</div>
                            </div>
                            
                            ${analysis.language ? `
                                <div class="history-analysis-item">
                                    <div class="label">Язык</div>
                                    <div class="value">${analysis.language}</div>
                                </div>
                            ` : ''}
                            
                            ${analysis.funnel ? `
                                <div class="history-analysis-item">
                                    <div class="label">Воронка</div>
                                    <div class="value">${analysis.funnel}</div>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
            
            container.innerHTML = historyHtml;
        }
    </script>
</body>
</html> 