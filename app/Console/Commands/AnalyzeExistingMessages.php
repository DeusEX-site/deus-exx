<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Services\CapAnalysisService;

class AnalyzeExistingMessages extends Command
{
    protected $signature = 'analyze:existing-messages {--limit=1000 : Количество сообщений для анализа}';
    protected $description = 'Анализирует существующие сообщения на наличие кап и сохраняет в таблицу caps';

    public function handle()
    {
        $limit = $this->option('limit');
        
        $this->info("Начинаю анализ существующих сообщений (лимит: {$limit})...");
        
        $capAnalysisService = new CapAnalysisService();
        
        // Поиск сообщений со словами cap, сар, сар (на разных языках)
        $capPatterns = [
            'cap', 'сар', 'сар', 'CAP', 'САР', 'САР',
            'кап', 'КАП', 'каП', 'Кап'
        ];
        
        $query = Message::query();
        
        $query->where(function($q) use ($capPatterns) {
            foreach ($capPatterns as $pattern) {
                $q->orWhere('message', 'LIKE', "%{$pattern}%");
            }
        });
        
        $messages = $query->orderBy('created_at', 'desc')
                         ->limit($limit)
                         ->get();
        
        $this->info("Найдено {$messages->count()} сообщений со словами cap для анализа");
        
        $processed = 0;
        $capsFound = 0;
        
        $progressBar = $this->output->createProgressBar($messages->count());
        $progressBar->start();
        
        foreach ($messages as $message) {
            try {
                $analysis = $capAnalysisService->analyzeAndSaveCapMessage($message->id, $message->message);
                
                if (!empty($analysis['cap_amounts'])) {
                    $capsFound++;
                }
                
                $processed++;
                $progressBar->advance();
                
            } catch (\Exception $e) {
                $this->error("\nОшибка при анализе сообщения {$message->id}: " . $e->getMessage());
            }
        }
        
        $progressBar->finish();
        
        $this->info("\n");
        $this->info("Анализ завершен!");
        $this->info("Обработано сообщений: {$processed}");
        $this->info("Найдено кап: {$capsFound}");
        
        return Command::SUCCESS;
    }
} 