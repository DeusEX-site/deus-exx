<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;
use App\Models\Cap;

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
        
        $this->testNewCapLogic();
        
        return Command::SUCCESS;
    }

    private function testNewCapLogic()
    {
        $this->info('Testing new CAP logic...');
        $this->line('');
        
        $testMessages = [
            "CAP 10\n10-19\n14.09\naff - brok : RU\nmax 100",
            "CAP 30\naff - brok : RU LV\naff2 - brok2 : DE FR",
            "CAP 50\naff - brok\nRU",
            "CAP 20\n24/7\nDE,FR,IT",
            "CAP 40\n- BinaryOptions : DE,FR"
        ];
        
        $capAnalysisService = new CapAnalysisService();
        
        foreach ($testMessages as $message) {
            $this->info("Testing message:");
            $this->info($message);
            $this->line('');
            
            // Analyze and save the message
            $analysis = $capAnalysisService->analyzeAndSaveCapMessage(999, $message);
            
            // Get the results
            $results = $capAnalysisService->searchCaps(null, null);
            
            if (!empty($results)) {
                $this->line("Found " . count($results) . " CAP entries:");
                
                foreach ($results as $result) {
                    $data = $result['analysis'];
                    
                    $this->line("  - CAP Amount: " . ($data['cap_amounts'] ? implode(', ', $data['cap_amounts']) : 'Not found'));
                    $this->line("  - Total Amount: " . ($data['total_amount'] === -1 ? 'Infinity' : ($data['total_amount'] ?: 'Not found')));
                    $this->line("  - Schedule: " . ($data['schedule'] ?: '24/7 (default)'));
                    $this->line("  - Date: " . ($data['date'] ?: 'Today'));
                    $this->line("  - Affiliate: " . ($data['affiliate_name'] ?: 'Missing (WARNING)'));
                    $this->line("  - Broker: " . ($data['broker_name'] ?: 'Missing (CRITICAL)'));
                    $this->line("  - Geo: " . (count($data['geos']) > 0 ? implode(', ', $data['geos']) : 'Missing (CRITICAL)'));
                    
                    // Check validation
                    $warnings = [];
                    $errors = [];
                    
                    if (!$data['broker_name']) {
                        $errors[] = 'Broker name is mandatory';
                    }
                    
                    if (!$data['affiliate_name']) {
                        $warnings[] = 'Affiliate name is missing';
                    }
                    
                    if (count($data['geos']) == 0) {
                        $errors[] = 'Geo is mandatory';
                    }
                    
                    if (!empty($errors)) {
                        $this->error("  ERRORS: " . implode(', ', $errors));
                    }
                    
                    if (!empty($warnings)) {
                        $this->warn("  WARNINGS: " . implode(', ', $warnings));
                    }
                    
                    if (empty($errors) && empty($warnings)) {
                        $this->info("  ✅ All mandatory fields present");
                    }
                    
                    $this->line('');
                }
            }
            
            $this->line('---');
            
            // Clean up test data
            Cap::where('message_id', 999)->delete();
        }
    }
} 