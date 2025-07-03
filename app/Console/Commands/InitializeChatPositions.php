<?php

namespace App\Console\Commands;

use App\Services\ChatPositionService;
use Illuminate\Console\Command;

class InitializeChatPositions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'chats:init-positions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Initialize chat display positions based on last message time';

    /**
     * Execute the console command.
     */
    public function handle(ChatPositionService $chatPositionService)
    {
        $this->info('Initializing chat positions...');
        
        $chatPositionService->initializePositions();
        
        $this->info('Chat positions initialized successfully!');
        
        // Показываем текущие позиции
        $positions = $chatPositionService->getCurrentPositions();
        
        $this->info('Current top 10 chats:');
        foreach ($positions['top_ten'] as $chat) {
            $this->line("Position {$chat['position']}: {$chat['title']} (Order: {$chat['display_order']})");
        }
        
        $this->info('Other chats: ' . count($positions['others']));
    }
} 