<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;

class TestEmptyFieldsSystem extends Command
{
    protected $signature = 'test:empty-fields {--clear : Очистить тестовые данные}';
    protected $description = 'Тестирует систему значений по умолчанию для пустых полей';

    private $capAnalysisService;

    public function __construct(CapAnalysisService $capAnalysisService)
    {
        parent::__construct();
        $this->capAnalysisService = $capAnalysisService;
    }

    public function handle()
    {
        if ($this->option('clear')) {
            $this->clearTestData();
            return Command::SUCCESS;
        }

        $this->info('🧪 Тестирование системы значений по умолчанию для пустых полей...');
        
        // Создаем тестовый чат
        $chat = $this->createTestChat();
        
        // Тест 1: Все необязательные поля пустые
        $this->info("\n📝 Тест 1: Все необязательные поля пустые");
        $result1 = $this->testAllEmptyFields($chat);
        $this->displayResult($result1);
        
        // Тест 2: Смешанные пустые и заполненные поля
        $this->info("\n🔄 Тест 2: Смешанные пустые и заполненные поля");
        $result2 = $this->testMixedFields($chat);
        $this->displayResult($result2);
        
        // Тест 3: Множественные значения с пустыми полями
        $this->info("\n📊 Тест 3: Множественные значения с пустыми полями");
        $result3 = $this->testMultipleWithEmpty($chat);
        $this->displayResult($result3);
        
        // Тест 4: Обновление существующей капы с пустыми полями  
        $this->info("\n🔄 Тест 4: Обновление существующей капы с пустыми полями");
        $result4 = $this->testUpdateWithEmptyFields($chat);
        $this->displayResult($result4);
        
        $this->info("\n✅ Все тесты завершены!");
        return Command::SUCCESS;
    }

    private function createTestChat()
    {
        return Chat::firstOrCreate([
            'chat_id' => -98765432,
            'type' => 'supergroup', 
            'title' => 'Test Empty Fields Chat',
            'is_active' => true
        ]);
    }

    private function testAllEmptyFields($chat)
    {
        $messageText = "Affiliate: EmptyTest
Recipient: EmptyRec
Cap: 100
Geo: RU
Total: 
Language: 
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: ";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'EmptyTestUser',
            'telegram_message_id' => rand(10000, 99999),
            'telegram_user_id' => 654321,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'all_empty',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testMixedFields($chat)
    {
        $messageText = "Affiliate: MixedTest
Recipient: MixedRec
Cap: 150
Geo: US
Total: 1000
Language: 
Funnel: TestFunnel
Schedule: 
Date: 25.12.2024
Pending ACQ: 
Freeze status on ACQ: Yes";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'MixedTestUser',
            'telegram_message_id' => rand(10000, 99999),
            'telegram_user_id' => 654322,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'mixed',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testMultipleWithEmpty($chat)
    {
        $messageText = "Affiliate: MultiTest
Recipient: MultiRec
Cap: 50 75 100
Geo: DE AT CH
Total: 
Language: 
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: ";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'MultiTestUser',
            'telegram_message_id' => rand(10000, 99999),
            'telegram_user_id' => 654323,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'multiple_empty',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testUpdateWithEmptyFields($chat)
    {
        // Сначала создаем капу с заполненными полями
        $initialMessage = "Affiliate: UpdateTest
Recipient: UpdateRec
Cap: 200
Geo: FR
Total: 500
Language: fr
Funnel: InitialFunnel
Schedule: 10:00/18:00 GMT+03:00
Date: 01.01.2024
Pending ACQ: Yes
Freeze status on ACQ: No";

        $message1 = Message::create([
            'chat_id' => $chat->id,
            'message' => $initialMessage,
            'user' => 'UpdateTestUser1',
            'telegram_message_id' => rand(10000, 99999),
            'telegram_user_id' => 654324,
            'telegram_date' => now()
        ]);

        // Создаем первую капу
        $this->capAnalysisService->analyzeAndSaveCapMessage($message1->id, $initialMessage);
        
        // Теперь обновляем эту же капу с пустыми полями
        $updateMessage = "Affiliate: UpdateTest
Recipient: UpdateRec
Cap: 250
Geo: FR
Total: 
Language: 
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: ";

        $message2 = Message::create([
            'chat_id' => $chat->id,
            'message' => $updateMessage,
            'user' => 'UpdateTestUser2',
            'telegram_message_id' => rand(10000, 99999),
            'telegram_user_id' => 654325,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message2->id, $updateMessage);
        
        return [
            'type' => 'update_empty',
            'result' => $result,
            'message_id' => $message2->id,
            'initial_message_id' => $message1->id
        ];
    }

    private function displayResult($testResult)
    {
        $result = $testResult['result'];
        
        $this->line("  📊 Результат:");
        $this->line("    - Создано новых: " . ($result['cap_entries_count'] ?? 0));
        $this->line("    - Обновлено: " . ($result['updated_entries_count'] ?? 0));
        $this->line("    - Всего обработано: " . ($result['total_processed'] ?? 0));
        
        // Проверяем значения по умолчанию
        $caps = Cap::whereHas('message', function($q) use ($testResult) {
            $q->where('id', $testResult['message_id']);
        })->get();
        
        $this->line("  🔍 Проверка значений по умолчанию:");
        foreach ($caps as $cap) {
            $geo = $cap->geos[0] ?? 'N/A';
            $capAmount = $cap->cap_amounts[0] ?? 'N/A';
            
            $this->line("    📍 Гео: {$geo}, Капа: {$capAmount}");
            $this->line("      - Total: " . ($cap->total_amount ?? 'NULL'));
            $this->line("      - Language: " . ($cap->language ?? 'NULL'));
            $this->line("      - Funnel: " . ($cap->funnel ?? 'NULL'));
            $this->line("      - Schedule: " . ($cap->schedule ?? 'NULL'));
            $this->line("      - Date: " . ($cap->date ?? 'NULL'));
            $this->line("      - Pending ACQ: " . ($cap->pending_acq ? 'Yes' : 'No'));
            $this->line("      - Freeze status: " . ($cap->freeze_status_on_acq ? 'Yes' : 'No'));
            $this->line("      - Is 24/7: " . ($cap->is_24_7 ? 'Yes' : 'No'));
            $this->line("");
        }
        
        // Специальная проверка для теста обновления
        if ($testResult['type'] === 'update_empty') {
            $this->line("  🔄 Проверка обновления с пустыми полями:");
            
            // Проверяем, что значения изменились на значения по умолчанию
            $cap = $caps->first();
            if ($cap) {
                $changedFields = [];
                if ($cap->total_amount === -1) $changedFields[] = 'Total: -1 (бесконечность)';
                if ($cap->language === 'en') $changedFields[] = 'Language: en (по умолчанию)';
                if ($cap->funnel === null) $changedFields[] = 'Funnel: NULL (по умолчанию)';
                if ($cap->schedule === '24/7') $changedFields[] = 'Schedule: 24/7 (по умолчанию)';
                if ($cap->date === null) $changedFields[] = 'Date: NULL (по умолчанию)';
                if ($cap->pending_acq === false) $changedFields[] = 'Pending ACQ: No (по умолчанию)';
                if ($cap->freeze_status_on_acq === false) $changedFields[] = 'Freeze status: No (по умолчанию)';
                
                $this->line("    ✅ Поля изменены на значения по умолчанию:");
                foreach ($changedFields as $field) {
                    $this->line("      - {$field}");
                }
            }
        }
        
        // Проверяем ожидаемые значения по умолчанию
        if (in_array($testResult['type'], ['all_empty', 'multiple_empty', 'update_empty'])) {
            $expectedDefaults = [
                'total_amount' => -1,
                'language' => 'en',
                'funnel' => null,
                'schedule' => '24/7',
                'date' => null,
                'pending_acq' => false,
                'freeze_status_on_acq' => false,
                'is_24_7' => true
            ];
            
            $this->line("  ✅ Ожидаемые значения по умолчанию:");
            foreach ($expectedDefaults as $field => $expectedValue) {
                if ($expectedValue === null) {
                    $this->line("    - {$field}: NULL");
                } elseif ($expectedValue === false) {
                    $this->line("    - {$field}: No");
                } elseif ($expectedValue === true) {
                    $this->line("    - {$field}: Yes");
                } else {
                    $this->line("    - {$field}: {$expectedValue}");
                }
            }
        }
    }

    private function clearTestData()
    {
        $this->info('🧹 Очистка тестовых данных...');
        
        // Удаляем тестовые сообщения и связанные данные
        Message::whereHas('chat', function($q) {
            $q->where('chat_id', -98765432);
        })->delete();
        
        // Удаляем тестовый чат
        Chat::where('chat_id', -98765432)->delete();
        
        $this->info('✅ Тестовые данные очищены!');
    }
} 