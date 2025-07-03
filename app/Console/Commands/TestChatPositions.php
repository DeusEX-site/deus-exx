<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Models\Message;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;
use Carbon\Carbon;

class TestChatPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:test-positions {--chat-id= : ID чата для тестирования}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test chat position system by simulating new messages';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🧪 Testing chat position system...');
        
        // Показываем текущие позиции
        $this->showCurrentPositions($chatPositionService);
        
        // Если передан ID чата, тестируем на нем
        if ($this->option('chat-id')) {
            $chatId = (int) $this->option('chat-id');
            $this->testSpecificChat($chatId, $chatPositionService);
        } else {
            // Иначе тестируем автоматически
            $this->testAutomatically($chatPositionService);
        }
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

    private function testSpecificChat(int $chatId, ChatPositionService $chatPositionService)
    {
        $chat = Chat::find($chatId);
        
        if (!$chat) {
            $this->error("Chat with ID {$chatId} not found!");
            return;
        }

        $this->info("🎯 Testing chat: {$chat->display_name}");
        
        // Создаем тестовое сообщение
        $message = Message::create([
            'chat_id' => $chat->id,
            'message' => 'Test message for position system',
            'user' => 'Test User',
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);

        // Обновляем время последнего сообщения
        $chat->update(['last_message_at' => now()]);

        // Тестируем систему позиций
        $result = $chatPositionService->handleNewMessage($chat->id);
        
        $this->info("Result: {$result['status']} - {$result['message']}");
        
        if ($result['status'] === 'swapped') {
            $this->info("✅ Position swap occurred!");
            $this->info("  Promoted: {$result['promoted_chat_title']}");
            $this->info("  Demoted: {$result['demoted_chat_title']}");
        }
        
        // Показываем новые позиции
        $this->line('');
        $this->showCurrentPositions($chatPositionService);
        
        // Удаляем тестовое сообщение
        $message->delete();
        $this->info('🧹 Test message cleaned up');
    }

    private function testAutomatically(ChatPositionService $chatPositionService)
    {
        $chats = Chat::active()->get();
        
        if ($chats->count() < 11) {
            $this->error('Need at least 11 chats to test position swapping!');
            return;
        }

        // Находим чат не в топ-10
        $topTenIds = Chat::active()->topTen()->pluck('id');
        $testChat = $chats->whereNotIn('id', $topTenIds)->first();
        
        if (!$testChat) {
            $this->error('No suitable chat found for testing!');
            return;
        }

        $this->info("🤖 Auto-testing with chat: {$testChat->display_name}");
        
        // Устанавливаем старое время для одного из топ-10 чатов
        $topTenChats = Chat::active()->topTen()->get();
        $chatToAge = $topTenChats->random();
        
        $this->info("🕰️ Setting old timestamp for: {$chatToAge->display_name}");
        $chatToAge->update(['last_message_at' => now()->subMinutes(2)]);
        
        // Создаем тестовое сообщение для чата не в топ-3
        $message = Message::create([
            'chat_id' => $testChat->id,
            'message' => 'Auto-test message for position system',
            'user' => 'Auto Test',
            'message_type' => 'text',
            'is_outgoing' => false,
        ]);

        // Тестируем систему позиций
        $result = $chatPositionService->handleNewMessage($testChat->id);
        
        $this->info("Result: {$result['status']} - {$result['message']}");
        
        if ($result['status'] === 'swapped') {
            $this->info("✅ Auto-test successful! Position swap occurred!");
            $this->info("  Promoted: {$result['promoted_chat_title']}");
            $this->info("  Demoted: {$result['demoted_chat_title']}");
        }
        
        // Показываем новые позиции
        $this->line('');
        $this->showCurrentPositions($chatPositionService);
        
        // Удаляем тестовое сообщение
        $message->delete();
        $this->info('🧹 Test message cleaned up');
    }
} 