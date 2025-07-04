<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestOutgoingMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-outgoing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test that outgoing messages (from bot) do not affect chat positions';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🤖 Testing outgoing message behavior...');
        $this->info('This test verifies that outgoing messages from bot do NOT change chat positions.');
        $this->line('');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Получаем чаты для тестирования
        $chats = Chat::active()->get();
        
        if ($chats->count() < 2) {
            $this->error('❌ Need at least 2 chats to test outgoing messages!');
            return Command::FAILURE;
        }
        
        // Тестируем несколько чатов
        $testChats = $chats->take(3);
        
        foreach ($testChats as $index => $chat) {
            $this->info("🎯 Testing outgoing message to: {$chat->display_name}");
            
            // Запоминаем текущие позиции
            $beforePositions = $this->getChatPositions();
            
            // Создаем исходящее сообщение (от бота)
            $outgoingMessage = Message::create([
                'chat_id' => $chat->id,
                'message' => 'Test outgoing message from bot - should not affect positions',
                'user' => '🤖 Bot Admin',
                'message_type' => 'text',
                'is_outgoing' => true,
            ]);
            
            $this->info("📤 Created outgoing message (ID: {$outgoingMessage->id})");
            
            // Тестируем систему позиций
            $result = $chatPositionService->handleNewMessage($chat->id, true);
            
            // Проверяем позиции после
            $afterPositions = $this->getChatPositions();
            
            $positionsChanged = $this->comparePositions($beforePositions, $afterPositions);
            
            if ($positionsChanged) {
                $this->error("❌ FAIL: Positions changed after outgoing message!");
                $this->showPositionDiff($beforePositions, $afterPositions);
            } else {
                $this->info("✅ PASS: No position changes for outgoing message (expected)");
            }
            
            $this->info("Result: {$result['status']} - {$result['message']}");
            
            // Проверяем, что last_message_at не изменился
            $chat->refresh();
            $lastMessageTime = $chat->last_message_at;
            
            if ($lastMessageTime && $lastMessageTime->diffInSeconds(now()) < 5) {
                $this->error("❌ FAIL: last_message_at was updated for outgoing message!");
            } else {
                $this->info("✅ PASS: last_message_at was not updated for outgoing message");
            }
            
            // Удаляем тестовое сообщение
            $outgoingMessage->delete();
            $this->info("🧹 Cleaned up test message");
            
            $this->line('');
        }
        
        // Финальная проверка позиций
        $this->info('🏁 Final position check:');
        $this->showCurrentPositions($chatPositionService);
        
        $this->info('✅ Outgoing message test completed!');
        return Command::SUCCESS;
    }
    
    private function showCurrentPositions(ChatPositionService $chatPositionService)
    {
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('📊 Current positions:');
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