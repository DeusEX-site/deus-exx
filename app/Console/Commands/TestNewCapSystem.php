<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

class TestNewCapSystem extends Command
{
    protected $signature = 'test:new-cap-system';
    protected $description = 'Тестирует новую систему отдельных записей кап';

    public function handle()
    {
        $this->info('Тестирование новой системы отдельных записей кап...');
        
        // Создаем тестовый чат
        $chat = Chat::firstOrCreate([
            'chat_id' => -999999,
            'type' => 'supergroup',
            'title' => 'Тестовый чат для кап'
        ]);
        
        // Создаем тестовые сообщения
        $testMessages = [
            'CAP 30 Aff - Rec : RU/KZ' . PHP_EOL . 'CAP 20 Aff2 - Rec : AU/US' . PHP_EOL . '10-19',
            'CAP 50 Partner1 - Broker1 : US/CA' . PHP_EOL . '24/7',
            'CAP 25 TestAff - TestBroker : EU/UK' . PHP_EOL . '14.05'
        ];
        
        $createdMessages = [];
        foreach ($testMessages as $index => $messageText) {
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => 'TestUser' . ($index + 1),
                'telegram_message_id' => 1000 + $index,
                'telegram_user_id' => 1000 + $index,
                'created_at' => now()->addMinutes($index)
            ]);
            
            $createdMessages[] = $message;
            $this->info("Создано сообщение {$message->id}: {$messageText}");
        }
        
        // Анализируем сообщения
        $capAnalysisService = new CapAnalysisService();
        
        foreach ($createdMessages as $message) {
            $result = $capAnalysisService->analyzeAndSaveCapMessage($message->id, $message->message);
            $this->info("Анализ сообщения {$message->id}: найдено {$result['cap_entries_count']} записей кап");
        }
        
        // Показываем результаты поиска
        $this->info("\nРезультаты поиска всех кап:");
        $results = $capAnalysisService->searchCaps();
        
        foreach ($results as $result) {
            $this->info("---");
            $this->info("ID: {$result['id']}");
            $this->info("Сообщение: {$result['message']}");
            $this->info("Пользователь: {$result['user']}");
            $this->info("Капа: " . implode(', ', $result['analysis']['cap_amounts']));
            $this->info("Аффилейт: {$result['analysis']['affiliate_name']}");
            $this->info("Брокер: {$result['analysis']['broker_name']}");
            $this->info("Гео: " . implode(', ', $result['analysis']['geos']));
            $this->info("Расписание: {$result['analysis']['schedule']}");
        }
        
        $this->info("\nТестирование завершено! Найдено " . count($results) . " отдельных записей кап");
        
        return Command::SUCCESS;
    }
} 