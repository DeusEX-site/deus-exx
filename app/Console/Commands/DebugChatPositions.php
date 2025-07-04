<?php

namespace App\Console\Commands;

use App\Models\Chat;
use App\Services\ChatPositionService;
use Illuminate\Console\Command;

class DebugChatPositions extends Command
{
    protected $signature = 'chats:debug-positions';
    protected $description = 'Debug chat positions and top-10 logic';

    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('🔍 Chat Positions Debug');
        $this->line('');
        
        // Показываем все чаты
        $allChats = Chat::active()->orderBy('display_order', 'desc')->orderBy('id', 'asc')->get();
        
        $this->info('📊 All active chats:');
        foreach ($allChats as $chat) {
            $isTopTen = $chat->isInTopTen() ? '✅ TOP-10' : '❌ Outside';
            $this->line("  Chat #{$chat->id}: {$chat->display_name} | Order: {$chat->display_order} | {$isTopTen}");
        }
        
        $this->line('');
        
        // Показываем топ-10 чаты
        $topTenChats = Chat::active()->topTen()->get();
        $this->info("🔝 TOP-10 chats (found {$topTenChats->count()}):");
        
        if ($topTenChats->count() > 0) {
            foreach ($topTenChats as $chat) {
                $this->line("  #{$chat->id}: {$chat->display_name} (Order: {$chat->display_order})");
            }
        } else {
            $this->error("  ❌ No chats in TOP-10!");
            $this->info("  💡 Run: php artisan chats:init-positions");
        }
        
        $this->line('');
        
        // Показываем чаты вне топ-10
        $outsideChats = Chat::active()->withoutTopTen()->get();
        $this->info("📤 Chats outside TOP-10 (found {$outsideChats->count()}):");
        
        if ($outsideChats->count() > 0) {
            foreach ($outsideChats as $chat) {
                $this->line("  #{$chat->id}: {$chat->display_name} (Order: {$chat->display_order})");
            }
        } else {
            $this->info("  ✅ All chats are in TOP-10");
        }
        
        $this->line('');
        
        // Тестируем логику для чата вне топ-10
        if ($outsideChats->count() > 0) {
            $testChat = $outsideChats->first();
            $this->info("🧪 Testing position logic for outside chat: {$testChat->display_name}");
            
            $result = $chatPositionService->handleNewMessage($testChat->id, false);
            
            $this->info("  Result: {$result['status']} - {$result['message']}");
            
            if ($result['status'] === 'swapped') {
                $this->info("  🔼 Promoted: {$result['promoted_chat_title']}");
                $this->info("  🔽 Demoted: {$result['demoted_chat_title']}");
            }
        }
        
        $this->line('');
        $this->info('🔍 Debug completed. Check logs for detailed information.');
        
        return Command::SUCCESS;
    }
} 