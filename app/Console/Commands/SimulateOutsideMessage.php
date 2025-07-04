<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;

class SimulateOutsideMessage extends Command
{
    protected $signature = 'chats:simulate-outside-message {chat_id?}';
    protected $description = 'Simulate a message in a chat outside TOP-10';

    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('📨 Simulating message in chat outside TOP-10');
        $this->line('');
        
        $chatId = $this->argument('chat_id');
        
        if (!$chatId) {
            // Находим первый чат вне топ-10
            $outsideChat = Chat::active()->withoutTopTen()->first();
            
            if (!$outsideChat) {
                $this->error('❌ No chats found outside TOP-10');
                $this->info('💡 All chats are already in TOP-10');
                return Command::FAILURE;
            }
            
            $chatId = $outsideChat->id;
        }
        
        $chat = Chat::find($chatId);
        if (!$chat) {
            $this->error("❌ Chat with ID {$chatId} not found");
            return Command::FAILURE;
        }
        
        if ($chat->isInTopTen()) {
            $this->error("❌ Chat '{$chat->display_name}' is already in TOP-10 (order: {$chat->display_order})");
            $this->info('💡 Use a chat with display_order = 0');
            return Command::FAILURE;
        }
        
        $this->info("🎯 Simulating message in chat: {$chat->display_name} (ID: {$chat->id})");
        $this->info("   Current display_order: {$chat->display_order}");
        $this->line('');
        
        // Создаем тестовое сообщение
        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => 'Test message to trigger position change',
            'user' => 'Test User',
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);
        
        // Обновляем время последнего сообщения
        $chat->update(['last_message_at' => now()]);
        $chat->increment('message_count');
        
        $this->info('📝 Test message created');
        
        // Вызываем логику позиций
        $result = $chatPositionService->handleNewMessage($chat->id, false);
        
        $this->info('🔄 Position logic result:');
        $this->line("   Status: {$result['status']}");
        $this->line("   Message: {$result['message']}");
        
        if ($result['status'] === 'swapped') {
            $this->info('✅ Chat positions swapped!');
            $this->line("   🔼 Promoted: {$result['promoted_chat_title']} (ID: {$result['promoted_chat']})");
            $this->line("   🔽 Demoted: {$result['demoted_chat_title']} (ID: {$result['demoted_chat']})");
        } elseif ($result['status'] === 'promoted') {
            $this->info('✅ Chat promoted to TOP-10!');
        } elseif ($result['status'] === 'no_change') {
            $this->warning('⚠️ No position changes made');
        } else {
            $this->error("❌ Unexpected status: {$result['status']}");
        }
        
        // Показываем новое состояние
        $chat->refresh();
        $this->line('');
        $this->info('📊 Final state:');
        $this->line("   Chat display_order: {$chat->display_order}");
        $this->line("   Is in TOP-10: " . ($chat->isInTopTen() ? 'Yes' : 'No'));
        
        // Очистка
        $message->delete();
        $this->info('🧹 Test message cleaned up');
        
        return Command::SUCCESS;
    }
} 