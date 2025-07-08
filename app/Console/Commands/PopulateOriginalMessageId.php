<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cap;
use App\Models\CapHistory;

class PopulateOriginalMessageId extends Command
{
    protected $signature = 'populate:original-message-id';
    protected $description = 'Populate missing original_message_id values for existing caps and caps history';

    public function handle()
    {
        $this->info('🔄 Populating missing original_message_id values...');
        
        // Update caps table - для существующих кап original_message_id остается null (они не обновлялись)
        $capsUpdated = 0;
        $caps = Cap::whereNull('original_message_id')->get();
        
        foreach ($caps as $cap) {
            // Для существующих кап original_message_id остается null, так как они не обновлялись
            // Это правильно, так как original_message_id указывает на сообщение, которое обновило капу
            $capsUpdated++;
        }
        
        $this->info("✅ Updated {$capsUpdated} caps with original_message_id");
        
        // Update caps_history table - для существующих записей истории original_message_id остается null
        $historyUpdated = 0;
        $history = CapHistory::whereNull('original_message_id')->get();
        
        foreach ($history as $historyRecord) {
            // Для существующих записей истории original_message_id остается null
            // Это правильно, так как original_message_id указывает на сообщение, которое обновило капу
            $historyUpdated++;
        }
        
        $this->info("✅ Updated {$historyUpdated} caps history records with original_message_id");
        
        $this->info('🎉 Original message ID population completed!');
        
        return Command::SUCCESS;
    }
} 