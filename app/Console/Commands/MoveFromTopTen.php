<?php

namespace App\Console\Commands;

use App\Models\Chat;
use Illuminate\Console\Command;

class MoveFromTopTen extends Command
{
    protected $signature = 'chats:move-from-top {chat_id}';
    protected $description = 'Move a chat from TOP-10 to outside for testing replacement logic';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        
        $chat = Chat::find($chatId);
        if (!$chat) {
            $this->error("❌ Chat with ID {$chatId} not found");
            return Command::FAILURE;
        }
        
        if (!$chat->isInTopTen()) {
            $this->error("❌ Chat '{$chat->display_name}' is already outside TOP-10");
            return Command::FAILURE;
        }
        
        $oldOrder = $chat->display_order;
        
        // Перемещаем чат вне топ-10
        $chat->update(['display_order' => 0]);
        
        $this->info("✅ Chat moved from TOP-10:");
        $this->line("   Chat: {$chat->display_name}");
        $this->line("   Old display_order: {$oldOrder}");
        $this->line("   New display_order: 0 (outside TOP-10)");
        
        $this->line('');
        $this->info('💡 Now you can test replacement with:');
        $this->line("   php artisan chats:simulate-outside-message {$chat->id}");
        
        return Command::SUCCESS;
    }
} 