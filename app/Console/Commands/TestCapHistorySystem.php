<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Models\CapHistory;

class TestCapHistorySystem extends Command
{
    protected $signature = 'test:cap-history {--clear : Очистить тестовые данные}';
    protected $description = 'Тестирует систему истории кап с дубликатами и обновлениями';

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

        $this->info('🧪 Тестирование системы истории кап...');
        
        // Создаем тестовый чат
        $chat = $this->createTestChat();
        
        // Тест 1: Создание новой капы
        $this->info("\n📝 Тест 1: Создание новой капы");
        $result1 = $this->testCreateNewCap($chat);
        $this->displayResult($result1);
        
        // Тест 2: Обновление существующей капы с изменениями
        $this->info("\n🔄 Тест 2: Обновление существующей капы с изменениями");
        $result2 = $this->testUpdateExistingCap($chat);
        $this->displayResult($result2);
        
        // Тест 3: Попытка обновления без изменений
        $this->info("\n⚡ Тест 3: Попытка обновления без изменений");
        $result3 = $this->testUpdateWithoutChanges($chat);
        $this->displayResult($result3);
        
        // Тест 4: Множественные расписания с GMT
        $this->info("\n🕒 Тест 4: Множественные расписания с GMT");
        $result4 = $this->testMultipleSchedulesWithGMT($chat);
        $this->displayResult($result4);
        
        // Тест 5: Обновление с пустыми полями (значения по умолчанию)
        $this->info("\n🔧 Тест 5: Обновление с пустыми полями");
        $result5 = $this->testUpdateWithEmptyFields($chat);
        $this->displayResult($result5);
        
        // Проверяем историю
        $this->info("\n📚 Проверка истории кап:");
        $this->checkHistory();
        
        $this->info("\n✅ Все тесты завершены!");
        return Command::SUCCESS;
    }

    private function createTestChat()
    {
        return Chat::firstOrCreate([
            'chat_id' => -12345678,
            'type' => 'supergroup',
            'title' => 'Test Cap History Chat',
            'is_active' => true
        ]);
    }

    private function testCreateNewCap($chat)
    {
        $messageText = "Affiliate: TestAff1
Recipient: TestRec1
Cap: 50
Total: 500
Geo: US
Language: en
Funnel: TestFunnel
Schedule: 10:00/18:00 GMT+03:00
Date: 25.12.2024
Pending ACQ: No
Freeze status on ACQ: No";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'TestUser',
            'telegram_message_id' => rand(1000, 9999),
            'telegram_user_id' => 123456,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'create',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testUpdateExistingCap($chat)
    {
        // Создаем сообщение с теми же affiliate, recipient, geo но с изменениями
        $messageText = "Affiliate: TestAff1
Recipient: TestRec1
Cap: 75
Total: 750
Geo: US
Language: es
Funnel: UpdatedFunnel
Schedule: 12:00/20:00 GMT+03:00
Date: 26.12.2024
Pending ACQ: Yes
Freeze status on ACQ: Yes";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'TestUser2',
            'telegram_message_id' => rand(1000, 9999),
            'telegram_user_id' => 123457,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'update',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testUpdateWithoutChanges($chat)
    {
        // Создаем точно такое же сообщение без изменений
        $messageText = "Affiliate: TestAff1
Recipient: TestRec1
Cap: 75
Total: 750
Geo: US
Language: es
Funnel: UpdatedFunnel
Schedule: 12:00/20:00 GMT+03:00
Date: 26.12.2024
Pending ACQ: Yes
Freeze status on ACQ: Yes";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'TestUser3',
            'telegram_message_id' => rand(1000, 9999),
            'telegram_user_id' => 123458,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'no_changes',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testMultipleSchedulesWithGMT($chat)
    {
        $messageText = "Affiliate: TestAff2
Recipient: TestRec2
Cap: 30 40
Total: 
Geo: DE FR
Language: de
Funnel: 
Schedule: 18:00/01:00 18:00/02:00 GMT+03:00
Date: 
Pending ACQ: No
Freeze status on ACQ: No";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'TestUser4',
            'telegram_message_id' => rand(1000, 9999),
            'telegram_user_id' => 123459,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'multiple_schedules',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function testUpdateWithEmptyFields($chat)
    {
        // Обновляем существующую капу TestAff1+TestRec1+US с пустыми полями
        $messageText = "Affiliate: TestAff1
Recipient: TestRec1
Cap: 100
Total: 
Geo: US
Language: 
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: ";

        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => $messageText,
            'user' => 'TestUser5',
            'telegram_message_id' => rand(1000, 9999),
            'telegram_user_id' => 123460,
            'telegram_date' => now()
        ]);

        $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
        
        return [
            'type' => 'empty_fields',
            'result' => $result,
            'message_id' => $message->id
        ];
    }

    private function displayResult($testResult)
    {
        $result = $testResult['result'];
        
        $this->line("  📊 Результат:");
        $this->line("    - Создано новых: " . ($result['cap_entries_count'] ?? 0));
        $this->line("    - Обновлено: " . ($result['updated_entries_count'] ?? 0));
        $this->line("    - Всего обработано: " . ($result['total_processed'] ?? 0));
        
        if ($testResult['type'] === 'multiple_schedules') {
            // Проверяем, что GMT применился ко всем записям
            $caps = Cap::whereHas('message', function($q) use ($testResult) {
                $q->where('id', $testResult['message_id']);
            })->get();
            
            $this->line("  🕒 Проверка GMT в расписаниях:");
            foreach ($caps as $cap) {
                $geo = $cap->geos[0] ?? 'N/A';
                $timezone = $cap->timezone ?? 'NULL';
                $this->line("    - Гео: {$geo}, Timezone: {$timezone}");
            }
        }
        
        if ($testResult['type'] === 'empty_fields') {
            // Проверяем, что пустые поля заменились на значения по умолчанию
            $caps = Cap::whereHas('message', function($q) use ($testResult) {
                $q->where('id', $testResult['message_id']);
            })->get();
            
            $this->line("  🔧 Проверка значений по умолчанию:");
            foreach ($caps as $cap) {
                $this->line("    - Капа: " . ($cap->cap_amounts[0] ?? 'N/A'));
                $this->line("    - Total: " . ($cap->total_amount === -1 ? 'бесконечность' : $cap->total_amount));
                $this->line("    - Language: " . ($cap->language ?? 'NULL'));
                $this->line("    - Funnel: " . ($cap->funnel ?? 'NULL'));
                $this->line("    - Schedule: " . ($cap->schedule ?? 'NULL'));
                $this->line("    - Date: " . ($cap->date ?? 'NULL'));
                $this->line("    - Pending ACQ: " . ($cap->pending_acq ? 'true' : 'false'));
                $this->line("    - Freeze status: " . ($cap->freeze_status_on_acq ? 'true' : 'false'));
                break; // Показываем только первую запись
            }
        }
    }

    private function checkHistory()
    {
        $historyCount = CapHistory::count();
        $capsCount = Cap::count();
        
        $this->line("  📈 Статистика:");
        $this->line("    - Записей в истории: {$historyCount}");
        $this->line("    - Активных кап: {$capsCount}");
        
        if ($historyCount > 0) {
            $this->line("  📋 Последние записи истории:");
            CapHistory::latest()->take(3)->get()->each(function($history) {
                $this->line("    - ID капы: {$history->cap_id}, Affiliate: {$history->affiliate_name}, Дата архивации: {$history->archived_at->format('H:i:s')}");
            });
        }
    }

    private function clearTestData()
    {
        $this->info('🧹 Очистка тестовых данных...');
        
        // Удаляем тестовые сообщения и связанные данные
        Message::whereHas('chat', function($q) {
            $q->where('chat_id', -12345678);
        })->delete();
        
        // Удаляем тестовый чат
        Chat::where('chat_id', -12345678)->delete();
        
        $this->info('✅ Тестовые данные очищены!');
    }
} 