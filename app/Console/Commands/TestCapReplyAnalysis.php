<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Services\CapAnalysisService;

class TestCapReplyAnalysis extends Command
{
    protected $signature = 'test:cap-reply-analysis';
    protected $description = 'Test the cap analysis system for REPLY messages (cap updates)';

    public function handle()
    {
        $this->info('🧪 Testing Cap REPLY Analysis System...');
        $this->info('This test checks analysis of REPLY messages for updating existing caps');
        $this->info('');

        // Подготавливаем тестовые данные
        $this->setupTestData();

        // Тестируем анализ ответов
        $this->testReplyAnalysis();

        $this->info('✅ Cap reply analysis test completed!');
    }

    private function setupTestData()
    {
        // Создаем тестовый чат
        $chat = Chat::updateOrCreate(
            ['chat_id' => -1001234567891], // Поиск по уникальному полю
            [
                'type' => 'supergroup',
                'title' => 'Test Cap Reply Chat',
                'is_active' => true,
            ]
        );

        // Очищаем старые данные
        Message::where('chat_id', $chat->id)->where('user', 'TestCapReply')->delete();
        Cap::whereIn('message_id', function($query) use ($chat) {
            $query->select('id')->from('messages')
                  ->where('chat_id', $chat->id)
                  ->where('user', 'TestCapReply');
        })->delete();

        $this->info('📝 Creating original cap messages and caps in database...');

        $capAnalysisService = new CapAnalysisService();

        // Создаем оригинальные сообщения с капами
        $originalMessages = [
            "Affiliate: TestAffiliate1\nRecipient: BinaryBroker\nCap: 30\nGeo: RU\nSchedule: 10-19\nDate: 14.05",
            "Affiliate: XYZ Affiliate\nRecipient: CryptoTrader\nCap: 50\nGeo: US\nSchedule: 24/7\nTotal: 100",
            "Affiliate: MultiAffiliate\nRecipient: MultiBroker\nCap: 25 35\nGeo: DE UK\nSchedule: 9-17",
        ];

        $originalMessageIds = [];

        foreach ($originalMessages as $index => $messageText) {
            // Создаем сообщение
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => 'TestCapReply',
                'telegram_message_id' => 9200 + $index,
                'telegram_user_id' => 123456789,
                'telegram_username' => 'testcapreply',
                'telegram_first_name' => 'Test',
                'telegram_last_name' => 'CapReply',
                'telegram_date' => now(),
                'message_type' => 'text',
                'is_outgoing' => false,
                'reply_to_message_id' => null,
            ]);

            $originalMessageIds[] = $message->id;

            // Анализируем и сохраняем капу в БД
            $result = $capAnalysisService->analyzeAndSaveCapMessage($message->id, $messageText);
            
            $this->line("   Original message #{$message->id}: created {$result['cap_entries_count']} cap(s)");
        }

        $this->info('📝 Creating reply messages for testing updates...');

        // Создаем сообщения-ответы для тестирования обновлений
        $replyMessages = [
            // 1. Обновление Cap для первой капы
            [
                'reply_to' => $originalMessageIds[0],
                'text' => "Cap: 45"
            ],
            
            // 2. Обновление Schedule для первой капы
            [
                'reply_to' => $originalMessageIds[0], 
                'text' => "Schedule: 24/7"
            ],
            
            // 3. Обновление Total для второй капы
            [
                'reply_to' => $originalMessageIds[1],
                'text' => "Total: 200"
            ],
            
            // 4. Обновление нескольких полей для второй капы
            [
                'reply_to' => $originalMessageIds[1],
                'text' => "Cap: 75\nSchedule: 8-20\nLanguage: RU"
            ],
            
            // 5. Обновление конкретного гео для мульти-капы (должен указать Geo)
            [
                'reply_to' => $originalMessageIds[2],
                'text' => "Geo: DE\nCap: 40"
            ],
            
            // 6. Попытка обновления без указания Geo для мульти-капы (должно вернуть ошибку)
            [
                'reply_to' => $originalMessageIds[2],
                'text' => "Cap: 60"
            ],
            
            // 7. Обновление с пустыми значениями (сброс к defaults)
            [
                'reply_to' => $originalMessageIds[0],
                'text' => "Schedule:\nTotal:"
            ],
            
            // 8. Статусные команды
            [
                'reply_to' => $originalMessageIds[0],
                'text' => "STOP"
            ],
            
            [
                'reply_to' => $originalMessageIds[0],
                'text' => "RUN"
            ],
            
            // 9. Попытка обновления несуществующей капы
            [
                'reply_to' => null, // Не reply
                'text' => "Cap: 100"
            ]
        ];

        foreach ($replyMessages as $index => $replyData) {
            Message::create([
                'chat_id' => $chat->id,
                'message' => $replyData['text'],
                'user' => 'TestCapReply',
                'telegram_message_id' => 9300 + $index,
                'telegram_user_id' => 123456789,
                'telegram_username' => 'testcapreply',
                'telegram_first_name' => 'Test',
                'telegram_last_name' => 'CapReply',
                'telegram_date' => now(),
                'message_type' => 'text',
                'is_outgoing' => false,
                'reply_to_message_id' => $replyData['reply_to'],
            ]);
        }

        $this->info('✅ Test data setup completed.');
        $this->line('');
    }

    private function testReplyAnalysis()
    {
        // Получаем все reply сообщения
        $replyMessages = Message::where('user', 'TestCapReply')
            ->whereNotNull('reply_to_message_id')
            ->orderBy('id')
            ->get();

        $this->info("📊 Testing {$replyMessages->count()} reply messages:");
        $this->line('');

        $capAnalysisService = new CapAnalysisService();
        $successCount = 0;
        $errorCount = 0;
        
        foreach ($replyMessages as $index => $message) {
            $messageNum = $index + 1;
            
            $this->line("🔄 REPLY MESSAGE #{$messageNum}:");
            $this->line("📄 Text: " . str_replace("\n", " | ", $message->message));
            $this->line("🔗 Reply to message ID: {$message->reply_to_message_id}");
            
            // Тестируем анализ и сохранение обновления капы
            $result = $capAnalysisService->analyzeAndSaveCapMessage($message->id, $message->message);
            
            if (isset($result['error'])) {
                $errorCount++;
                $this->line("❌ Result: ERROR - " . $result['error']);
            } else {
                $successCount++;
                $created = $result['cap_entries_count'] ?? 0;
                $updated = $result['updated_entries_count'] ?? 0;
                $this->line("✅ Result: SUCCESS");
                if ($created > 0) {
                    $this->line("   - Created {$created} new cap(s)");
                }
                if ($updated > 0) {
                    $this->line("   - Updated {$updated} existing cap(s)");
                }
                if ($created == 0 && $updated == 0) {
                    $this->line("   - No changes made (status command or no updates needed)");
                }
            }
            
            $this->line('');
        }

        // Тестируем обычное сообщение (не reply)
        $nonReplyMessage = Message::where('user', 'TestCapReply')
            ->whereNull('reply_to_message_id')
            ->where('message', 'NOT LIKE', '%Affiliate:%')
            ->first();

        if ($nonReplyMessage) {
            $this->line("📝 NON-REPLY MESSAGE:");
            $this->line("📄 Text: " . $nonReplyMessage->message);
            
            $result = $capAnalysisService->analyzeAndSaveCapMessage($nonReplyMessage->id, $nonReplyMessage->message);
            
            if ($result['cap_entries_count'] == 0) {
                $this->line("✅ Result: Correctly ignored (not a cap message)");
            } else {
                $this->line("❌ Result: Incorrectly processed as cap");
                $errorCount++;
            }
            $this->line('');
        }

        // Показываем итоговое состояние кап в БД
        $this->showFinalCapState();

        $this->info("📈 SUMMARY:");
        $this->info("✅ Successful operations: {$successCount}");
        $this->info("❌ Error operations: {$errorCount}");
        $this->info("📊 Total reply messages tested: " . ($successCount + $errorCount));
        
        if ($successCount >= 7) { // Ожидаем минимум 7 успешных операций
            $this->info("🎉 SUCCESS: Cap reply analysis system is working correctly!");
        } else {
            $this->error("💥 FAILURE: Expected at least 7 successful operations, but got only {$successCount}!");
        }
    }

    private function showFinalCapState()
    {
        $this->info("📊 FINAL CAP STATE IN DATABASE:");
        
        $caps = Cap::whereIn('message_id', function($query) {
            $query->select('id')->from('messages')
                  ->where('user', 'TestCapReply');
        })->orderBy('message_id')->get();

        foreach ($caps as $cap) {
            $this->line("💎 Cap ID {$cap->id} (Message {$cap->message_id}):");
            $this->line("   - Amounts: [" . implode(', ', $cap->cap_amounts) . "]");
            $this->line("   - Total: " . ($cap->total_amount == -1 ? '♾️' : $cap->total_amount));
            $this->line("   - Schedule: " . $cap->schedule);
            $this->line("   - Affiliate: " . $cap->affiliate_name);
            $this->line("   - Recipient: " . $cap->recipient_name);
            $this->line("   - Geos: " . implode(', ', $cap->geos));
            $this->line("   - Status: " . $cap->status);
            if ($cap->language) {
                $this->line("   - Language: " . $cap->language);
            }
            $this->line('');
        }
    }
} 