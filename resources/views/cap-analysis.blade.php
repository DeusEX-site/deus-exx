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
        
        /* Стили для истории кап */
        .history-item {
            background: rgba(20, 20, 40, 0.6);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .history-item.hidden {
            background: rgba(239, 68, 68, 0.1);
            border-color: rgba(239, 68, 68, 0.3);
        }
        
        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        
        .history-action {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .history-action.created {
            background: rgba(16, 185, 129, 0.2);
            color: #10b981;
        }
        
        .history-action.updated {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .history-action.replaced {
            background: rgba(245, 158, 11, 0.2);
            color: #f59e0b;
        }
        
        .history-meta {
            font-size: 0.75rem;
            color: rgba(255, 255, 255, 0.6);
        }
        
        .history-description {
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 0.5rem;
        }
        
        .history-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            font-size: 0.875rem;
        }
        
        .history-detail {
            color: rgba(255, 255, 255, 0.8);
        }
        
        .history-detail strong {
            color: #10b981;
        }
        
        .history-controls {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .history-btn {
            padding: 0.25rem 0.5rem;
            border: none;
            border-radius: 0.25rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .history-btn.toggle-visibility {
            background: rgba(139, 92, 246, 0.3);
            color: #a855f7;
        }
        
        .history-btn.toggle-visibility:hover {
            background: rgba(139, 92, 246, 0.5);
        }
        
        .history-btn.view-details {
            background: rgba(59, 130, 246, 0.3);
            color: #3b82f6;
        }
        
        .history-btn.view-details:hover {
            background: rgba(59, 130, 246, 0.5);
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

        <!-- Cap History Section -->
        <div class="results-section" style="margin-bottom: 2rem;">
            <div class="results-header">
                <h2>📈 Последние обновления кап</h2>
                <div>
                    <button class="export-btn" id="toggle-history-btn" style="background: rgba(139, 92, 246, 0.3); margin-right: 1rem;">
                        👁️ Показать скрытые
                    </button>
                    <button class="export-btn" id="refresh-history-btn">
                        🔄 Обновить
                    </button>
                </div>
            </div>
            
            <div class="message-list" id="history-list">
                <div class="loading" id="history-loading">
                    🔄 Загрузка истории обновлений...
                </div>
            </div>
        </div>

        <!-- Cap Statistics Section -->
        <div class="results-section" style="margin-bottom: 2rem;">
            <div class="results-header">
                <h2>📊 Статистика системы кап</h2>
                <button class="export-btn" id="refresh-stats-btn">
                    🔄 Обновить
                </button>
            </div>
            
            <div class="stats-section" id="cap-stats-section">
                <div class="stat-card">
                    <h3 id="total-caps">0</h3>
                    <p>Всего кап</p>
                </div>
                <div class="stat-card">
                    <h3 id="total-history">0</h3>
                    <p>Записей истории</p>
                </div>
                <div class="stat-card">
                    <h3 id="updates-today">0</h3>
                    <p>Обновлений сегодня</p>
                </div>
                <div class="stat-card">
                    <h3 id="new-caps-today">0</h3>
                    <p>Новых кап сегодня</p>
                </div>
                <div class="stat-card">
                    <h3 id="hidden-records">0</h3>
                    <p>Скрытых записей</p>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section">
            <div class="results-header">
                <h2>🔍 Результаты анализа</h2>
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
        let showHiddenHistory = false;
        
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

        // Загрузка истории обновлений кап
        async function loadCapHistory() {
            const historyList = document.getElementById('history-list');
            const historyLoading = document.getElementById('history-loading');
            
            if (historyLoading) {
                historyLoading.style.display = 'block';
                historyLoading.textContent = '🔄 Загрузка истории обновлений...';
            }
            
            try {
                const response = await fetch(`/api/cap-updates/recent?limit=10&include_hidden=${showHiddenHistory}`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    if (data.updates && data.updates.length > 0) {
                        let html = '';
                        
                        data.updates.forEach(update => {
                            const isHidden = update.is_hidden;
                            const actionClass = update.action_type;
                            const actionIcon = {
                                'created': '🆕',
                                'updated': '🔄',
                                'replaced': '🔁'
                            }[update.action_type] || '📝';
                            
                            html += `
                                <div class="history-item ${isHidden ? 'hidden' : ''}">
                                    <div class="history-header">
                                        <div class="history-action ${actionClass}">
                                            ${actionIcon} ${update.action_type}
                                        </div>
                                        <div class="history-meta">
                                            ${update.created_at} • ${update.chat_name}
                                        </div>
                                    </div>
                                    <div class="history-description">
                                        ${update.description}
                                    </div>
                                    <div class="history-details">
                                        <div class="history-detail">
                                            <strong>Аффилейт:</strong> ${update.affiliate_name || 'Не указан'}
                                        </div>
                                        <div class="history-detail">
                                            <strong>Брокер:</strong> ${update.broker_name || 'Не указан'}
                                        </div>
                                        <div class="history-detail">
                                            <strong>Гео:</strong> ${update.geos ? update.geos.join(', ') : 'Не указано'}
                                        </div>
                                        <div class="history-detail">
                                            <strong>Причина:</strong> ${update.reason || 'Не указана'}
                                        </div>
                                    </div>
                                    <div class="history-controls">
                                        <button class="history-btn toggle-visibility" onclick="toggleHistoryVisibility(${update.id})">
                                            ${isHidden ? '👁️ Показать' : '🙈 Скрыть'}
                                        </button>
                                        <button class="history-btn view-details" onclick="viewCapDetails(${update.cap_id})">
                                            📊 Подробнее
                                        </button>
                                    </div>
                                </div>
                            `;
                        });
                        
                        historyList.innerHTML = html;
                    } else {
                        historyList.innerHTML = '<div class="loading">📝 История обновлений пуста</div>';
                    }
                } else {
                    historyList.innerHTML = '<div class="loading">❌ Ошибка загрузки истории</div>';
                }
            } catch (error) {
                console.error('Ошибка загрузки истории:', error);
                historyList.innerHTML = '<div class="loading">❌ Ошибка загрузки истории</div>';
            }
            
            if (historyLoading) {
                historyLoading.style.display = 'none';
            }
        }
        
        // Загрузка статистики кап
        async function loadCapStatistics() {
            try {
                const response = await fetch('/api/cap-statistics', {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const stats = await response.json();
                    
                    document.getElementById('total-caps').textContent = stats.total_caps || 0;
                    document.getElementById('total-history').textContent = stats.total_history_records || 0;
                    document.getElementById('updates-today').textContent = stats.updates_today || 0;
                    document.getElementById('new-caps-today').textContent = stats.new_caps_today || 0;
                    document.getElementById('hidden-records').textContent = stats.hidden_records || 0;
                }
            } catch (error) {
                console.error('Ошибка загрузки статистики:', error);
            }
        }
        
        // Переключение видимости записи истории
        async function toggleHistoryVisibility(historyId) {
            try {
                const response = await fetch(`/api/cap-history/${historyId}/toggle-visibility`, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    if (data.success) {
                        // Перезагружаем историю
                        loadCapHistory();
                        // Обновляем статистику
                        loadCapStatistics();
                    }
                }
            } catch (error) {
                console.error('Ошибка переключения видимости:', error);
            }
        }
        
        // Просмотр деталей капы
        async function viewCapDetails(capId) {
            try {
                const response = await fetch(`/api/cap/${capId}/history?include_hidden=true`, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                if (response.ok) {
                    const data = await response.json();
                    
                    let detailsHtml = `
                        <div style="background: rgba(30, 30, 60, 0.9); padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                            <h3 style="color: #10b981; margin-bottom: 0.5rem;">📊 Капа #${capId}</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0.5rem; font-size: 0.875rem;">
                                <div><strong>Аффилейт:</strong> ${data.cap.affiliate_name || 'Не указан'}</div>
                                <div><strong>Брокер:</strong> ${data.cap.broker_name || 'Не указан'}</div>
                                <div><strong>Гео:</strong> ${data.cap.geos ? data.cap.geos.join(', ') : 'Не указано'}</div>
                                <div><strong>Капа:</strong> ${data.cap.cap_amounts ? data.cap.cap_amounts.join(', ') : 'Не указано'}</div>
                                <div><strong>Расписание:</strong> ${data.cap.schedule || '24/7'}</div>
                                <div><strong>Общий лимит:</strong> ${data.cap.total_amount === -1 ? '∞' : (data.cap.total_amount || 'Не указан')}</div>
                            </div>
                        </div>
                        <h4 style="color: #10b981; margin-bottom: 0.5rem;">📝 История изменений:</h4>
                    `;
                    
                    if (data.history && data.history.length > 0) {
                        data.history.forEach(item => {
                            detailsHtml += `
                                <div style="background: rgba(20, 20, 40, 0.6); padding: 0.5rem; border-radius: 0.25rem; margin-bottom: 0.5rem; border: 1px solid rgba(255, 255, 255, 0.1);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.25rem;">
                                        <span style="font-weight: 600; color: #3b82f6;">${item.action_type}</span>
                                        <span style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.6);">${item.created_at}</span>
                                    </div>
                                    <div style="font-size: 0.875rem; color: rgba(255, 255, 255, 0.9);">${item.description}</div>
                                    <div style="font-size: 0.75rem; color: rgba(255, 255, 255, 0.6); margin-top: 0.25rem;">${item.reason}</div>
                                </div>
                            `;
                        });
                    } else {
                        detailsHtml += '<div style="color: rgba(255, 255, 255, 0.6);">История изменений отсутствует</div>';
                    }
                    
                    // Создаем модальное окно
                    const modal = document.createElement('div');
                    modal.style.cssText = `
                        position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(0, 0, 0, 0.8); display: flex; align-items: center; 
                        justify-content: center; z-index: 1000;
                    `;
                    
                    const modalContent = document.createElement('div');
                    modalContent.style.cssText = `
                        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f0f23 100%);
                        max-width: 800px; width: 90%; max-height: 80%; overflow-y: auto;
                        border-radius: 1rem; padding: 2rem; color: #e2e8f0;
                        border: 1px solid rgba(255, 255, 255, 0.1);
                    `;
                    
                    modalContent.innerHTML = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                            <h2 style="color: white; margin: 0;">Детали капы</h2>
                            <button onclick="this.closest('div').remove()" style="background: rgba(239, 68, 68, 0.3); color: #ef4444; border: none; padding: 0.5rem 1rem; border-radius: 0.5rem; cursor: pointer;">✕ Закрыть</button>
                        </div>
                        ${detailsHtml}
                    `;
                    
                    modal.appendChild(modalContent);
                    document.body.appendChild(modal);
                    
                    // Закрытие по клику на фон
                    modal.addEventListener('click', (e) => {
                        if (e.target === modal) {
                            modal.remove();
                        }
                    });
                }
            } catch (error) {
                console.error('Ошибка загрузки деталей капы:', error);
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
                            </div>
                        </div>
                        
                        <div class="message-text" style="word-wrap: break-word; line-height: 1.5;">${highlightedText}</div>
                        
                        ${caps.map((cap, capIndex) => `
                        <div class="analysis-section" style="margin-top: ${capIndex > 0 ? '1rem' : '0'}; ${capIndex > 0 ? 'border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1rem;' : ''}">
                            <div class="analysis-item ${cap.has_cap_word ? 'positive' : 'negative'}">
                                <div class="label">Слово CAP</div>
                                <div class="value">${cap.has_cap_word ? '✅' : '❌'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.cap_amounts && cap.cap_amounts.length > 0 ? 'positive' : ''}">
                                <div class="label">Капа ${caps.length > 1 ? `#${capIndex + 1}` : ''}</div>
                                <div class="value">${cap.cap_amounts && cap.cap_amounts.length > 0 ? cap.cap_amounts.map(capAmt => `<span style="display: inline-block; margin: 0 0.25rem; padding: 0.125rem 0.5rem; background: rgba(16, 185, 129, 0.3); border-radius: 0.25rem;">${capAmt}</span>`).join('') : '—'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.total_amount === -1 || cap.total_amount > 0 ? 'positive' : 'negative'}">
                                <div class="label">Общий лимит</div>
                                <div class="value">${cap.total_amount === -1 ? '∞' : (cap.total_amount > 0 ? cap.total_amount : '—')}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.schedule ? 'positive' : 'positive'}">
                                <div class="label">Расписание</div>
                                <div class="value">${cap.schedule || '24/7'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.date ? 'positive' : 'positive'}">
                                <div class="label">Дата</div>
                                <div class="value">${cap.date || '∞'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.affiliate_name ? 'positive' : 'critical'}">
                                <div class="label">Аффилейт</div>
                                <div class="value">${cap.affiliate_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.broker_name ? 'positive' : 'critical'}">
                                <div class="label">Брокер</div>
                                <div class="value">${cap.broker_name || '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                            
                            <div class="analysis-item ${cap.geos && cap.geos.length > 0 ? 'positive' : 'critical'}">
                                <div class="label">Гео</div>
                                <div class="value">${cap.geos && cap.geos.length > 0 ? cap.geos.join(', ') : '<span style="color: #ef4444;">❌ ОБЯЗАТЕЛЬНО</span>'}</div>
                            </div>
                        </div>
                        `).join('')}
                            
                        ${caps.some(cap => cap.highlighted_text) ? `
                        <div class="analysis-section" style="margin-top: 1rem; border-top: 1px solid rgba(255, 255, 255, 0.1); padding-top: 1rem;">
                            <div class="analysis-item positive" style="grid-column: 1 / -1;">
                                <div class="label">Обработанный текст для всех блоков (${caps.length})</div>
                                <div class="value" style="background: rgba(245, 158, 11, 0.2); padding: 0.5rem; border-radius: 0.25rem; border: 1px solid rgba(245, 158, 11, 0.3); font-family: monospace; text-align: left;">${caps.map((cap, index) => {
                                    if (!cap.highlighted_text) return '';
                                    let result = `${index + 1}. ${cap.highlighted_text}`;
                                    
                                    // Добавляем дополнительную информацию
                                    const additionalInfo = [];
                                    if (cap.total_amount === -1) {
                                        additionalInfo.push('∞');
                                    } else if (cap.total_amount > 0) {
                                        additionalInfo.push(cap.total_amount);
                                    }
                                    if (cap.schedule) {
                                        additionalInfo.push(cap.schedule);
                                    }
                                    if (cap.date) {
                                        additionalInfo.push(cap.date);
                                    }
                                    
                                    if (additionalInfo.length > 0) {
                                        result += ' [' + additionalInfo.join(', ') + ']';
                                    }
                                    
                                    return result;
                                }).filter(Boolean).join('|||BREAK|||')
                                    .replace(/&/g, '&amp;')
                                    .replace(/</g, '&lt;')
                                    .replace(/>/g, '&gt;')
                                    .replace(/"/g, '&quot;')
                                    .replace(/'/g, '&#39;')
                                    .replace(/\n/g, '<br>')
                                    .replace(/\|\|\|BREAK\|\|\|/g, '<br>')}</div>
                            </div>
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
            // Группируем по уникальным сообщениям (по message_id)
            const uniqueMessages = {};
            messages.forEach(msg => {
                const messageId = msg.id.split('_')[0]; // Получаем message_id из "message_id_cap_id"
                if (!uniqueMessages[messageId]) {
                    uniqueMessages[messageId] = msg;
                }
            });
            
            const uniqueMessageArray = Object.values(uniqueMessages);
            const totalMessages = uniqueMessageArray.length;
            const capMessages = uniqueMessageArray.filter(msg => msg.analysis.has_cap_word).length;
            const scheduleMessages = uniqueMessageArray.filter(msg => msg.analysis.schedule).length;
            const geoMessages = uniqueMessageArray.filter(msg => msg.analysis.geos.length > 0).length;
            
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
                'Слово CAP',
                'Капа (все)',
                'Общий лимит',
                'Расписание',
                'Дата работы',
                'Аффилейт',
                'Брокер',
                'Гео'
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
                    const allBrokers = [...new Set(caps.map(cap => cap.broker_name).filter(Boolean))];
                    const allGeos = [...new Set(caps.flatMap(cap => cap.geos || []))];
                    const allSchedules = [...new Set(caps.map(cap => cap.schedule).filter(Boolean))];
                    const allDates = [...new Set(caps.map(cap => cap.date).filter(Boolean))];
                    const allTotalAmounts = [...new Set(caps.map(cap => cap.total_amount).filter(t => t !== null && t !== undefined))];
                    
                    return [
                        `"${msg.timestamp}"`,
                        `"${msg.chat_name}"`,
                        `"${msg.user || 'Unknown'}"`,
                        `"${msg.message.replace(/"/g, '""')}"`,
                        firstCap.has_cap_word ? 'Да' : 'Нет',
                        `"${allCaps.join(', ')}"`,
                        `"${allTotalAmounts.map(total => total === -1 ? '∞' : total).join(', ')}"`,
                        `"${allSchedules.join(', ') || '24/7'}"`,
                        `"${allDates.join(', ') || '∞'}"`,
                        `"${allAffiliates.join(', ')}"`,
                        `"${allBrokers.join(', ')}"`,
                        `"${allGeos.join(', ')}"`
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

            // Обработчики для истории кап
            const toggleHistoryBtn = document.getElementById('toggle-history-btn');
            if (toggleHistoryBtn) {
                toggleHistoryBtn.addEventListener('click', () => {
                    showHiddenHistory = !showHiddenHistory;
                    toggleHistoryBtn.textContent = showHiddenHistory ? '🙈 Скрыть скрытые' : '👁️ Показать скрытые';
                    loadCapHistory();
                });
            }

            const refreshHistoryBtn = document.getElementById('refresh-history-btn');
            if (refreshHistoryBtn) {
                refreshHistoryBtn.addEventListener('click', loadCapHistory);
            }

            const refreshStatsBtn = document.getElementById('refresh-stats-btn');
            if (refreshStatsBtn) {
                refreshStatsBtn.addEventListener('click', loadCapStatistics);
            }

            // Загружаем данные
            loadChats();
            loadFilterOptions();
            loadCapHistory();
            loadCapStatistics();
        });
    </script>
</body>
</html> 