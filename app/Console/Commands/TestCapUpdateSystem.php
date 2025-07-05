<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CapUpdateService;
use App\Services\CapAnalysisService;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use App\Models\Chat;

class TestCapUpdateSystem extends Command
{
    protected $signature = 'test:cap-update-system';
    protected $description = 'Тестирует систему обновления кап по совпадению aff, brok, geo';

    private $capUpdateService;
    private $capAnalysisService;

    public function __construct(CapUpdateService $capUpdateService, CapAnalysisService $capAnalysisService)
    {
        parent::__construct();
        $this->capUpdateService = $capUpdateService;
        $this->capAnalysisService = $capAnalysisService;
    }

    public function handle()
    {
        $this->info('🚀 Тестирование системы обновления кап...');
        $this->line('');

        // Создаем тестовый чат
        $testChat = $this->createTestChat();
        
        // Тест 1: Создание новых кап
        $this->info('📝 Тест 1: Создание новых кап');
        $this->testCreateNewCaps($testChat);
        
        // Тест 2: Обновление существующих кап
        $this->info('📝 Тест 2: Обновление существующих кап');
        $this->testUpdateExistingCaps($testChat);
        
        // Тест 3: Проверка истории
        $this->info('📝 Тест 3: Проверка истории изменений');
        $this->testCapHistory();
        
        // Тест 4: Статистика
        $this->info('📝 Тест 4: Проверка статистики');
        $this->testStatistics();
        
        // Очистка тестовых данных
        $this->cleanupTestData();
        
        $this->info('');
        $this->info('✅ Тестирование завершено успешно!');
        
        return Command::SUCCESS;
    }

    private function createTestChat()
    {
        return Chat::firstOrCreate([
            'chat_id' => -1001234567999,
            'type' => 'supergroup',
            'title' => 'Test Cap Update Chat',
            'is_active' => true,
        ]);
    }

    private function testCreateNewCaps($testChat)
    {
        $testMessages = [
            'CAP 50 TestAff - TestBrok : RU,KZ 10-19',
            'CAP 30 AnotherAff - AnotherBrok : DE,FR 24/7',
            'CAP 25 ThirdAff - ThirdBrok : US,CA 09-18',
        ];

        foreach ($testMessages as $index => $messageText) {
            $message = Message::create([
                'chat_id' => $testChat->id,
                'message' => $messageText,
                'user' => 'TestUser',
                'telegram_message_id' => 9990 + $index,
                'telegram_user_id' => 12345,
                'message_type' => 'text'
            ]);

            $result = $this->capUpdateService->processNewMessage($message->id, $messageText);
            
            $this->line("  Сообщение: {$messageText}");
            $this->line("  Результат: {$result['new_caps']} новых кап, {$result['updated_caps']} обновлений");
            $this->line('');
        }
    }

    private function testUpdateExistingCaps($testChat)
    {
        // Создаем сообщения, которые должны обновить существующие капы
        $updateMessages = [
            'CAP 75 TestAff - TestBrok : RU,KZ,BY 12-20', // Обновляет первую капу
            'CAP 40 AnotherAff - AnotherBrok : DE,FR,IT 24/7', // Обновляет вторую капу
        ];

        foreach ($updateMessages as $index => $messageText) {
            $message = Message::create([
                'chat_id' => $testChat->id,
                'message' => $messageText,
                'user' => 'UpdateUser',
                'telegram_message_id' => 9995 + $index,
                'telegram_user_id' => 12346,
                'message_type' => 'text'
            ]);

            $result = $this->capUpdateService->processNewMessage($message->id, $messageText);
            
            $this->line("  Сообщение: {$messageText}");
            $this->line("  Результат: {$result['new_caps']} новых кап, {$result['updated_caps']} обновлений");
            
            if ($result['updated_caps'] > 0) {
                $this->info("    ✅ Капа успешно обновлена!");
            } else {
                $this->warn("    ⚠️ Обновления не произошло (создана новая капа)");
            }
            $this->line('');
        }
    }

    private function testCapHistory()
    {
        $caps = Cap::where('affiliate_name', 'LIKE', 'Test%')->get();
        
        foreach ($caps as $cap) {
            $history = CapHistory::getHistoryForCap($cap->id, true);
            
            $this->line("  Капа #{$cap->id} ({$cap->affiliate_name} - {$cap->broker_name}):");
            $this->line("    Записей истории: " . $history->count());
            
            foreach ($history as $record) {
                $this->line("    - {$record->action_type}: {$record->reason}");
            }
            $this->line('');
        }
    }

    private function testStatistics()
    {
        $stats = $this->capUpdateService->getUpdateStatistics();
        
        $this->line("  Общая статистика:");
        $this->line("    Всего кап: {$stats['total_caps']}");
        $this->line("    Записей истории: {$stats['total_history_records']}");
        $this->line("    Обновлений сегодня: {$stats['updates_today']}");
        $this->line("    Новых кап сегодня: {$stats['new_caps_today']}");
        $this->line("    Скрытых записей: {$stats['hidden_records']}");
        $this->line("    Видимых записей: {$stats['visible_records']}");
        $this->line('');
        
        // Тест поиска дубликатов
        $duplicates = $this->capUpdateService->findPotentialDuplicates();
        $this->line("  Найдено потенциальных дубликатов: " . count($duplicates));
        
        foreach ($duplicates as $duplicate) {
            $original = $duplicate['original'];
            $dup = $duplicate['duplicate'];
            $this->warn("    Дубликат: #{$original->id} и #{$dup->id} ({$original->affiliate_name} - {$original->broker_name})");
        }
    }

    private function cleanupTestData()
    {
        $this->info('🧹 Очистка тестовых данных...');
        
        // Удаляем тестовые капы (история удалится автоматически через CASCADE)
        $testCaps = Cap::whereHas('message', function($q) {
            $q->where('user', 'LIKE', '%TestUser%')
              ->orWhere('user', 'LIKE', '%UpdateUser%');
        })->get();
        
        foreach ($testCaps as $cap) {
            $cap->delete();
        }
        
        // Удаляем тестовые сообщения
        Message::where('user', 'LIKE', '%TestUser%')
               ->orWhere('user', 'LIKE', '%UpdateUser%')
               ->delete();
        
        // Удаляем тестовый чат
        Chat::where('title', 'Test Cap Update Chat')->delete();
        
        $this->line('Тестовые данные очищены');
    }
} 