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
    protected $description = 'Test that TOP-10 chats do not change positions when receiving new messages';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🧪 Testing TOP-10 chat stability...');
        $this->info('This test verifies that messages in TOP-10 chats do NOT change positions.');
        $this->line('');
        
        // Получаем топ-10 чатов
        $topTenChats = Chat::active()->topTen()->get();
        
        if ($topTenChats->count() < 10) {
            $this->error('❌ Need at least 10 chats in TOP-10 to test stability!');
            return Command::FAILURE;
        }
        
        $this->info('📊 Initial state:');
        $this->showCurrentPositions($chatPositionService);
        $this->line('');
        
        // Тестируем каждый чат в топ-10
        foreach ($topTenChats as $index => $chat) {
            $this->info("🎯 Testing TOP-" . ($index + 1) . " chat: {$chat->display_name}");
            
            // Запоминаем текущие позиции
            $beforePositions = $this->getChatPositions();
            
            // Тестируем входящее сообщение
            $this->info("📥 Testing incoming message...");
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => 'Test incoming message for TOP-10 chat',
                'user' => 'Stability Test User',
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
            
            // Обновляем время последнего сообщения
            $chat->update(['last_message_at' => now()]);
            
            // Тестируем систему позиций
            $result = $chatPositionService->handleNewMessage($chat->id, false);
            
            // Проверяем позиции после входящего сообщения
            $afterPositions = $this->getChatPositions();
            
            $positionsChanged = $this->comparePositions($beforePositions, $afterPositions);
            
            if ($positionsChanged) {
                $this->error("❌ FAIL: Positions changed for TOP-10 chat after incoming message!");
                $this->showPositionDiff($beforePositions, $afterPositions);
            } else {
                $this->info("✅ PASS: No position changes for incoming message (expected)");
            }
            
            $this->info("Result: {$result['status']} - {$result['message']}");
            
            // Удаляем входящее сообщение
            $message->delete();
            
            // Тестируем исходящее сообщение (от бота)
            $this->info("📤 Testing outgoing message (from bot)...");
            $outgoingMessage = Message::create([
                'chat_id' => $chat->id,
                'message' => 'Test outgoing message from bot',
                'user' => '🤖 Bot Admin',
                'message_type' => 'text',
                'is_outgoing' => true,
            ]);
            
            // НЕ обновляем last_message_at для исходящих сообщений
            
            // Тестируем систему позиций для исходящего сообщения
            $outgoingResult = $chatPositionService->handleNewMessage($chat->id, true);
            
            // Проверяем позиции после исходящего сообщения
            $afterOutgoingPositions = $this->getChatPositions();
            
            $outgoingPositionsChanged = $this->comparePositions($beforePositions, $afterOutgoingPositions);
            
            if ($outgoingPositionsChanged) {
                $this->error("❌ FAIL: Positions changed after outgoing message!");
                $this->showPositionDiff($beforePositions, $afterOutgoingPositions);
            } else {
                $this->info("✅ PASS: No position changes for outgoing message (expected)");
            }
            
            $this->info("Outgoing result: {$outgoingResult['status']} - {$outgoingResult['message']}");
            
            // Удаляем исходящее сообщение
            $outgoingMessage->delete();
            
            $this->line('');
        }
        
        // Финальная проверка
        $this->info('🏁 Final position check:');
        $this->showCurrentPositions($chatPositionService);
        
        $this->info('✅ Stability test completed!');
        return Command::SUCCESS;
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('Current TOP-10:');
        foreach ($positions['top_ten'] as $chat) {
            $lastMessage = $chat['last_message_at'] ? 
                \Carbon\Carbon::parse($chat['last_message_at'])->diffForHumans() : 
                'No messages';
            
            $this->line("  ТОП {$chat['position']}: {$chat['title']} (Order: {$chat['display_order']}, Last: {$lastMessage})");
        }
    }
    
    private function getChatPositions()
    {
        return Chat::active()
                  ->orderByRaw('
                      CASE 
                          WHEN display_order > 0 THEN display_order
                          ELSE 999999 + id  
                      END DESC
                  ')
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