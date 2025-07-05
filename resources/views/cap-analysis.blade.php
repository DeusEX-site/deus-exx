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
            white-space: pre-wrap;
            word-wrap: break-word;
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
                <h3 id="total-messages">0</h3>
                <p>Всего сообщений</p>
            </div>
            <div class="stat-card">
                <h3 id="cap-messages">0</h3>
                <p>С капой</p>
            </div>
            <div class="stat-card">
                <h3 id="schedule-messages">0</h3>
                <p>С расписанием</p>
            </div>
            <div class="stat-card">
                <h3 id="geo-messages">0</h3>
                <p>С гео</p>
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
                'affiliate-filter', 'schedule-filter', 'total-filter'
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
            const scheduleFilter = document.getElementById('schedule-filter');
            const totalFilter = document.getElementById('total-filter');
            
            const search = searchInput.value;
            const chatId = chatSelect.value;
            const geo = geoFilter?.value || '';
            const broker = brokerFilter?.value || '';
            const affiliate = affiliateFilter?.value || '';
            const schedule = scheduleFilter?.value || '';
            const total = totalFilter?.value || '';

            console.log('Параметры поиска:', {
                search, chatId, geo, broker, affiliate, schedule, total
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
                if (schedule) params.append('schedule', schedule);
                if (total) params.append('total', total);
                
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
            
            messageList.innerHTML = messages.map(msg => {
                const analysis = msg.analysis;
                const highlightedText = highlightCapWords(msg.message);
                
                return `
                    <div class="message-item">
                        <div class="message-header">
                            <div class="message-info">
                                <span class="chat-name">${msg.chat_name}</span>
                                <span class="message-author">👤 ${msg.user || 'Unknown'}</span>
                                <span class="message-date">${msg.timestamp}</span>
                            </div>
                        </div>
                        
                        <div class="message-text">${highlightedText}</div>
                        
                        <div class="analysis-section">
                            <div class="analysis-item ${analysis.has_cap_word ? 'positive' : 'negative'}">
                                <div class="label">Слово CAP</div>
                                <div class="value">${analysis.has_cap_word ? '✅' : '❌'}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.cap_amounts && analysis.cap_amounts.length > 0 ? 'positive' : ''}">
                                <div class="label">Капа ${analysis.cap_amounts && analysis.cap_amounts.length > 1 ? '(отдельно)' : ''}</div>
                                <div class="value">${analysis.cap_amounts && analysis.cap_amounts.length > 0 ? analysis.cap_amounts.map(cap => `<span style="display: inline-block; margin: 0 0.25rem; padding: 0.125rem 0.5rem; background: rgba(16, 185, 129, 0.3); border-radius: 0.25rem;">${cap}</span>`).join('') : '—'}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.total_amount === -1 || analysis.total_amount > 0 ? 'positive' : 'negative'}">
                                <div class="label">Общий лимит</div>
                                <div class="value">${analysis.total_amount === -1 ? '∞' : (analysis.total_amount > 0 ? analysis.total_amount : '—')}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.schedule ? 'positive' : 'positive'}">
                                <div class="label">Расписание</div>
                                <div class="value">${analysis.schedule || '24/7'}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.date ? 'positive' : 'positive'}">
                                <div class="label">Дата</div>
                                <div class="value">${analysis.date || new Date().toLocaleDateString('ru-RU')}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.affiliate_name ? 'positive' : 'critical'}">
                                <div class="label">Аффилейт</div>
                                <div class="value">${analysis.affiliate_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.broker_name ? 'positive' : 'critical'}">
                                <div class="label">Брокер</div>
                                <div class="value">${analysis.broker_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${analysis.geos.length > 0 ? 'positive' : 'critical'}">
                                <div class="label">Гео</div>
                                <div class="value">${analysis.geos.length > 0 ? analysis.geos.join(', ') : '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            ${analysis.highlighted_text ? `
                            <div class="analysis-item positive" style="grid-column: 1 / -1;">
                                <div class="label">Обработанный текст для этого блока</div>
                                <div class="value" style="background: rgba(245, 158, 11, 0.2); padding: 0.5rem; border-radius: 0.25rem; border: 1px solid rgba(245, 158, 11, 0.3); font-family: monospace; text-align: left; white-space: pre-wrap;">${analysis.highlighted_text
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#39;')}</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }).join('');
        }
        
        // Подсветка слов cap
        function highlightCapWords(text) {
            const capWords = ['cap', 'сар', 'сар', 'кап', 'CAP', 'САР', 'САР', 'КАП'];
            // Экранируем HTML символы, но сохраняем переносы строк
            let highlightedText = text
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
            
            capWords.forEach(word => {
                const regex = new RegExp(`\\b${word}\\b`, 'gi');
                highlightedText = highlightedText.replace(regex, `<span class="highlight">${word}</span>`);
            });
            
            return highlightedText;
        }
        
        // Обновление статистики
        function updateStats(messages) {
            const totalMessages = messages.length;
            const capMessages = messages.filter(msg => msg.analysis.has_cap_word).length;
            const scheduleMessages = messages.filter(msg => msg.analysis.schedule).length;
            const geoMessages = messages.filter(msg => msg.analysis.geos.length > 0).length;
            
            const totalEl = document.getElementById('total-messages');
            const capEl = document.getElementById('cap-messages');
            const scheduleEl = document.getElementById('schedule-messages');
            const geoEl = document.getElementById('geo-messages');
            
            if (totalEl) totalEl.textContent = totalMessages;
            if (capEl) capEl.textContent = capMessages;
            if (scheduleEl) scheduleEl.textContent = scheduleMessages;
            if (geoEl) geoEl.textContent = geoMessages;
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
            
            const headers = [
                'Дата',
                'Чат',
                'Автор',
                'Сообщение',
                'Слово CAP',
                'Капа',
                'Общий лимит',
                'Расписание',
                'Дата работы',
                'Аффилейт',
                'Брокер',
                'Гео'
            ];
            
            const csvContent = [
                headers.join(','),
                ...currentMessages.map(msg => {
                    const analysis = msg.analysis;
                    return [
                        `"${msg.timestamp}"`,
                        `"${msg.chat_name}"`,
                        `"${msg.user || 'Unknown'}"`,
                        `"${msg.message.replace(/"/g, '""')}"`,
                        analysis.has_cap_word ? 'Да' : 'Нет',
                        `"${analysis.cap_amounts && analysis.cap_amounts.length > 0 ? analysis.cap_amounts.join(', ') : ''}"`,
                        analysis.total_amount || '',
                        `"${analysis.schedule || ''}"`,
                        `"${analysis.date || ''}"`,
                        `"${analysis.affiliate_name || ''}"`,
                        `"${analysis.broker_name || ''}"`,
                        `"${analysis.geos.join(', ')}"`
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
    </script>
</body>
</html> 