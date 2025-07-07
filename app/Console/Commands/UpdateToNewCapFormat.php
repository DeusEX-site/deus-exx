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
        $this->line('• Если Total пустое или Cap содержит слеш -> Total = бесконечность');
        $this->line('• Если Date пустое -> бесконечность');
        $this->line('• Если Schedule пустое -> 24/7');
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
        $this->line('• Автоматические значения по умолчанию для пустых полей');
        
        return Command::SUCCESS;
    }

    private function testNewFormat()
    {
        $this->line('Тестирование нового формата...');
        
        // Создаем тестовое сообщение в новом формате
        $testMessage = "Affiliate: G06
Recipient: TMedia
Cap: 15
Total: 
Geo: IE
Language: en
Funnel: 
Schedule: 10:00/18:00 GMT+03:00
Date: 
Pending ACQ: No
Freeze status on ACQ: No";

        $this->line('');
        $this->line('Тестовое сообщение:');
        $this->line($testMessage);
        $this->line('');

        $capAnalysisService = new \App\Services\CapAnalysisService();
        
        // Тестируем парсинг
        $analysis = $capAnalysisService->analyzeCapMessage($testMessage);
        
        $this->line('Результат парсинга:');
        $this->line("• Является стандартной капой: " . ($analysis['has_cap_word'] ? 'Да' : 'Нет'));
        $this->line("• Affiliate: " . ($analysis['affiliate_name'] ?: 'Не найдено'));
        $this->line("• Recipient: " . ($analysis['recipient_name'] ?: 'Не найдено'));
        $this->line("• Cap: " . ($analysis['cap_amount'] ?: 'Не найдено'));
        $this->line("• Total: " . ($analysis['total_amount'] === -1 ? 'Бесконечность' : ($analysis['total_amount'] ?: 'Не найдено')));
        $this->line("• Geo: " . (count($analysis['geos']) > 0 ? implode(', ', $analysis['geos']) : 'Не найдено'));
        $this->line("• Language: " . ($analysis['language'] ?: 'Не найдено'));
        $this->line("• Funnel: " . ($analysis['funnel'] ?: 'Не найдено'));
        $this->line("• Schedule: " . ($analysis['schedule'] ?: 'Не найдено'));
        $this->line("• Date: " . ($analysis['date'] ?: 'Бесконечность'));
        $this->line("• Pending ACQ: " . ($analysis['pending_acq'] ? 'Да' : 'Нет'));
        $this->line("• Freeze status: " . ($analysis['freeze_status_on_acq'] ? 'Да' : 'Нет'));
        
        if ($analysis['has_cap_word']) {
            $this->info('✅ Тест прошел успешно!');
        } else {
            $this->error('❌ Тест не прошел - сообщение не распознано как стандартная капа');
        }
    }
} 