<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;

class SimulateSwapMessage extends Command
{
    protected $signature = 'chats:simulate-swap-message';
    protected $description = 'Simulate sending a message to chat #2: eweew to test swap logic';

    public function handle()
    {
        $this->info('🧪 Simulating message to chat #2: eweew...');
        
        // Найти чат #2: eweew
        $chat = Chat::find(2);
        
        if (!$chat) {
            $this->error('❌ Chat #2 not found');
            return Command::FAILURE;
        }
        
        if ($chat->display_name !== 'eweew') {
            $this->error("❌ Chat #2 is not 'eweew', it's '{$chat->display_name}'");
            return Command::FAILURE;
        }
        
        $this->info("✅ Found chat: #{$chat->id} - {$chat->display_name}");
        $this->info("📊 Current display_order: {$chat->display_order}");
        
        // Показать состояние ДО
        $this->info('');
        $this->info('📊 BEFORE - TOP-10 state:');
        $topTenBefore = Chat::active()->topTen()->get();
        foreach ($topTenBefore as $c) {
            $this->line("  #{$c->id}: {$c->display_name} (Order: {$c->display_order})");
        }
        
        // Создать входящее сообщение
        $message = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => rand(1000, 9999),
            'message' => 'Test swap message',
            'user' => 'TestUser',
            'timestamp' => now()->format('H:i:s'),
            'is_telegram' => true,
            'is_outgoing' => false, // ВХОДЯЩЕЕ сообщение
            'message_type' => 'text',
            'created_at' => now()
        ]);
        
        $this->info("✅ Created incoming message: ID={$message->id}");
        
        // Вызвать ChatPositionService
        $service = new ChatPositionService();
        $result = $service->handleNewMessage($chat->id, false); // false = входящее
        
        $this->info('');
        $this->info('📋 ChatPositionService result:');
        $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Показать состояние ПОСЛЕ
        $this->info('');
        $this->info('📊 AFTER - TOP-10 state:');
        $topTenAfter = Chat::active()->topTen()->get();
        foreach ($topTenAfter as $c) {
            $this->line("  #{$c->id}: {$c->display_name} (Order: {$c->display_order})");
        }
        
        // Проверить результат
        $freshChat = $chat->fresh();
        $this->info('');
        if ($freshChat->isInTopTen()) {
            $this->info("✅ SUCCESS: Chat 'eweew' is now in TOP-10 (Order: {$freshChat->display_order})");
        } else {
            $this->error("❌ FAILED: Chat 'eweew' is still outside TOP-10");
        }
        
        $this->info('');
        $this->info('💡 Instructions:');
        $this->line('1. Check Laravel logs for detailed swap information');
        $this->line('2. Refresh dashboard page to see position changes');
        $this->line('3. The swap should be permanent in database');
        
        return Command::SUCCESS;
    }
} 