<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

class TestCapAnalysis extends Command
{
    protected $signature = 'test:cap-analysis';
    protected $description = 'Test the cap analysis system for NEW cap messages (not replies)';

    public function handle()
    {
        $this->info('🧪 Testing NEW Cap Message Analysis System...');
        $this->info('This test checks analysis of NEW cap messages (not replies)');
        $this->info('');

        // Создаем тестовые сообщения если их нет
        $this->createTestMessages();

        // Тестируем анализ
        $this->testAnalysis();

        $this->info('✅ New cap message analysis test completed!');
    }

    private function createTestMessages()
    {
        // Создаем тестовый чат если его нет
        $chat = Chat::updateOrCreate(
            ['chat_id' => -1001234567890], // Поиск по уникальному полю
            [
                'type' => 'supergroup',
                'title' => 'Test Cap Analysis Chat',
                'is_active' => true,
            ]
        );

        // Удаляем старые тестовые сообщения
        Message::where('chat_id', $chat->id)->where('user', 'TestCapAnalysis')->delete();

        // ПРАВИЛЬНЫЕ тестовые сообщения в новом формате
        // ВАЖНО: количество Cap и Geo должно совпадать!
        $testMessages = [
            // === ВАЛИДНЫЕ СООБЩЕНИЯ ===
            
            // 1. Простая капа (1 cap = 1 geo)
            "Affiliate: TestAffiliate1\nRecipient: BinaryBroker\nCap: 30\nGeo: RU\nSchedule: 10-19\nDate: 14.05",
            
            // 2. Капа с двумя гео (2 cap = 2 geo)
            "Affiliate: XYZ Affiliate\nRecipient: BinaryBroker\nCap: 50 25\nGeo: DE UK\nSchedule: 24/7",
            
            // 3. Капа с тремя гео (3 cap = 3 geo)
            "Affiliate: TestAffiliate\nRecipient: CryptoTrader\nCap: 25 30 20\nGeo: RU UA KZ\nSchedule: 10-18",
            
            // 4. Простая капа с 24/7
            "Affiliate: MyAffiliate\nRecipient: ForexPro\nCap: 40\nGeo: US\nSchedule: 24/7",
            
            // 5. Капа с дополнительными полями
            "Affiliate: SuperAffiliate\nRecipient: BinaryOptions\nCap: 100\nGeo: IT\nSchedule: 9-17\nDate: 25.12\nTotal: 500\nLanguage: EN",
            
            // 6. Капа с русским языком и воронкой
            "Affiliate: RussianAffiliate\nRecipient: TradeMax\nCap: 15\nGeo: RU\nSchedule: 09-17\nLanguage: RU\nFunnel: crypto",
            
            // 7. Капа с общим лимитом
            "Affiliate: EnglishAffiliate\nRecipient: TradingPlatform\nCap: 75\nGeo: GB\nSchedule: 24/7\nTotal: 1000",
            
            // 8. Капа с бесконечным лимитом
            "Affiliate: TestCompany\nRecipient: BrokerName\nCap: 20\nGeo: FR\nSchedule: 24/7\nTotal: -1",
            
            // 9. Капа с Pending ACQ
            "Affiliate: SpecialAffiliate\nRecipient: TestBroker\nCap: 35\nGeo: CA\nSchedule: 8-16\nPending ACQ: Yes",
            
            // 10. Капа с Freeze status
            "Affiliate: AnotherAffiliate\nRecipient: AnotherBroker\nCap: 45\nGeo: AU\nSchedule: 12:00-20:00\nFreeze status: Yes",
            
            // 11. Множественные капы с несколькими гео (4=4)
            "Affiliate: MultiAffiliate\nRecipient: MultiBroker\nCap: 10 20 30 40\nGeo: US UK DE FR\nSchedule: 24/7\nTotal: 200",
            
            // === НЕВАЛИДНЫЕ СООБЩЕНИЯ ===
            
            // 12. Отсутствует Affiliate
            "Recipient: BrokerName\nCap: 20\nGeo: FR",
            
            // 13. Отсутствует Recipient  
            "Affiliate: TestAffiliate\nCap: 30\nGeo: RU",
            
            // 14. Отсутствует Cap
            "Affiliate: TestAffiliate\nRecipient: BrokerName\nGeo: RU",
            
            // 15. Отсутствует Geo
            "Affiliate: TestAffiliate\nRecipient: BrokerName\nCap: 30",
            
            // 16. Количество Cap != Geo (2 cap, 1 geo)
            "Affiliate: TestAffiliate\nRecipient: BrokerName\nCap: 30 40\nGeo: RU",
            
            // 17. Количество Cap != Geo (1 cap, 2 geo)  
            "Affiliate: TestAffiliate\nRecipient: BrokerName\nCap: 30\nGeo: RU KZ",
            
            // 18. Обычные сообщения (не капы)
            "Общий объем 500 лидов на сегодня",
            "Нужно 200 лидов до конца месяца",
            "Статус: активно"
        ];

        foreach ($testMessages as $index => $messageText) {
            Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => 'TestCapAnalysis',
                'telegram_message_id' => 9100 + $index,
                'telegram_user_id' => 123456789,
                'telegram_username' => 'testcapanalysis',
                'telegram_first_name' => 'Test',
                'telegram_last_name' => 'CapAnalysis',
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
        $messages = Message::where('user', 'TestCapAnalysis')
            ->orderBy('id')
            ->get();

        $this->info("📊 Found {$messages->count()} test messages for analysis:");
        $this->line('');

        $capAnalysisService = new CapAnalysisService();
        $validCapCount = 0;
        $invalidCapCount = 0;
        $nonCapCount = 0;
        
        foreach ($messages as $index => $message) {
            $analysis = $capAnalysisService->analyzeCapMessage($message->message);
            
            $messageNum = $index + 1;
            
            if ($analysis['has_cap_word']) {
                $validCapCount++;
                $this->line("✅ VALID CAP MESSAGE #{$messageNum}:");
                $this->line("📄 Text: " . str_replace("\n", " | ", $message->message));
                $this->line("🔍 Analysis:");
                $this->line("  - Cap amounts: [" . implode(', ', $analysis['cap_amounts']) . "]");
                $this->line("  - Total amount: " . ($analysis['total_amount'] === -1 ? '♾️ Unlimited' : $analysis['total_amount']));
                $this->line("  - Schedule: " . ($analysis['schedule'] ?: '24/7'));
                $this->line("  - Date: " . ($analysis['date'] ?: '♾️ Permanent'));
                $this->line("  - Affiliate: " . $analysis['affiliate_name']);
                $this->line("  - Recipient: " . $analysis['recipient_name']);
                $this->line("  - Geos: " . implode(', ', $analysis['geos']));
                if ($analysis['language'] && $analysis['language'] !== 'en') {
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
                // Проверяем, должно ли это быть капой (содержит обязательные поля)
                $hasAffiliateField = strpos($message->message, 'Affiliate:') !== false;
                $hasRecipientField = strpos($message->message, 'Recipient:') !== false;
                $hasCapField = strpos($message->message, 'Cap:') !== false;
                $hasGeoField = strpos($message->message, 'Geo:') !== false;
                
                if ($hasAffiliateField || $hasRecipientField || $hasCapField || $hasGeoField) {
                    $invalidCapCount++;
                    $this->line("❌ INVALID CAP MESSAGE #{$messageNum}:");
                    $this->line("📄 Text: " . str_replace("\n", " | ", $message->message));
                    $this->line("🔍 Analysis: Invalid cap format or missing required fields");
                    
                    // Детализируем причину
                    $missing = [];
                    if (!$hasAffiliateField) $missing[] = 'Affiliate';
                    if (!$hasRecipientField) $missing[] = 'Recipient';
                    if (!$hasCapField) $missing[] = 'Cap';
                    if (!$hasGeoField) $missing[] = 'Geo';
                    
                    if (count($missing) > 0) {
                        $this->line("  - Missing fields: " . implode(', ', $missing));
                    } else {
                        $this->line("  - Possible issue: Cap/Geo count mismatch or invalid values");
                    }
                } else {
                    $nonCapCount++;
                    $this->line("ℹ️ NON-CAP MESSAGE #{$messageNum}:");
                    $this->line("📄 Text: " . str_replace("\n", " | ", $message->message));
                    $this->line("🔍 Analysis: Not a cap message (expected)");
                }
            }
            $this->line('');
        }
        
        $this->info("📈 SUMMARY:");
        $this->info("✅ Valid cap messages: {$validCapCount}");
        $this->info("❌ Invalid cap messages: {$invalidCapCount}");
        $this->info("ℹ️ Non-cap messages: {$nonCapCount}");
        $this->info("📊 Total messages analyzed: " . ($validCapCount + $invalidCapCount + $nonCapCount));
        
        // Проверяем результаты
        if ($validCapCount >= 11) { // Ожидаем 11 валидных кап
            $this->info("🎉 SUCCESS: Cap analysis system correctly detects valid cap messages!");
        } else {
            $this->error("💥 FAILURE: Expected 11 valid caps, but found only {$validCapCount}!");
        }
        
        if ($invalidCapCount >= 6) { // Ожидаем 6 невалидных кап
            $this->info("🎯 SUCCESS: Cap analysis system correctly rejects invalid cap messages!");
        } else {
            $this->warn("⚠️ WARNING: Expected 6 invalid caps, but found only {$invalidCapCount}!");
        }
    }
} 