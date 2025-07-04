<?php

namespace App\Console\Commands;

use App\Models\Chat;
use Illuminate\Console\Command;

class FillTopTen extends Command
{
    protected $signature = 'chats:fill-top-ten';
    protected $description = 'Fill TOP-10 with chats from outside to test replacement logic';

    public function handle()
    {
        $this->info('🔄 Filling TOP-10 with chats...');
        
        // Получаем текущие топ-10 чаты
        $topTenChats = Chat::active()->topTen()->get();
        $this->info("📊 Current TOP-10 count: {$topTenChats->count()}");
        
        // Получаем чаты вне топ-10
        $outsideChats = Chat::active()->withoutTopTen()->get();
        $this->info("📊 Chats outside TOP-10: {$outsideChats->count()}");
        
        // Вычисляем сколько мест свободно
        $availableSlots = 10 - $topTenChats->count();
        
        if ($availableSlots <= 0) {
            $this->info("✅ TOP-10 is already full ({$topTenChats->count()}/10)");
            return Command::SUCCESS;
        }
        
        $this->info("🎯 Available slots in TOP-10: {$availableSlots}");
        
        // Заполняем TOP-10
        $maxOrder = $topTenChats->max('display_order') ?? 0;
        $promoted = 0;
        
        foreach ($outsideChats->take($availableSlots) as $chat) {
            $maxOrder++;
            $chat->update(['display_order' => $maxOrder]);
            $this->line("  ✅ Promoted: {$chat->display_name} → Order: {$maxOrder}");
            $promoted++;
        }
        
        $this->info("🎉 Promoted {$promoted} chats to TOP-10");
        
        // Показываем финальное состояние
        $this->info('');
        $this->info('📊 Final TOP-10 state:');
        $finalTopTen = Chat::active()->topTen()->get();
        foreach ($finalTopTen as $chat) {
            $this->line("  #{$chat->id}: {$chat->display_name} (Order: {$chat->display_order})");
        }
        
        $finalOutside = Chat::active()->withoutTopTen()->get();
        $this->info("📊 Chats still outside TOP-10: {$finalOutside->count()}");
        
        if ($finalOutside->count() > 0) {
            $this->info('');
            $this->info('💡 Now you can test replacement by messaging one of these chats:');
            foreach ($finalOutside as $chat) {
                $this->line("  #{$chat->id}: {$chat->display_name}");
            }
        }
        
        return Command::SUCCESS;
    }
} 