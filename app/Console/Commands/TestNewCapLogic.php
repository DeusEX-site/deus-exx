<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\CapAnalysisService;

class TestNewCapLogic extends Command
{
    protected $signature = 'test:new-cap-logic';
    protected $description = 'Test the new CAP parsing logic';

    public function handle()
    {
        $capService = new CapAnalysisService();
        
        // Тестовое сообщение похожее на то что показал пользователь
        $testMessage = "CAP 10
Aff - Rec RU KZ
CAP 30 Aff2 - Rec AU US 24/7
10-19
no 200 Aff3 - Rec AU US";
        
        $this->info("Тестовое сообщение:");
        $this->info($testMessage);
        $this->info("===================");
        
        // Получаем отдельные CAP записи
        $reflection = new \ReflectionClass($capService);
        $method = $reflection->getMethod('extractSeparateCapEntries');
        $method->setAccessible(true);
        
        $capEntries = $method->invoke($capService, $testMessage);
        
        $this->info("Найдено записей: " . count($capEntries));
        
        foreach ($capEntries as $index => $entry) {
            $this->info("Запись #" . ($index + 1) . ":");
            $this->info("  CAP: " . $entry['cap_amount']);
            $this->info("  Тотал: " . ($entry['total_amount'] == -1 ? '∞' : $entry['total_amount']));
            $this->info("  Аффилиат: " . ($entry['affiliate_name'] ?? 'null'));
            $this->info("  Брокер: " . ($entry['broker_name'] ?? 'null'));
            $this->info("  Гео: " . implode(', ', $entry['geos'] ?? []));
            $this->info("  Расписание: " . ($entry['schedule'] ?? 'null'));
            $this->info("  Дата: " . ($entry['date'] ?? 'null'));
            $this->info("  Текст: " . $entry['highlighted_text']);
            $this->info("---");
        }
    }
} 