<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Настройки') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="settings-container">
                        
                        <!-- Telegram Bot Settings -->
                        <div class="telegram-settings">
                            <h3 class="text-lg font-semibold mb-4">🤖 Управление Telegram Ботом</h3>
                            <p class="text-gray-600 text-sm mb-6">
                                📡 Сообщения и новые чаты обновляются каждую секунду<br>
                                💬 Отправляйте сообщения через бота прямо из каждого чата
                            </p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
                                <button class="settings-btn success" onclick="setWebhook()">
                                    ⚡ Установить Webhook
                                </button>
                                <button class="settings-btn primary" onclick="getWebhookInfo()">
                                    ℹ️ Инфо Webhook
                                </button>
                                <button class="settings-btn warning" onclick="getBotInfo()">
                                    🤖 Инфо Бота
                                </button>
                                <button class="settings-btn danger" onclick="deleteWebhook()">
                                    🗑️ Удалить Webhook
                                </button>
                                <button class="settings-btn primary" onclick="refreshChats()">
                                    🔄 Обновить Чаты
                                </button>
                            </div>
                            
                            <!-- Bot Status -->
                            <div class="bot-status" id="bot-status">
                                <div class="status-indicator">
                                    <div class="status-dot unknown"></div>
                                    <span>Статус бота неизвестен</span>
                                </div>
                            </div>
                            
                            <!-- Webhook Info -->
                            <div class="webhook-info" id="webhook-info" style="display: none;">
                                <h4 class="font-semibold mb-2">📡 Информация о Webhook</h4>
                                <div class="info-block" id="webhook-details"></div>
                            </div>
                            
                            <!-- Bot Info -->
                            <div class="bot-info" id="bot-info-section" style="display: none;">
                                <h4 class="font-semibold mb-2">🤖 Информация о Боте</h4>
                                <div class="info-block" id="bot-details"></div>
                            </div>
                            
                            <!-- Status Messages -->
                            <div class="status-messages" id="status-messages"></div>
                        </div>
                        
                        <!-- Other Settings sections can be added here -->
                        
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .settings-container {
            max-width: 100%;
        }
        
        .telegram-settings {
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: #f8fafc;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
        }
        
        .settings-btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-align: center;
        }
        
        .settings-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }
        
        .settings-btn.primary {
            background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
            color: white;
        }
        
        .settings-btn.success {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
        }
        
        .settings-btn.warning {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: white;
        }
        
        .settings-btn.danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }
        
        .bot-status {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #6b7280;
            animation: pulse 2s infinite;
        }
        
        .status-dot.online {
            background: #10b981;
        }
        
        .status-dot.offline {
            background: #ef4444;
        }
        
        .status-dot.unknown {
            background: #6b7280;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .webhook-info, .bot-info {
            margin-top: 1.5rem;
            padding: 1rem;
            background: white;
            border-radius: 0.5rem;
            border: 1px solid #e5e7eb;
        }
        
        .info-block {
            background: #f9fafb;
            padding: 1rem;
            border-radius: 0.375rem;
            border: 1px solid #e5e7eb;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            white-space: pre-wrap;
        }
        
        .status-messages {
            margin-top: 1rem;
        }
        
        .status-message {
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            margin-bottom: 0.5rem;
            border: 1px solid;
        }
        
        .status-message.success {
            background: #dcfce7;
            color: #166534;
            border-color: #bbf7d0;
        }
        
        .status-message.error {
            background: #fef2f2;
            color: #991b1b;
            border-color: #fecaca;
        }
        
        .status-message.info {
            background: #dbeafe;
            color: #1e40af;
            border-color: #bfdbfe;
        }
    </style>

    <script>
        // Telegram Bot Management Functions
        async function setWebhook() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Установка...';
            button.disabled = true;
            
            try {
                const response = await fetch('/telegram/webhook/set', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        url: window.location.origin + '/api/telegram/webhook'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatusMessage('✅ Webhook установлен успешно!', 'success');
                    updateBotStatus('online', 'Webhook активен');
                } else {
                    showStatusMessage('❌ Ошибка: ' + data.message, 'error');
                }
            } catch (error) {
                showStatusMessage('❌ Ошибка соединения: ' + error.message, 'error');
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        async function getWebhookInfo() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Получение...';
            button.disabled = true;
            
            try {
                const response = await fetch('/telegram/webhook/info', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showWebhookInfo(data.webhook_info);
                    showStatusMessage('ℹ️ Информация о webhook получена', 'info');
                } else {
                    showStatusMessage('❌ Ошибка: ' + data.message, 'error');
                }
            } catch (error) {
                showStatusMessage('❌ Ошибка соединения: ' + error.message, 'error');
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        async function getBotInfo() {
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Получение...';
            button.disabled = true;
            
            try {
                const response = await fetch('/telegram/bot/info', {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showBotInfo(data.bot_info);
                    showStatusMessage('ℹ️ Информация о боте получена', 'info');
                    updateBotStatus('online', 'Бот активен');
                } else {
                    showStatusMessage('❌ Ошибка: ' + data.message, 'error');
                    updateBotStatus('offline', 'Бот недоступен');
                }
            } catch (error) {
                showStatusMessage('❌ Ошибка соединения: ' + error.message, 'error');
                updateBotStatus('unknown', 'Статус неизвестен');
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        async function deleteWebhook() {
            if (!confirm('Вы уверены, что хотите удалить webhook?')) {
                return;
            }
            
            const button = event.target;
            const originalText = button.innerHTML;
            button.innerHTML = '⏳ Удаление...';
            button.disabled = true;
            
            try {
                const response = await fetch('/telegram/webhook', {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showStatusMessage('✅ Webhook удален успешно!', 'success');
                    updateBotStatus('offline', 'Webhook отключен');
                    hideWebhookInfo();
                } else {
                    showStatusMessage('❌ Ошибка: ' + data.message, 'error');
                }
            } catch (error) {
                showStatusMessage('❌ Ошибка соединения: ' + error.message, 'error');
            } finally {
                button.innerHTML = originalText;
                button.disabled = false;
            }
        }
        
        function refreshChats() {
            showStatusMessage('🔄 Чаты обновлены. Перейдите на дашборд для просмотра.', 'info');
        }
        
        function showStatusMessage(message, type) {
            const container = document.getElementById('status-messages');
            const messageEl = document.createElement('div');
            messageEl.className = `status-message ${type}`;
            messageEl.textContent = message;
            
            container.appendChild(messageEl);
            
            // Удаляем сообщение через 5 секунд
            setTimeout(() => {
                if (messageEl.parentNode) {
                    messageEl.parentNode.removeChild(messageEl);
                }
            }, 5000);
        }
        
        function updateBotStatus(status, message) {
            const statusEl = document.getElementById('bot-status');
            const dot = statusEl.querySelector('.status-dot');
            const text = statusEl.querySelector('span');
            
            dot.className = `status-dot ${status}`;
            text.textContent = message;
        }
        
        function showWebhookInfo(webhookInfo) {
            const section = document.getElementById('webhook-info');
            const details = document.getElementById('webhook-details');
            
            details.textContent = JSON.stringify(webhookInfo, null, 2);
            section.style.display = 'block';
        }
        
        function hideWebhookInfo() {
            const section = document.getElementById('webhook-info');
            section.style.display = 'none';
        }
        
        function showBotInfo(botInfo) {
            const section = document.getElementById('bot-info-section');
            const details = document.getElementById('bot-details');
            
            details.textContent = JSON.stringify(botInfo, null, 2);
            section.style.display = 'block';
        }
        
        // Получаем информацию о боте при загрузке страницы
        document.addEventListener('DOMContentLoaded', function() {
            getBotInfo();
        });
    </script>
</x-app-layout> 