<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class MigrateCapHistory extends Command
{
    protected $signature = 'migrate:cap-history';
    protected $description = 'Создает таблицу cap_history для хранения истории изменений кап';

    public function handle()
    {
        $this->info('Создание таблицы cap_history...');
        
        try {
            if (Schema::hasTable('cap_history')) {
                $this->info('Таблица cap_history уже существует');
                return Command::SUCCESS;
            }
            
            DB::statement('
                CREATE TABLE cap_history (
                    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                    cap_id BIGINT UNSIGNED NOT NULL,
                    message_id BIGINT UNSIGNED NOT NULL,
                    action_type VARCHAR(255) NOT NULL,
                    old_values JSON NULL,
                    cap_amounts JSON NULL,
                    total_amount INT NULL,
                    schedule VARCHAR(255) NULL,
                    date VARCHAR(255) NULL,
                    is_24_7 BOOLEAN NOT NULL DEFAULT FALSE,
                    affiliate_name VARCHAR(255) NULL,
                    broker_name VARCHAR(255) NULL,
                    geos JSON NULL,
                    work_hours VARCHAR(255) NULL,
                    highlighted_text TEXT NULL,
                    reason TEXT NULL,
                    updated_by VARCHAR(255) NULL,
                    is_hidden BOOLEAN NOT NULL DEFAULT TRUE,
                    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_cap_id (cap_id),
                    INDEX idx_message_id (message_id),
                    INDEX idx_action_type (action_type),
                    INDEX idx_is_hidden (is_hidden),
                    INDEX idx_affiliate_broker (affiliate_name, broker_name),
                    INDEX idx_created_at (created_at),
                    FOREIGN KEY (cap_id) REFERENCES caps(id) ON DELETE CASCADE,
                    FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE
                )
            ');
            
            $this->info('Таблица cap_history успешно создана!');
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Ошибка создания таблицы: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
} 