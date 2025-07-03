<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;

class TestTopThreeStability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-stability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that TOP-3 chats do not change positions when receiving new messages';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🔒 Testing TOP-3 chats stability...');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Получаем топ-3 чаты
        $topThreeChats = Chat::active()->topThree()->get();
        
        if ($topThreeChats->count() < 3) {
            $this->error('Need at least 3 chats in TOP-3 for testing!');
            return;
        }
        
        $this->info('📊 Testing each TOP-3 chat...');
        
        foreach ($topThreeChats as $index => $chat) {
            $this->info("🎯 Testing TOP-" . ($index + 1) . " chat: {$chat->display_name}");
            
            // Запоминаем текущие позиции
            $beforePositions = $this->getChatPositions();
            
            // Создаем тестовое сообщение
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => 'Test stability message for TOP-3 chat',
                'user' => 'Stability Test',
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
            
            // Обновляем время последнего сообщения
            $chat->update(['last_message_at' => now()]);
            
            // Тестируем систему позиций
            $result = $chatPositionService->handleNewMessage($chat->id);
            
            // Проверяем позиции после
            $afterPositions = $this->getChatPositions();
            
            // Сравниваем позиции
            $positionsChanged = $this->comparePositions($beforePositions, $afterPositions);
            
            if ($positionsChanged) {
                $this->error("❌ FAIL: Positions changed for TOP-3 chat!");
                $this->showPositionDiff($beforePositions, $afterPositions);
            } else {
                $this->info("✅ PASS: No position changes (expected)");
            }
            
            $this->info("Result: {$result['status']} - {$result['message']}");
            
            // Удаляем тестовое сообщение
            $message->delete();
            
            $this->line('');
        }
        
        // Финальная проверка
        $this->info('🏁 Final position check:');
        $this->showCurrentPositions($chatPositionService);
        
        $this->info('✅ Stability test completed!');
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('Current TOP-3:');
        foreach ($positions['top_three'] as $chat) {
            $lastMessage = $chat['last_message_at'] ? 
                \Carbon\Carbon::parse($chat['last_message_at'])->diffForHumans() : 
                'No messages';
            
            $this->line("  ТОП {$chat['position']}: {$chat['title']} (Order: {$chat['display_order']}, Last: {$lastMessage})");
        }
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
        
        for ($i = 0; $i < max(count($before), count($after)); $i++) {
            $beforeChat = $before[$i] ?? null;
            $afterChat = $after[$i] ?? null;
            
            if ($beforeChat && $afterChat) {
                if ($beforeChat['id'] !== $afterChat['id']) {
                    $this->line("  Position {$i}: {$beforeChat['title']} -> {$afterChat['title']}");
                }
            } elseif ($beforeChat) {
                $this->line("  Position {$i}: {$beforeChat['title']} -> [REMOVED]");
            } elseif ($afterChat) {
                $this->line("  Position {$i}: [NEW] -> {$afterChat['title']}");
            }
        }
    }
} 