<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateCaps extends Command
{
    protected $signature = 'migrate:caps';
    protected $description = 'Создает таблицу caps для хранения анализа кап';

    public function handle()
    {
        $this->info('Создание таблицы caps...');
        
        try {
            if (Schema::hasTable('caps')) {
                $this->info('Таблица caps уже существует');
                return Command::SUCCESS;
            }
            
            DB::statement('
                CREATE TABLE caps (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    message_id BIGINT UNSIGNED NOT NULL,
                    cap_amounts JSON NULL,
                    total_amount INT NULL,
                    schedule VARCHAR(255) NULL,
                    date VARCHAR(255) NULL,
                    is_24_7 BOOLEAN NOT NULL DEFAULT FALSE,
                    affiliate_name VARCHAR(255) NULL,
                    broker_name VARCHAR(255) NULL,
                    geos JSON NULL,
                    work_hours VARCHAR(255) NULL,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_message_id (message_id),
                    INDEX idx_is_24_7 (is_24_7),
                    INDEX idx_affiliate_name (affiliate_name),
                    INDEX idx_broker_name (broker_name),
                    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
                )
            ');
            
            $this->info('Таблица caps успешно создана!');
            
        } catch (\Exception $e) {
            $this->error('Ошибка при создании таблицы caps: ' . $e->getMessage());
            return Command::FAILURE;
        }
        
        return Command::SUCCESS;
    }
} 