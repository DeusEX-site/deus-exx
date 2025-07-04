<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Message;
use App\Services\CapAnalysisService;

class UpdateCapHighlighting extends Command
{
    protected $signature = 'update:cap-highlighting';
    protected $description = 'Update cap highlighting system with new highlighted_text field';

    public function handle()
    {
        $this->info('🔄 Обновление системы подсветки кап...');
        
        // Запускаем миграцию
        $this->info('📋 Запуск миграции...');
        Artisan::call('migrate', ['--force' => true]);
        $this->info('✅ Миграция выполнена');
        
        // Перезапускаем анализ для всех сообщений
        $this->info('🔍 Перезапуск анализа всех сообщений...');
        
        $capAnalysisService = new CapAnalysisService();
        $messages = Message::all();
        
        $analyzed = 0;
        foreach ($messages as $message) {
            // Проверяем, есть ли cap слова в сообщении
            $capWords = ['cap', 'сар', 'сар', 'кап', 'CAP', 'САР', 'САР', 'КАП'];
            $hasCapWord = false;
            
            foreach ($capWords as $word) {
                if (stripos($message->message, $word) !== false) {
                    $hasCapWord = true;
                    break;
                }
            }
            
            if ($hasCapWord) {
                try {
                    $capAnalysisService->analyzeAndSaveCapMessage($message->id, $message->message);
                    $analyzed++;
                } catch (\Exception $e) {
                    $this->error("Ошибка анализа сообщения {$message->id}: " . $e->getMessage());
                }
            }
        }
        
        $this->info("✅ Анализ завершен. Обработано сообщений: {$analyzed}");
        $this->info('🎉 Система подсветки кап обновлена!');
        
        return 0;
    }
} 