<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class TestFullSystem extends Command
{
    protected $signature = 'chats:test-full-system';
    protected $description = 'Full system test of chat position replacement';

    public function handle()
    {
        $this->info('🧪 Starting full system test...');
        
        // Шаг 1: Показать текущее состояние
        $this->info('');
        $this->info('📊 STEP 1: Current state');
        $this->call('chats:debug-positions');
        
        // Шаг 2: Создать чат вне топ-10
        $this->info('');
        $this->info('📊 STEP 2: Creating test chat outside TOP-10');
        $testChat = Chat::create([
            'chat_id' => rand(1000000, 9999999),
            'type' => 'group',
            'title' => 'TestSwapChat',
            'username' => null,
            'description' => 'Test chat for full system test',
            'is_active' => true,
            'last_message_at' => now()->subMinutes(30),
            'message_count' => 3,
            'display_order' => 0 // ВНЕ топ-10
        ]);
        
        $this->info("✅ Created test chat: ID={$testChat->id}, title='{$testChat->title}'");
        
        // Шаг 3: Показать состояние после создания
        $this->info('');
        $this->info('📊 STEP 3: State after creating test chat');
        $this->call('chats:debug-positions');
        
        // Шаг 4: Добавить входящее сообщение в тестовый чат
        $this->info('');
        $this->info('📊 STEP 4: Adding incoming message to test chat');
        
        $message = Message::create([
            'chat_id' => $testChat->id,
            'telegram_message_id' => rand(1000, 9999),
            'message' => 'Test message for position change',
            'user' => 'TestUser',
            'timestamp' => now()->format('H:i:s'),
            'is_telegram' => true,
            'is_outgoing' => false, // ВХОДЯЩЕЕ сообщение
            'message_type' => 'text',
            'created_at' => now()
        ]);
        
        $this->info("✅ Created incoming message: ID={$message->id}");
        
        // Шаг 5: Вызвать ChatPositionService
        $this->info('');
        $this->info('📊 STEP 5: Calling ChatPositionService');
        
        $service = new ChatPositionService();
        $result = $service->handleNewMessage($testChat->id, false); // false = входящее сообщение
        
        $this->info("📋 Result: " . json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Шаг 6: Показать финальное состояние
        $this->info('');
        $this->info('📊 STEP 6: Final state');
        $this->call('chats:debug-positions');
        
        // Шаг 7: Проверить результат
        $this->info('');
        $this->info('📊 STEP 7: Verification');
        
        $freshTestChat = $testChat->fresh();
        if ($freshTestChat->isInTopTen()) {
            $this->info("✅ SUCCESS: Test chat is now in TOP-10 (position {$freshTestChat->display_order})");
        } else {
            $this->error("❌ FAILED: Test chat is still outside TOP-10");
        }
        
        $this->info('');
        $this->info('💡 Instructions:');
        $this->line('1. Check Laravel logs for detailed backend processing');
        $this->line('2. Open dashboard in browser to see frontend swap');
        $this->line('3. Within 3 seconds, you should see position changes on frontend');
        $this->line('4. Check browser console for frontend logs');
        
        return Command::SUCCESS;
    }
} 