<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CreateTestChats extends Command
{
    protected $signature = 'test:create-chats {count=100 : Количество чатов для создания}';
    protected $description = 'Создает тестовые чаты в базе данных';

    public function handle()
    {
        $count = (int) $this->argument('count');
        
        $this->info("Создание {$count} тестовых чатов...");
        
        // Очищаем существующие данные с учетом foreign key constraints
        $this->warn('Очистка существующих данных...');
        
        // Сначала очищаем зависимые таблицы
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('caps_history')->truncate();
        DB::table('caps')->truncate();
        DB::table('messages')->truncate();
        DB::table('chats')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('Создание новых чатов...');
        
        $chats = [];
        $now = Carbon::now();
        
        for ($i = 1; $i <= $count; $i++) {
            $chatTypes = ['private', 'group', 'supergroup', 'channel'];
            $type = $chatTypes[array_rand($chatTypes)];
            
            $chat = [
                'chat_id' => 1000 + $i, // Начинаем с 1001
                'type' => $type,
                'title' => $this->generateChatTitle($type, $i),
                'username' => $this->generateUsername($type, $i),
                'description' => $this->generateDescription($type, $i),
                'is_active' => true,
                'last_message_at' => $now->copy()->subMinutes(rand(0, 1440)), // Случайное время за последние 24 часа
                'message_count' => rand(0, 1000),
                'display_order' => $i <= 10 ? $i : 0, // Первые 10 чатов в топе
                'display_name' => null, // Будет генерироваться автоматически
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            $chats[] = $chat;
        }
        
        // Вставляем все чаты одним запросом
        Chat::insert($chats);
        
        $this->info("✅ Успешно создано {$count} тестовых чатов");
        
        // Показываем статистику
        $this->showStatistics();
    }

    private function generateChatTitle($type, $index)
    {
        switch ($type) {
            case 'private':
                return null; // У приватных чатов нет title
            case 'group':
                return "Группа #{$index}";
            case 'supergroup':
                return "Супергруппа #{$index}";
            case 'channel':
                return "Канал #{$index}";
            default:
                return "Чат #{$index}";
        }
    }

    private function generateUsername($type, $index)
    {
        switch ($type) {
            case 'private':
                return "user_{$index}";
            case 'group':
                return null; // У групп обычно нет username
            case 'supergroup':
                return rand(0, 1) ? "supergroup_{$index}" : null;
            case 'channel':
                return "channel_{$index}";
            default:
                return "user_{$index}";
        }
    }

    private function generateDescription($type, $index)
    {
        switch ($type) {
            case 'private':
                return "Приватный чат с пользователем #{$index}";
            case 'group':
                return "Тестовая группа #{$index} для проверки системы";
            case 'supergroup':
                return "Тестовая супергруппа #{$index} с расширенными возможностями";
            case 'channel':
                return "Тестовый канал #{$index} для публикации сообщений";
            default:
                return "Тестовый чат #{$index}";
        }
    }

    private function showStatistics()
    {
        $this->info("\n📊 Статистика созданных чатов:");
        
        $types = Chat::selectRaw('type, COUNT(*) as count')
                     ->groupBy('type')
                     ->pluck('count', 'type')
                     ->toArray();
        
        foreach ($types as $type => $count) {
            $this->line("  - {$type}: {$count}");
        }
        
        $topTenCount = Chat::where('display_order', '>', 0)->count();
        $this->line("  - В топ-10: {$topTenCount}");
        
        $this->info("\n🎯 Чаты готовы для тестирования!");
    }
} 