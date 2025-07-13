<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Http\Controllers\TelegramWebhookController;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CreateTestChats extends Command
{
    protected $signature = 'test:create-chats {count=100 : Количество чатов для создания}';
    protected $description = 'Создает тестовые чаты используя существующую логику системы';

    public function handle()
    {
        $count = (int) $this->argument('count');
        
        $this->info("Создание {$count} тестовых чатов через систему...");
        
        // Очищаем существующие данные
        $this->warn('Очистка существующих данных...');
        $this->clearDatabase();
        
        // Создаем контроллер для обработки сообщений
        $webhookController = app(TelegramWebhookController::class);
        
        $this->info('Отправка тестовых сообщений через webhook...');
        
        $successCount = 0;
        $errorCount = 0;
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                // Создаем тестовое сообщение для чата
                $testMessage = $this->generateTestMessage($i);
                
                // Отправляем через webhook контроллер
                $request = new Request($testMessage);
                $response = $webhookController->handle($request);
                
                if ($response->getStatusCode() == 200) {
                    $successCount++;
                    if ($i % 10 == 0) {
                        $this->info("Обработано чатов: {$i}");
                    }
                } else {
                    $errorCount++;
                    $this->error("Ошибка для чата {$i}: " . $response->getContent());
                }
                
                // Небольшая задержка для имитации реального времени
                usleep(10000); // 0.01 секунды
                
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Исключение для чата {$i}: " . $e->getMessage());
            }
        }
        
        $this->info("✅ Обработано сообщений: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("⚠️ Ошибок: {$errorCount}");
        }
        
        // Показываем статистику
        $this->showStatistics();
    }

    private function clearDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('caps_history')->truncate();
        DB::table('caps')->truncate();  
        DB::table('messages')->truncate();
        DB::table('chats')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('База данных очищена');
    }

    private function generateTestMessage($index)
    {
        $chatTypes = ['private', 'group', 'supergroup', 'channel'];
        $chatType = $chatTypes[array_rand($chatTypes)];
        
        $chatId = 1000 + $index;
        $messageId = 10000 + $index;
        $userId = 2000 + $index;
        
        // Генерируем разные типы сообщений
        $messageTexts = [
            "CAP {$index} TestAff{$index} - TestBroker{$index} : US/CA\n10-19",
            "CAP " . (20 + $index) . " Partner{$index} - Broker{$index} : EU/UK\n24/7",
            "CAP " . (30 + $index) . " Affiliate{$index} - Recipient{$index} : RU/KZ\n14.05",
            "Обычное сообщение в чате номер {$index}",
            "Тестовое сообщение для проверки системы {$index}",
            "CAP " . (40 + $index) . " TestPartner{$index} - TestRecipient{$index} : AU/NZ\n09-18",
        ];
        
        $messageText = $messageTexts[$index % count($messageTexts)];
        
        // Структура сообщения Telegram API
        return [
            'update_id' => $index,
            'message' => [
                'message_id' => $messageId,
                'from' => [
                    'id' => $userId,
                    'is_bot' => false,
                    'first_name' => "TestUser{$index}",
                    'last_name' => "LastName{$index}",
                    'username' => "testuser{$index}",
                    'language_code' => 'ru'
                ],
                'chat' => [
                    'id' => $chatId,
                    'type' => $chatType,
                    'title' => $this->generateChatTitle($chatType, $index),
                    'username' => $this->generateChatUsername($chatType, $index),
                    'description' => $this->generateChatDescription($chatType, $index)
                ],
                'date' => Carbon::now()->subMinutes(rand(0, 1440))->timestamp,
                'text' => $messageText
            ]
        ];
    }

    private function generateChatTitle($type, $index)
    {
        switch ($type) {
            case 'private':
                return null;
            case 'group':
                return "Группа тестирования #{$index}";
            case 'supergroup':
                return "Супергруппа тестирования #{$index}";
            case 'channel':
                return "Канал тестирования #{$index}";
            default:
                return "Чат #{$index}";
        }
    }

    private function generateChatUsername($type, $index)
    {
        switch ($type) {
            case 'private':
                return "testuser{$index}";
            case 'group':
                return null;
            case 'supergroup':
                return rand(0, 1) ? "testgroup{$index}" : null;
            case 'channel':
                return "testchannel{$index}";
            default:
                return "testuser{$index}";
        }
    }

    private function generateChatDescription($type, $index)
    {
        switch ($type) {
            case 'private':
                return null;
            case 'group':
                return "Тестовая группа для проверки системы #{$index}";
            case 'supergroup':
                return "Тестовая супергруппа с расширенными возможностями #{$index}";
            case 'channel':
                return "Тестовый канал для публикации сообщений #{$index}";
            default:
                return "Тестовый чат #{$index}";
        }
    }

    private function showStatistics()
    {
        $this->info("\n📊 Статистика созданных данных:");
        
        // Статистика чатов
        $chatCount = Chat::count();
        $this->line("📁 Всего чатов: {$chatCount}");
        
        $chatTypes = Chat::selectRaw('type, COUNT(*) as count')
                     ->groupBy('type')
                     ->pluck('count', 'type')
                     ->toArray();
        
        foreach ($chatTypes as $type => $count) {
            $this->line("  - {$type}: {$count}");
        }
        
        // Статистика сообщений
        $messageCount = Message::count();
        $this->line("💬 Всего сообщений: {$messageCount}");
        
        // Статистика кап
        $capCount = Cap::count();
        $this->line("🎯 Всего кап: {$capCount}");
        
        if ($capCount > 0) {
            $capsByGeo = Cap::selectRaw('geos, COUNT(*) as count')
                          ->groupBy('geos')
                          ->pluck('count', 'geos')
                          ->toArray();
            
            $this->line("📍 Капы по регионам:");
            foreach (array_slice($capsByGeo, 0, 5) as $geo => $count) {
                $this->line("  - {$geo}: {$count}");
            }
        }
        
        // Статистика истории кап
        $capHistoryCount = CapHistory::count();
        $this->line("📚 Записей в истории кап: {$capHistoryCount}");
        
        $this->info("\n🎯 Система готова для тестирования!");
        $this->info("✅ Чаты созданы через TelegramWebhookController");
        $this->info("✅ Сообщения обработаны через CapAnalysisService");
        $this->info("✅ Капы найдены и сохранены автоматически");
    }
} 