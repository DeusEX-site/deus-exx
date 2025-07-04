<?php

namespace App\Console\Commands;

use App\Models\Chat;
use Illuminate\Console\Command;

class CreateTestChat extends Command
{
    protected $signature = 'chats:create-test-chat {title=TestChatOutside}';
    protected $description = 'Create a test chat outside TOP-10 for testing replacement logic';

    public function handle()
    {
        $title = $this->argument('title');
        
        // Создаем чат с display_order = 0 (вне топ-10)
        $chat = Chat::create([
            'chat_id' => rand(1000000, 9999999), // Случайный ID
            'type' => 'group',
            'title' => $title,
            'username' => null,
            'description' => 'Test chat for replacement logic',
            'is_active' => true,
            'last_message_at' => now()->subHours(2), // 2 часа назад
            'message_count' => 5,
            'display_order' => 0 // ВНЕ топ-10
        ]);
        
        $this->info("✅ Test chat created:");
        $this->line("   ID: {$chat->id}");
        $this->line("   Title: {$chat->title}");
        $this->line("   Telegram Chat ID: {$chat->chat_id}");
        $this->line("   Display Order: {$chat->display_order} (outside TOP-10)");
        $this->line("   Last Message: {$chat->last_message_at}");
        
        $this->line('');
        $this->info('💡 Now you can test with:');
        $this->line("   php artisan chats:simulate-outside-message {$chat->id}");
        
        return Command::SUCCESS;
    }
} 