<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Services\CapAnalysisService;
use App\Services\CapNotificationService;

class TestCapNotifications extends Command
{
    protected $signature = 'test:cap-notifications {--disable-notifications : Отключить уведомления для теста}';
    protected $description = 'Тестирует систему уведомлений о капах в Telegram';

    public function handle()
    {
        $this->info('🔔 Тестирование системы уведомлений о капах...');
        
        // Проверяем настройки
        $botToken = config('telegram.bot_token');
        $notificationsEnabled = config('telegram.cap_notifications.enabled', true);
        $adminChatId = config('telegram.cap_notifications.admin_chat_id');
        
        if ($this->option('disable-notifications')) {
            $this->info('⚠️ Уведомления отключены для теста');
            $notificationsEnabled = false;
        }
        
        $this->info('📋 Текущие настройки:');
        $this->info("  - Bot Token: " . ($botToken ? 'Установлен' : '❌ НЕ УСТАНОВЛЕН'));
        $this->info("  - Уведомления: " . ($notificationsEnabled ? '✅ Включены' : '❌ Отключены'));
        $this->info("  - Админский чат: " . ($adminChatId ? $adminChatId : '❌ НЕ УСТАНОВЛЕН'));
        $this->line('');
        
        if (!$botToken) {
            $this->error('❌ Bot token не установлен. Установите TELEGRAM_BOT_TOKEN в .env файле');
            return Command::FAILURE;
        }
        
        // Создаем тестовый чат
        $chat = Chat::firstOrCreate([
            'chat_id' => -777777,
            'type' => 'supergroup',
            'title' => 'Тестовый чат для уведомлений'
        ]);
        
        $this->info("📱 Используем тестовый чат: {$chat->title} (ID: {$chat->chat_id})");
        $this->line('');
        
        // Тест 1: Создание новой капы
        $this->info('🧪 Тест 1: Создание новой капы...');
        
        $message1 = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 25 TestNotif - TestBroker : RU/KZ' . PHP_EOL . '09-18' . PHP_EOL . '25.12',
            'user' => 'TestNotificationUser',
            'telegram_message_id' => 3001,
            'telegram_user_id' => 3001,
            'created_at' => now()
        ]);
        
                 $capAnalysisService = new CapAnalysisService();
         
         if (!$notificationsEnabled) {
             $capAnalysisService->notificationService->setEnabled(false);
         }
        
        $result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $message1->message);
        
        $this->info("✅ Результат создания:");
        $this->info("  - Создано кап: {$result1['created_caps']}");
        $this->info("  - Уведомления: " . ($result1['notifications_sent'] ? 'Отправлены' : 'Отключены'));
        $this->line('');
        
        // Получаем созданную капу
        $cap1 = Cap::where('message_id', $message1->id)->first();
        
        if (!$cap1) {
            $this->error('❌ Не удалось найти созданную капу');
            return Command::FAILURE;
        }
        
        // Тест 2: Обновление одной капы
        $this->info('🧪 Тест 2: Обновление одной капы...');
        
        $message2 = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 35 TestNotif - TestBroker : RU/KZ' . PHP_EOL . '24/7' . PHP_EOL . '31.12',
            'user' => 'TestUpdateUser',
            'telegram_message_id' => 3002,
            'telegram_user_id' => 3002,
            'created_at' => now()->addMinutes(1)
        ]);
        
        $result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $message2->message);
        
        $this->info("✅ Результат обновления:");
        $this->info("  - Создано кап: {$result2['created_caps']}");
        $this->info("  - Обновлено кап: {$result2['updated_caps']}");
        $this->info("  - Без изменений: " . ($result2['unchanged_caps'] ?? 0));
        $this->info("  - Уведомления: " . ($result2['notifications_sent'] ? 'Отправлены' : 'Отключены'));
        $this->line('');
        
        // Тест 3: Массовое обновление (создаем несколько кап с одинаковыми данными)
        $this->info('🧪 Тест 3: Подготовка к массовому обновлению...');
        
        // Создаем дополнительные капы для массового обновления
        $additionalMessages = [];
        for ($i = 1; $i <= 3; $i++) {
            $msg = Message::create([
                'chat_id' => $chat->id,
                'message' => "CAP 20 BulkTest{$i} - BulkBroker : US/CA" . PHP_EOL . '10-20',
                'user' => "BulkUser{$i}",
                'telegram_message_id' => 3010 + $i,
                'telegram_user_id' => 3010 + $i,
                'created_at' => now()->addMinutes(2 + $i)
            ]);
            
            $capAnalysisService->analyzeAndSaveCapMessage($msg->id, $msg->message);
            $additionalMessages[] = $msg;
        }
        
        $this->info("✅ Создано 3 дополнительные капы для тестирования");
        
        // Теперь создаем сообщение которое должно обновить все эти капы
        $this->info('🧪 Тест 4: Массовое обновление (3+ кап)...');
        
        $bulkUpdateMessage = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 40 BulkTest1 - BulkBroker : US/CA' . PHP_EOL . 
                        'CAP 40 BulkTest2 - BulkBroker : US/CA' . PHP_EOL . 
                        'CAP 40 BulkTest3 - BulkBroker : US/CA' . PHP_EOL . 
                        '24/7' . PHP_EOL . '01.01',
            'user' => 'BulkUpdateUser',
            'telegram_message_id' => 3020,
            'telegram_user_id' => 3020,
            'created_at' => now()->addMinutes(10)
        ]);
        
        $bulkResult = $capAnalysisService->analyzeAndSaveCapMessage($bulkUpdateMessage->id, $bulkUpdateMessage->message);
        
        $this->info("✅ Результат массового обновления:");
        $this->info("  - Создано кап: {$bulkResult['created_caps']}");
        $this->info("  - Обновлено кап: {$bulkResult['updated_caps']}");
        $this->info("  - Без изменений: " . ($bulkResult['unchanged_caps'] ?? 0));
        $this->info("  - Уведомления: " . ($bulkResult['notifications_sent'] ? 'Отправлены (групповое)' : 'Отключены'));
        $this->line('');
        
        // Тест 5: Отправка идентичных данных (без изменений)
        $this->info('🧪 Тест 5: Отправка идентичных данных (без изменений)...');
        
        $identicalMessage = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 35 TestNotif - TestBroker : RU/KZ' . PHP_EOL . '24/7' . PHP_EOL . '31.12',
            'user' => 'TestUnchangedUser',
            'telegram_message_id' => 3030,
            'telegram_user_id' => 3030,
            'created_at' => now()->addMinutes(15)
        ]);
        
        $unchangedResult = $capAnalysisService->analyzeAndSaveCapMessage($identicalMessage->id, $identicalMessage->message);
        
        $this->info("✅ Результат идентичных данных:");
        $this->info("  - Создано кап: {$unchangedResult['created_caps']}");
        $this->info("  - Обновлено кап: {$unchangedResult['updated_caps']}");
        $this->info("  - Без изменений: " . ($unchangedResult['unchanged_caps'] ?? 0));
        $this->info("  - Уведомления: " . ($unchangedResult['notifications_sent'] ? 'Отправлены' : 'Отключены'));
        $this->line('');
        
        // Тест уведомлений напрямую
        if ($notificationsEnabled) {
            $this->info('🧪 Тест 6: Прямая отправка уведомления...');
            
            $notificationService = new CapNotificationService();
            $testResult = $notificationService->sendNewCapNotification($cap1, $message1);
            
            $this->info("✅ Результат прямой отправки: " . ($testResult ? 'Успешно' : 'Ошибка'));
        }
        
        // Статистика
        $this->line('');
        $this->info('📊 Итоговая статистика:');
        $totalCaps = Cap::count();
        $totalMessages = Message::where('chat_id', $chat->id)->count();
        
        $this->info("  - Всего кап в системе: {$totalCaps}");
        $this->info("  - Тестовых сообщений: {$totalMessages}");
        $this->info("  - Чат для уведомлений: {$chat->chat_id}");
        
        $this->line('');
        $this->info('🎉 Тестирование завершено!');
        $this->line('');
        
        if ($notificationsEnabled) {
            $this->info('💡 Проверьте Telegram чат на наличие уведомлений:');
            $this->info("   - Основной чат: {$chat->chat_id}");
            if ($adminChatId) {
                $this->info("   - Админский чат: {$adminChatId}");
            }
        } else {
            $this->info('💡 Уведомления были отключены для этого теста');
        }
        
        return Command::SUCCESS;
    }
} 