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
        
        // Update caps table
        $capsUpdated = 0;
        $caps = Cap::whereNull('original_message_id')->get();
        
        foreach ($caps as $cap) {
            $cap->update(['original_message_id' => $cap->message_id]);
            $capsUpdated++;
        }
        
        $this->info("✅ Updated {$capsUpdated} caps with original_message_id");
        
        // Update caps_history table
        $historyUpdated = 0;
        $history = CapHistory::whereNull('original_message_id')->get();
        
        foreach ($history as $historyRecord) {
            $historyRecord->update(['original_message_id' => $historyRecord->message_id]);
            $historyUpdated++;
        }
        
        $this->info("✅ Updated {$historyUpdated} caps history records with original_message_id");
        
        $this->info('🎉 Original message ID population completed!');
        
        return Command::SUCCESS;
    }
} 