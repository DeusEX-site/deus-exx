<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use App\Models\Cap;

class UpdateToNewCapFormat extends Command
{
    protected $signature = 'update:new-cap-format {--test : Также запустить тесты}';
    protected $description = 'Обновляет систему кап до нового стандартного формата';

    public function handle()
    {
        $this->info('🚀 Обновление системы кап до нового стандартного формата...');
        $this->line('');
        
        // Шаг 1: Запуск миграции
        $this->info('📋 Шаг 1: Применение миграции новых полей...');
        try {
            Artisan::call('migrate', ['--force' => true]);
            $this->info('✅ Миграция выполнена успешно!');
        } catch (\Exception $e) {
            $this->error('❌ Ошибка при выполнении миграции: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        // Шаг 2: Очистка старых записей кап
        $this->info('🧹 Шаг 2: Очистка старых записей кап...');
        $oldCapsCount = Cap::count();
        Cap::truncate();
        $this->info("✅ Удалено {$oldCapsCount} старых записей кап");
        
        // Шаг 3: Информация о новом формате
        $this->info('📄 Шаг 3: Новый формат сообщений');
        $this->line('');
        $this->line('Теперь система обрабатывает только сообщения в стандартном формате:');
        $this->line('');
        $this->line('Affiliate: G06');
        $this->line('Recipient: TMedia');
        $this->line('Cap: 15');
        $this->line('Total: ');
        $this->line('Geo: IE');
        $this->line('Language: en');
        $this->line('Funnel: ');
        $this->line('Schedule: 10:00/18:00 GMT+03:00');
        $this->line('Date: ');
        $this->line('Pending ACQ: No');
        $this->line('Freeze status on ACQ: No');
        $this->line('');
        
        $this->info('📝 Правила обработки:');
        $this->line('• Ищем сообщения с полями Affiliate, Recipient, Cap');
        $this->line('• Affiliate и Recipient - по одному значению (без запятых)');
        $this->line('• Geo разделяется на отдельные капы (GT PE MX = 3 капы)');
        $this->line('• Если Total пустое или Cap содержит слеш -> Total = бесконечность');
        $this->line('• Если Date пустое -> бесконечность');
        $this->line('• Если Schedule пустое -> 24/7');
        $this->line('• Date без года -> добавляется текущий год (24.02 -> 24.02.2024)');
        $this->line('• Pending ACQ и Freeze status: Yes/No или пусто (No)');
        $this->line('');
        
        // Шаг 4: Тестирование (если запрошено)
        if ($this->option('test')) {
            $this->info('🧪 Шаг 4: Запуск тестов...');
            $this->testNewFormat();
        }
        
        $this->info('🎉 Обновление завершено успешно!');
        $this->line('');
        $this->info('📊 Теперь система работает следующим образом:');
        $this->line('• Обрабатывает только стандартные сообщения');
        $this->line('• Поддерживает новые поля: Language, Funnel, Pending ACQ, Freeze status');
        $this->line('• Recipient вместо Broker');
        $this->line('• Разделение по гео: каждое гео = отдельная запись');
        $this->line('• Автоматические значения по умолчанию для пустых полей');
        
        return Command::SUCCESS;
    }

    private function testNewFormat()
    {
        $this->line('Тестирование нового формата...');
        
        // Создаем тестовые сообщения в новом формате
        $testMessages = [
            // Тест 1: Одиночная капа
            "Affiliate: G06
Recipient: TMedia
Cap: 15
Total: 
Geo: IE
Language: en
Funnel: 
Schedule: 10:00/18:00 GMT+03:00
Date: 
Pending ACQ: No
Freeze status on ACQ: No",
            
            // Тест 2: Множественные гео (должны создаться 3 капы)
            "Affiliate: Webgate
Recipient: TradingM
Cap: 20
Total: 200
Geo: GT PE MX
Language: es
Funnel: Crypto
Schedule: 24/7
Date: 24.02
Pending ACQ: Yes
Freeze status on ACQ: No",
            
            // Тест 3: Одно гео (должна создаться 1 капа)
            "Affiliate: TestAffiliate
Recipient: TestBroker
Cap: 30
Total: 
Geo: RU
Language: ru
Funnel: 
Schedule: 
Date: 
Pending ACQ: 
Freeze status on ACQ: ",

            // Тест 4: Автоматическая подстановка года к дате
            "Affiliate: TestAff
Recipient: TestRec
Cap: 25
Total: 150
Geo: DE, FR
Language: en
Funnel: Crypto
Schedule: 10:00/18:00 GMT+03:00
Date: 24.02
Pending ACQ: No
Freeze status on ACQ: Yes"
        ];

        $capAnalysisService = new \App\Services\CapAnalysisService();
        
        foreach ($testMessages as $index => $testMessage) {
            $testNumber = $index + 1;
            $this->line('');
            $this->line("=== Тест {$testNumber} ===");
            $this->line($testMessage);
            $this->line('');

            // Тестируем анализ и сохранение
            $result = $capAnalysisService->analyzeAndSaveCapMessage(999 + $index, $testMessage);
            
            $this->line("Создано записей кап: " . $result['cap_entries_count']);
            
            if ($result['cap_entries_count'] > 0) {
                // Получаем созданные записи
                $caps = \App\Models\Cap::where('message_id', 999 + $index)->get();
                
                foreach ($caps as $capIndex => $cap) {
                    $this->line("  Запись " . ($capIndex + 1) . ":");
                    $this->line("  • Affiliate: " . ($cap->affiliate_name ?: 'Не найдено'));
                    $this->line("  • Recipient: " . ($cap->recipient_name ?: 'Не найдено'));
                    $this->line("  • Cap: " . ($cap->cap_amounts ? implode(', ', $cap->cap_amounts) : 'Не найдено'));
                    $this->line("  • Total: " . ($cap->total_amount === -1 ? 'Бесконечность' : ($cap->total_amount ?: 'Не найдено')));
                    $this->line("  • Geo: " . (count($cap->geos ?: []) > 0 ? implode(', ', $cap->geos) : 'Не найдено'));
                    $this->line("  • Language: " . ($cap->language ?: 'Пусто'));
                    $this->line("  • Funnel: " . ($cap->funnel ?: 'Пусто'));
                    $this->line("  • Schedule: " . ($cap->schedule ?: '24/7'));
                    $this->line("  • Date: " . ($cap->date ?: 'Бесконечность'));
                    $this->line("  • Pending ACQ: " . ($cap->pending_acq ? 'Да' : 'Нет'));
                    $this->line("  • Freeze status: " . ($cap->freeze_status_on_acq ? 'Да' : 'Нет'));
                    $this->line('');
                }
                
                $this->info("✅ Тест {$testNumber} прошел успешно!");
                
                // Очищаем тестовые данные
                \App\Models\Cap::where('message_id', 999 + $index)->delete();
            } else {
                $this->error("❌ Тест {$testNumber} не прошел - записи не созданы");
            }
        }
    }
} 