<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

class TestCapAnalysis extends Command
{
    protected $signature = 'test:cap-analysis';
    protected $description = 'Test the cap analysis system with sample messages';

    public function handle()
    {
        $this->info('🧪 Testing Cap Analysis System...');
        $this->info('');

        // Создаем тестовые сообщения если их нет
        $this->createTestMessages();

        // Тестируем анализ
        $this->testAnalysis();

        $this->info('✅ Cap analysis test completed!');
    }

    private function createTestMessages()
    {
        // Создаем тестовый чат если его нет
        $chat = Chat::firstOrCreate([
            'chat_id' => -1001234567890,
            'type' => 'supergroup',
            'title' => 'Test Cap Chat',
            'is_active' => true,
        ]);

        // Удаляем старые тестовые сообщения
        Message::where('chat_id', $chat->id)->where('user', 'TestUser')->delete();

        // Тестовые сообщения в НОВОМ формате
        $testMessages = [
            // Стандартные капы
            "Affiliate: TestAffiliate1\nRecipient: BinaryBroker\nCap: 30\nGeo: RU,KZ\nSchedule: 10-19\nDate: 14.05",
            
            "Affiliate: XYZ Affiliate\nRecipient: BinaryBroker\nCap: 50\nGeo: DE,FR,UK\nSchedule: 24/7",
            
            "Affiliate: TestAffiliate\nRecipient: CryptoTrader\nCap: 25\nGeo: RU,UA,KZ\nSchedule: 10-18",
            
            "Affiliate: MyAffiliate\nRecipient: ForexPro\nCap: 40\nGeo: US,CA,AU\nSchedule: 24/7",
            
            // Капы с дополнительными полями
            "Affiliate: SuperAffiliate\nRecipient: BinaryOptions\nCap: 100\nGeo: IT,ES,PT\nSchedule: 9-17\nDate: 25.12\nTotal: 500\nLanguage: EN",
            
            "Affiliate: RussianAffiliate\nRecipient: TradeMax\nCap: 15\nGeo: RU,BY,KZ\nSchedule: 09-17\nLanguage: RU\nFunnel: crypto",
            
            "Affiliate: EnglishAffiliate\nRecipient: TradingPlatform\nCap: 75\nGeo: GB,IE,US\nSchedule: 24/7\nTotal: 1000",
            
            // Капы с особыми значениями
            "Affiliate: TestCompany\nRecipient: BrokerName\nCap: 20\nGeo: FR,DE,NL\nSchedule: 24/7\nTotal: -1",
            
            "Affiliate: SpecialAffiliate\nRecipient: TestBroker\nCap: 35\nGeo: CA\nSchedule: 8-16\nPending ACQ: Yes",
            
            "Affiliate: AnotherAffiliate\nRecipient: AnotherBroker\nCap: 45\nGeo: UK,AU\nSchedule: 12:00-20:00\nFreeze status: Yes",
            
            // Сообщения БЕЗ cap (для проверки)
            "Общий объем 500 лидов на сегодня",
            "Нужно 200 лидов до конца месяца",
            "Лимит 300 на сегодня",
            "Статус: активно",
            "Обновление системы завершено"
        ];

        foreach ($testMessages as $index => $messageText) {
            Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => 'TestUser',
                'telegram_message_id' => 9000 + $index,
                'telegram_user_id' => 123456789,
                'telegram_username' => 'testuser',
                'telegram_first_name' => 'Test',
                'telegram_last_name' => 'User',
                'telegram_date' => now(),
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
        }

        $this->info('📝 Test messages created.');
    }

    private function testAnalysis()
    {
        // Поиск всех тестовых сообщений
        $messages = Message::where('user', 'TestUser')
            ->orderBy('id', 'desc')
            ->limit(15)
            ->get();

        $this->info("📊 Found {$messages->count()} test messages for analysis:");
        $this->line('');

        $capAnalysisService = new CapAnalysisService();
        $capCount = 0;
        $nonCapCount = 0;
        
        foreach ($messages as $message) {
            $analysis = $capAnalysisService->analyzeCapMessage($message->message);
            
            if ($analysis['has_cap_word']) {
                $capCount++;
                $this->line("✅ CAP MESSAGE #{$message->id}:");
                $this->line("📄 Message: " . str_replace("\n", " | ", $message->message));
                $this->line("🔍 Analysis:");
                $this->line("  - Cap amounts: " . (count($analysis['cap_amounts']) > 0 ? '[' . implode(', ', $analysis['cap_amounts']) . ']' : '❌ Not found'));
                $this->line("  - Total amount: " . ($analysis['total_amount'] !== null ? $analysis['total_amount'] : '♾️ Unlimited'));
                $this->line("  - Schedule: " . ($analysis['schedule'] ?: '24/7'));
                $this->line("  - Date: " . ($analysis['date'] ?: '♾️ Permanent'));
                $this->line("  - Affiliate: " . ($analysis['affiliate_name'] ?: '❌ Not found'));
                $this->line("  - Recipient: " . ($analysis['recipient_name'] ?: '❌ Not found'));
                $this->line("  - Geos: " . (count($analysis['geos']) > 0 ? implode(', ', $analysis['geos']) : '❌ Not found'));
                if ($analysis['language']) {
                    $this->line("  - Language: " . $analysis['language']);
                }
                if ($analysis['funnel']) {
                    $this->line("  - Funnel: " . $analysis['funnel']);
                }
                if ($analysis['pending_acq']) {
                    $this->line("  - Pending ACQ: Yes");
                }
                if ($analysis['freeze_status_on_acq']) {
                    $this->line("  - Freeze status: Yes");
                }
            } else {
                $nonCapCount++;
                $this->line("❌ NON-CAP MESSAGE #{$message->id}:");
                $this->line("📄 Message: " . str_replace("\n", " | ", $message->message));
                $this->line("🔍 Analysis: No cap detected");
            }
            $this->line('');
        }
        
        $this->info("📈 SUMMARY:");
        $this->info("✅ Cap messages found: {$capCount}");
        $this->info("❌ Non-cap messages: {$nonCapCount}");
        $this->info("📊 Total messages analyzed: " . ($capCount + $nonCapCount));
        
        // Проверяем, что система правильно распознает капы
        if ($capCount > 0) {
            $this->info("🎉 SUCCESS: Cap analysis system is working correctly!");
        } else {
            $this->error("💥 FAILURE: Cap analysis system is not detecting any caps!");
        }
    }
} 