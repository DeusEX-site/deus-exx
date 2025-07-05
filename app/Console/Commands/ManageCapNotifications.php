<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CapNotificationService;
use App\Models\Chat;
use Illuminate\Support\Facades\Http;

class ManageCapNotifications extends Command
{
    protected $signature = 'cap-notifications:manage 
                           {action : enable/disable/status/test/get-chat-id}
                           {--chat-id= : ID чата для тестирования}';
    
    protected $description = 'Управление настройками уведомлений о капах';

    public function handle()
    {
        $action = $this->argument('action');
        
        switch ($action) {
            case 'enable':
                return $this->enableNotifications();
            case 'disable':
                return $this->disableNotifications();
            case 'status':
                return $this->showStatus();
            case 'test':
                return $this->testNotifications();
            case 'get-chat-id':
                return $this->getChatId();
            default:
                $this->error('Неизвестное действие. Используйте: enable, disable, status, test, get-chat-id');
                return Command::FAILURE;
        }
    }
    
    private function enableNotifications()
    {
        $this->info('🔔 Включение уведомлений о капах...');
        
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->error('❌ Файл .env не найден');
            return Command::FAILURE;
        }
        
        $envContent = file_get_contents($envPath);
        
        // Обновляем или добавляем настройку
        if (preg_match('/TELEGRAM_CAP_NOTIFICATIONS_ENABLED=(.*)/', $envContent)) {
            $envContent = preg_replace('/TELEGRAM_CAP_NOTIFICATIONS_ENABLED=(.*)/', 'TELEGRAM_CAP_NOTIFICATIONS_ENABLED=true', $envContent);
        } else {
            $envContent .= "\nTELEGRAM_CAP_NOTIFICATIONS_ENABLED=true";
        }
        
        file_put_contents($envPath, $envContent);
        
        $this->info('✅ Уведомления включены');
        $this->info('💡 Для применения изменений перезапустите приложение');
        
        return Command::SUCCESS;
    }
    
    private function disableNotifications()
    {
        $this->info('🔕 Отключение уведомлений о капах...');
        
        $envPath = base_path('.env');
        if (!file_exists($envPath)) {
            $this->error('❌ Файл .env не найден');
            return Command::FAILURE;
        }
        
        $envContent = file_get_contents($envPath);
        
        // Обновляем или добавляем настройку
        if (preg_match('/TELEGRAM_CAP_NOTIFICATIONS_ENABLED=(.*)/', $envContent)) {
            $envContent = preg_replace('/TELEGRAM_CAP_NOTIFICATIONS_ENABLED=(.*)/', 'TELEGRAM_CAP_NOTIFICATIONS_ENABLED=false', $envContent);
        } else {
            $envContent .= "\nTELEGRAM_CAP_NOTIFICATIONS_ENABLED=false";
        }
        
        file_put_contents($envPath, $envContent);
        
        $this->info('✅ Уведомления отключены');
        $this->info('💡 Для применения изменений перезапустите приложение');
        
        return Command::SUCCESS;
    }
    
    private function showStatus()
    {
        $this->info('📊 Статус системы уведомлений о капах');
        $this->line('');
        
        $botToken = config('telegram.bot_token');
        $enabled = config('telegram.cap_notifications.enabled', true);
        $adminChatId = config('telegram.cap_notifications.admin_chat_id');
        $notifyCreate = config('telegram.cap_notifications.notify_on_create', true);
        $notifyUpdate = config('telegram.cap_notifications.notify_on_update', true);
        $notifyUnchanged = config('telegram.cap_notifications.notify_on_unchanged', false);
        $bulkThreshold = config('telegram.cap_notifications.bulk_threshold', 3);
        
        $this->info('🔧 Настройки:');
        $this->info("  - Bot Token: " . ($botToken ? '✅ Установлен' : '❌ НЕ УСТАНОВЛЕН'));
        $this->info("  - Уведомления: " . ($enabled ? '✅ Включены' : '❌ Отключены'));
        $this->info("  - Админский чат: " . ($adminChatId ? "✅ {$adminChatId}" : '❌ НЕ УСТАНОВЛЕН'));
        $this->info("  - Уведомления о создании: " . ($notifyCreate ? '✅ Включены' : '❌ Отключены'));
        $this->info("  - Уведомления об обновлении: " . ($notifyUpdate ? '✅ Включены' : '❌ Отключены'));
        $this->info("  - Уведомления о неизменных данных: " . ($notifyUnchanged ? '✅ Включены' : '❌ Отключены'));
        $this->info("  - Порог группировки: {$bulkThreshold}");
        
        $this->line('');
        
        // Проверяем доступность Telegram API
        if ($botToken) {
            $this->info('🔍 Проверка соединения с Telegram API...');
            
            try {
                $response = Http::timeout(10)->get("https://api.telegram.org/bot{$botToken}/getMe");
                $result = $response->json();
                
                if ($result['ok']) {
                    $botInfo = $result['result'];
                    $this->info("✅ Бот активен: @{$botInfo['username']} ({$botInfo['first_name']})");
                } else {
                    $this->error('❌ Ошибка API: ' . ($result['description'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $this->error('❌ Ошибка соединения: ' . $e->getMessage());
            }
        }
        
        $this->line('');
        
        // Показываем активные чаты
        $chats = Chat::active()->orderBy('title')->get();
        $this->info("📱 Активные чаты ({$chats->count()}):");
        
        foreach ($chats as $chat) {
            $this->info("  - {$chat->title} (ID: {$chat->chat_id})");
        }
        
        return Command::SUCCESS;
    }
    
    private function testNotifications()
    {
        $chatId = $this->option('chat-id');
        
        if (!$chatId) {
            $this->error('❌ Укажите ID чата для тестирования: --chat-id=CHAT_ID');
            return Command::FAILURE;
        }
        
        $this->info("🧪 Тестирование отправки уведомления в чат: {$chatId}");
        
        $botToken = config('telegram.bot_token');
        if (!$botToken) {
            $this->error('❌ Bot token не установлен');
            return Command::FAILURE;
        }
        
        $testMessage = "🔔 <b>ТЕСТ УВЕДОМЛЕНИЙ</b>\n\n" .
                      "Это тестовое уведомление системы кап.\n" .
                      "Если вы видите это сообщение, уведомления работают корректно.\n\n" .
                      "🕐 Время: " . now()->format('d.m.Y H:i:s');
        
        try {
            $response = Http::timeout(10)->post("https://api.telegram.org/bot{$botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $testMessage,
                'parse_mode' => 'HTML'
            ]);
            
            $result = $response->json();
            
            if ($result['ok']) {
                $this->info('✅ Тестовое уведомление отправлено успешно');
                $this->info("📨 Message ID: {$result['result']['message_id']}");
            } else {
                $this->error('❌ Ошибка отправки: ' . ($result['description'] ?? 'Unknown error'));
            }
            
        } catch (\Exception $e) {
            $this->error('❌ Ошибка соединения: ' . $e->getMessage());
        }
        
        return Command::SUCCESS;
    }
    
    private function getChatId()
    {
        $this->info('📱 Получение ID чата из базы данных...');
        $this->line('');
        
        $chats = Chat::active()->orderBy('title')->get();
        
        if ($chats->isEmpty()) {
            $this->warn('❌ Активные чаты не найдены');
            $this->line('');
            $this->info('💡 Для получения ID чата:');
            $this->info('  1. Добавьте бота в чат');
            $this->info('  2. Отправьте любое сообщение');
            $this->info('  3. ID чата появится в логах или используйте эту команду снова');
            return Command::SUCCESS;
        }
        
        $this->info("📋 Найдено чатов: {$chats->count()}");
        $this->line('');
        
        $this->table(
            ['Название', 'ID чата', 'Тип', 'Сообщений'],
            $chats->map(function ($chat) {
                return [
                    $chat->title ?: 'Без названия',
                    $chat->chat_id,
                    $chat->type,
                    $chat->message_count
                ];
            })->toArray()
        );
        
        $this->line('');
        $this->info('💡 Для тестирования уведомлений используйте:');
        $this->info('  php artisan cap-notifications:manage test --chat-id=CHAT_ID');
        
        return Command::SUCCESS;
    }
} 