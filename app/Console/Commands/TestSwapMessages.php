<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use Carbon\Carbon;

class TestSwapMessages extends Command
{
    protected $signature = 'test:swap-messages';
    protected $description = 'Test message updates during chat swap';

    public function handle()
    {
        $this->info('🧪 Testing message updates during chat swap...');
        
        // Получаем топ-10 чатов
        $topChats = Chat::active()
            ->where('display_order', '>', 0)
            ->orderBy('display_order', 'ASC')
            ->take(10)
            ->get();
            
        if ($topChats->count() < 2) {
            $this->error('❌ Need at least 2 chats in top-10 for testing');
            return;
        }
        
        $firstChat = $topChats->first();
        $secondChat = $topChats->skip(1)->first();
        
        $this->info("📊 Testing with chats:");
        $this->info("  1. {$firstChat->title} (ID: {$firstChat->id}) - {$firstChat->messages()->count()} messages");
        $this->info("  2. {$secondChat->title} (ID: {$secondChat->id}) - {$secondChat->messages()->count()} messages");
        
        // Добавляем тестовые сообщения в каждый чат
        $this->info('📝 Adding test messages...');
        
        // Сообщения для первого чата
        Message::create([
            'chat_id' => $firstChat->id,
            'message' => '🔥 ТЕСТ: Сообщение для ' . ($firstChat->title ?: 'Чат #' . $firstChat->chat_id),
            'user' => '🧪 Test Bot',
            'telegram_message_id' => rand(100000, 999999),
            'telegram_user_id' => 123456,
            'telegram_date' => Carbon::now(),
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);
        
        // Сообщения для второго чата
        Message::create([
            'chat_id' => $secondChat->id,
            'message' => '🎯 ТЕСТ: Сообщение для ' . ($secondChat->title ?: 'Чат #' . $secondChat->chat_id),
            'user' => '🧪 Test Bot',
            'telegram_message_id' => rand(100000, 999999),
            'telegram_user_id' => 123456,
            'telegram_date' => Carbon::now(),
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);
        
        $this->info('✅ Test messages added successfully!');
        $this->info('🔄 Now perform a swap in the dashboard and check:');
        $this->info('  1. That each chat shows its own messages');
        $this->info('  2. That messages are properly updated after swap');
        $this->info('  3. That polling works correctly for new chat IDs');
        
        $this->info('📋 Latest messages in each chat:');
        
        // Показываем последние сообщения
        $firstChatMessages = Message::where('chat_id', $firstChat->id)
            ->orderBy('created_at', 'DESC')
            ->take(3)
            ->get();
            
        $secondChatMessages = Message::where('chat_id', $secondChat->id)
            ->orderBy('created_at', 'DESC')
            ->take(3)
            ->get();
            
        $this->info("  {$firstChat->title} (ID: {$firstChat->id}):");
        foreach ($firstChatMessages as $msg) {
            $this->info("    - {$msg->message} ({$msg->created_at->format('H:i:s')})");
        }
        
        $this->info("  {$secondChat->title} (ID: {$secondChat->id}):");
        foreach ($secondChatMessages as $msg) {
            $this->info("    - {$msg->message} ({$msg->created_at->format('H:i:s')})");
        }
        
        return 0;
    }
} 