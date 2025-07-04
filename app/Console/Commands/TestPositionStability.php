<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestPositionStability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-stability-final';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that TOP-10 chats do NOT change positions when receiving new messages';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🔒 Final Position Stability Test');
        $this->info('Testing that TOP-10 chats do NOT change positions when receiving new messages');
        $this->info('Only chats OUTSIDE TOP-10 can replace the oldest chat inside TOP-10');
        $this->line('');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Получаем топ-10 чатов
        $topTenChats = Chat::active()->topTen()->get();
        
        if ($topTenChats->count() < 10) {
            $this->error('❌ Need exactly 10 chats in TOP-10 to test stability!');
            $this->info('💡 Use: php artisan chats:init-positions to fill top-10');
            return Command::FAILURE;
        }
        
        $this->info('🧪 TEST 1: Messages in TOP-10 chats should NOT change positions');
        $this->line('');
        
        // Тестируем несколько чатов в топ-10
        $testChatsInTopTen = $topTenChats->take(3);
        
        foreach ($testChatsInTopTen as $index => $chat) {
            $this->info("🎯 Testing message in TOP-10 chat: {$chat->display_name} (Position #{$index + 1})");
            
            // Запоминаем позиции до
            $beforePositions = $this->getChatPositions();
            
            // Отправляем сообщение в чат топ-10
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => 'New message in TOP-10 chat - should NOT change positions',
                'user' => 'Test User',
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
            
            // Обновляем время последнего сообщения
            $chat->update(['last_message_at' => now()]);
            
            // Тестируем систему позиций
            $result = $chatPositionService->handleNewMessage($chat->id, false);
            
            // Проверяем результат
            if ($result['status'] === 'no_change') {
                $this->info("✅ CORRECT: No position change for TOP-10 chat");
            } else {
                $this->error("❌ WRONG: Positions changed for TOP-10 chat! Status: {$result['status']}");
            }
            
            // Проверяем что позиции действительно не изменились
            $afterPositions = $this->getChatPositions();
            $positionsChanged = $this->comparePositions($beforePositions, $afterPositions);
            
            if (!$positionsChanged) {
                $this->info("✅ VERIFIED: Positions remained the same");
            } else {
                $this->error("❌ POSITIONS CHANGED!");
                $this->showPositionDiff($beforePositions, $afterPositions);
            }
            
            // Очистка
            $message->delete();
            $this->line('');
        }
        
        $this->info('🧪 TEST 2: Messages OUTSIDE TOP-10 should replace oldest chat');
        $this->line('');
        
        // Получаем чат вне топ-10
        $topTenIds = $topTenChats->pluck('id');
        $outsideChat = Chat::active()->whereNotIn('id', $topTenIds)->first();
        
        if ($outsideChat) {
            $this->info("🎯 Testing message in OUTSIDE chat: {$outsideChat->display_name}");
            
            // Запоминаем позиции до
            $beforePositions = $this->getChatPositions();
            
            // Отправляем сообщение в чат вне топ-10
            $message = Message::create([
                'chat_id' => $outsideChat->id,
                'message' => 'New message outside TOP-10 - should replace oldest',
                'user' => 'Test User',
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
            
            // Обновляем время последнего сообщения
            $outsideChat->update(['last_message_at' => now()]);
            
            // Тестируем систему позиций
            $result = $chatPositionService->handleNewMessage($outsideChat->id, false);
            
            // Проверяем результат
            if ($result['status'] === 'swapped') {
                $this->info("✅ CORRECT: Chat from outside TOP-10 replaced oldest chat");
                $this->info("  🔼 Promoted: {$result['promoted_chat_title']}");
                $this->info("  🔽 Demoted: {$result['demoted_chat_title']}");
            } elseif ($result['status'] === 'promoted') {
                $this->info("✅ CORRECT: Chat promoted to TOP-10 (was space available)");
            } else {
                $this->warning("⚠️ Unexpected result: {$result['status']} - {$result['message']}");
            }
            
            // Очистка
            $message->delete();
        } else {
            $this->warning("⚠️ No chats outside TOP-10 to test replacement");
        }
        
        $this->line('');
        $this->info('📊 Final positions:');
        $this->showCurrentPositions($chatPositionService);
        
        $this->info('✅ Position stability test completed!');
        return Command::SUCCESS;
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('TOP-10 chats:');
        
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
    
    private function comparePositions($before, $after)
    {
        if (count($before) !== count($after)) {
            return true;
        }
        
        foreach ($before as $index => $beforeChat) {
            $afterChat = $after[$index] ?? null;
            
            if (!$afterChat || $beforeChat['id'] !== $afterChat['id']) {
                return true;
            }
        }
        
        return false;
    }
    
    private function showPositionDiff($before, $after)
    {
        $this->error('Position differences:');
        
        $this->info('Before:');
        foreach ($before as $index => $chat) {
            $this->line("  #{$index}: {$chat['title']} (Order: {$chat['display_order']})");
        }
        
        $this->info('After:');
        foreach ($after as $index => $chat) {
            $this->line("  #{$index}: {$chat['title']} (Order: {$chat['display_order']})");
        }
    }
} 