<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestTopTenRule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-top-ten';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the one-minute rule for TOP-10 chat position changes';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🔟 Testing TOP-10 one-minute rule...');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Получаем топ-10 чаты
        $topTenChats = Chat::active()->topTen()->get();
        $otherChats = Chat::active()->withoutTopTen()->get();
        
        if ($topTenChats->count() < 10) {
            $this->error('Need at least 10 chats in TOP-10 for testing!');
            return;
        }
        
        if ($otherChats->count() < 1) {
            $this->error('Need at least 1 chat outside TOP-10 for testing!');
            return;
        }
        
        // Берем случайный чат из топ-10 и делаем его "старым"
        $oldChat = $topTenChats->random();
        $newChat = $otherChats->first();
        
        $this->info("🎯 Setting up test scenario:");
        $this->line("  - Making chat '{$oldChat->display_name}' old (> 1 minute)");
        $this->line("  - Will send message to '{$newChat->display_name}' (outside TOP-10)");
        
        // Устанавливаем старое время для чата в топ-10
        $oldTime = Carbon::now()->subMinutes(2);
        $oldChat->update(['last_message_at' => $oldTime]);
        
        $this->info("✅ Set '{$oldChat->display_name}' last message time to: " . $oldTime->diffForHumans());
        
        // Показываем состояние перед тестом
        $this->line('');
        $this->info('📊 Current state:');
        $this->showDetailedPositions($chatPositionService);
        
        // Создаем тестовое сообщение в чате вне топ-10
        $this->info("📤 Sending test message to '{$newChat->display_name}'...");
        
        $message = Message::create([
            'chat_id' => $newChat->id,
            'message' => 'Test message to trigger TOP-10 position change',
            'user' => 'TOP-10 Rule Test',
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);
        
        // Тестируем систему позиций
        $result = $chatPositionService->handleNewMessage($newChat->id);
        
        $this->line('');
        $this->info("🔄 Position system result: {$result['status']} - {$result['message']}");
        
        if ($result['status'] === 'swapped') {
            $this->info("✅ SUCCESS: Positions were swapped!");
            $this->info("  🔼 Promoted: {$result['promoted_chat_title']}");
            $this->info("  🔽 Demoted: {$result['demoted_chat_title']}");
        } elseif ($result['status'] === 'no_change') {
            $this->warning("⚠️ No position change - this might be expected if all TOP-10 chats are fresh");
        } elseif ($result['status'] === 'promoted') {
            $this->info("✅ SUCCESS: Chat was promoted to TOP-10!");
        }
        
        // Показываем финальное состояние
        $this->line('');
        $this->info('📊 Final state:');
        $this->showDetailedPositions($chatPositionService);
        
        // Очистка
        $message->delete();
        $this->info('🧹 Test message cleaned up');
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('Current TOP-10:');
        foreach ($positions['top_ten'] as $chat) {
            $lastMessage = $chat['last_message_at'] ? 
                Carbon::parse($chat['last_message_at'])->diffForHumans() : 
                'No messages';
            
            $this->line("  ТОП {$chat['position']}: {$chat['title']} (Last: {$lastMessage})");
        }
    }
    
    private function showDetailedPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        $oneMinuteAgo = Carbon::now()->subMinute();
        
        $this->line('TOP-10 chats:');
        foreach ($positions['top_ten'] as $chat) {
            $lastMessageTime = $chat['last_message_at'] ? Carbon::parse($chat['last_message_at']) : null;
            $minutesAgo = $lastMessageTime ? $lastMessageTime->diffInMinutes(Carbon::now()) : null;
            
            // Use the same logic as ChatPositionService
            $isOlderThanMinute = $lastMessageTime && $lastMessageTime->lt($oneMinuteAgo);
            
            $status = $isOlderThanMinute ? '🔴 OLD' : '🟢 FRESH';
            
            $this->line("  ТОП {$chat['position']}: {$chat['title']} - {$status}");
            $this->line("    Last message: " . ($lastMessageTime ? $lastMessageTime->toDateTimeString() : 'none'));
            $this->line("    Minutes ago: " . ($minutesAgo ?? 'unknown'));
            $this->line("    Order: {$chat['display_order']}");
        }
        
        if (!empty($positions['others'])) {
            $this->line('Other chats: ' . count($positions['others']));
        }
    }
} 