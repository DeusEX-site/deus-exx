<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestReplacementLogic extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-replacement';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that chat replacement logic chooses the oldest chat in top-10';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🔄 Testing chat replacement logic...');
        $this->info('This test verifies that the OLDEST chat (by last incoming message) gets replaced.');
        $this->info('⚡ NO minute waiting - always replace the oldest chat in TOP-10');
        $this->line('');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Получаем топ-10 чатов
        $topTenChats = Chat::active()->topTen()->get();
        
        if ($topTenChats->count() < 10) {
            $this->error('❌ Need exactly 10 chats in TOP-10 to test replacement logic!');
            $this->info('💡 Use: php artisan chats:init-positions to fill top-10');
            return Command::FAILURE;
        }
        
        // Получаем чат не в топ-10 для тестирования
        $topTenIds = $topTenChats->pluck('id');
        $testChat = Chat::active()->whereNotIn('id', $topTenIds)->first();
        
        if (!$testChat) {
            $this->error('❌ Need at least 1 chat outside TOP-10 to test replacement!');
            return Command::FAILURE;
        }
        
        $this->info("🎯 Test chat (will try to enter TOP-10): {$testChat->display_name}");
        $this->line('');
        
        // Устанавливаем разные времена для чатов в топ-10
        $this->info('⏰ Setting up different message times for TOP-10 chats...');
        
        $chatsToAge = $topTenChats->take(4); // Берем первые 4 чата
        $times = [
            now()->subMinutes(2),  // 2 минуты назад
            now()->subSeconds(30), // 30 секунд назад  
            now()->subMinutes(5),  // 5 минут назад (самый старый)
            now()->subMinutes(1),  // 1 минута назад
        ];
        
        foreach ($chatsToAge as $index => $chat) {
            $oldTime = $times[$index];
            
            // Создаем входящее сообщение с нужным временем
            $oldMessage = Message::create([
                'chat_id' => $chat->id,
                'message' => "Test message - {$oldTime->diffForHumans()}",
                'user' => 'Test User',
                'message_type' => 'text',
                'is_outgoing' => false,
                'created_at' => $oldTime,
                'updated_at' => $oldTime,
            ]);
            
            $this->info("  📅 {$chat->display_name}: {$oldTime->diffForHumans()} (Order: {$chat->display_order})");
        }
        
        $this->line('');
        $this->info('📊 Expected behavior: Chat with 5-minute-old message should be replaced');
        $this->line('');
        
        // Создаем новое сообщение в тестовом чате
        $this->info("📤 Sending new message to test chat...");
        $newMessage = Message::create([
            'chat_id' => $testChat->id,
            'message' => 'New message to trigger replacement',
            'user' => 'Test User',
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);
        
        // Обновляем время последнего сообщения
        $testChat->update(['last_message_at' => now()]);
        
        // Тестируем систему позиций
        $result = $chatPositionService->handleNewMessage($testChat->id, false);
        
        $this->info("Result: {$result['status']} - {$result['message']}");
        
        if ($result['status'] === 'swapped') {
            $this->info("✅ Replacement occurred!");
            $this->info("  🔼 Promoted: {$result['promoted_chat_title']}");
            $this->info("  🔽 Demoted: {$result['demoted_chat_title']}");
            
            // Проверяем, что заменен был именно чат с самым старым сообщением
            $expectedOldestChat = $chatsToAge->get(2); // Чат с 5-минутным сообщением
            
            if ($result['demoted_chat'] === $expectedOldestChat->id) {
                $this->info("✅ CORRECT: The chat with oldest message was replaced!");
            } else {
                $this->error("❌ WRONG: Expected to replace '{$expectedOldestChat->display_name}' but replaced '{$result['demoted_chat_title']}'");
                
                // Показываем детали для отладки
                $this->info("Debug info:");
                foreach ($chatsToAge as $index => $chat) {
                    $this->line("  {$chat->display_name}: {$times[$index]->diffForHumans()}");
                }
            }
        } elseif ($result['status'] === 'promoted') {
            $this->info("✅ Chat was promoted to TOP-10 (less than 10 chats were in TOP-10)");
        } else {
            $this->error("❌ Unexpected result: {$result['status']}");
        }
        
        // Показываем позиции после
        $this->line('');
        $this->info('📊 Positions after test:');
        $this->showCurrentPositions($chatPositionService);
        
        // Очистка
        $newMessage->delete();
        foreach ($chatsToAge as $chat) {
            $chat->messages()->where('message', 'like', 'Test message%')->delete();
        }
        
        $this->info('🧹 Test messages cleaned up');
        $this->info('✅ Replacement logic test completed!');
        
        return Command::SUCCESS;
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('Top 10 chats:');
        
        foreach ($positions['top_ten'] as $chat) {
            $lastMessage = $chat['last_message_at'] ? 
                Carbon::parse($chat['last_message_at'])->diffForHumans() : 
                'No messages';
            
            $this->line("  #{$chat['position']} - {$chat['title']} (Order: {$chat['display_order']}, Last: {$lastMessage})");
        }
        
        $this->info('Other chats: ' . count($positions['others']));
        $this->line('');
    }
    
    private function getChatPositions()
    {
        return Chat::active()
                  ->orderByActivity()
                  ->get(['id', 'title', 'display_order'])
                  ->map(function ($chat, $index) {
                      return [
                          'id' => $chat->id,
                          'title' => $chat->title,
                          'display_order' => $chat->display_order,
                          'position' => $index
                      ];
                  })
                  ->toArray();
    }
} 