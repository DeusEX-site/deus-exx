<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class SetupCapSystem extends Command
{
    protected $signature = 'setup:cap-system {--test : Также запустить тесты}';
    protected $description = 'Полная настройка новой системы отдельных записей кап';

    public function handle()
    {
        $this->info('🚀 Настройка новой системы отдельных записей кап...');
        
        // Шаг 1: Создание таблицы
        $this->info('📋 Шаг 1: Создание таблицы caps...');
        $exitCode = Artisan::call('migrate:caps');
        if ($exitCode === 0) {
            $this->info('✅ Таблица caps создана успешно!');
        } else {
            $this->error('❌ Ошибка при создании таблицы caps');
            return Command::FAILURE;
        }
        
        // Шаг 2: Анализ существующих сообщений
        $this->info('🔍 Шаг 2: Анализ существующих сообщений...');
        $exitCode = Artisan::call('analyze:existing-messages', ['--limit' => 1000]);
        if ($exitCode === 0) {
            $this->info('✅ Анализ существующих сообщений завершен!');
        } else {
            $this->error('❌ Ошибка при анализе существующих сообщений');
            return Command::FAILURE;
        }
        
        // Шаг 3: Тестирование (если запрошено)
        if ($this->option('test')) {
            $this->info('🧪 Шаг 3: Запуск тестов...');
            $exitCode = Artisan::call('test:new-cap-system');
            if ($exitCode === 0) {
                $this->info('✅ Тесты прошли успешно!');
            } else {
                $this->error('❌ Ошибка при выполнении тестов');
                return Command::FAILURE;
            }
        }
        
        $this->info('');
        $this->info('🎉 Настройка завершена успешно!');
        $this->info('');
        $this->info('📊 Теперь система кап работает следующим образом:');
        $this->info('   • Каждая капа создает отдельную запись');
        $this->info('   • Новые сообщения анализируются автоматически');
        $this->info('   • Поиск работает быстро через базу данных');
        $this->info('   • Каждая капа отображается отдельно');
        $this->info('');
        $this->info('🌐 Откройте /cap-analysis в браузере для просмотра результатов');
        
        return Command::SUCCESS;
    }
} 