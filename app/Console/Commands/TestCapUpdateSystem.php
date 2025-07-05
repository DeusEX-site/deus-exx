<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Services\CapAnalysisService;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class TestCapUpdateSystem extends Command
{
    protected $signature = 'test:cap-update-system';
    protected $description = 'Тестирует новую систему обновления капы по совпадению аффилейт-брокер-гео';

    public function handle()
    {
        $this->info('🚀 Тестирование системы обновления капы...');
        
        // Проверяем наличие таблицы cap_history
        if (!Schema::hasTable('cap_history')) {
            $this->info('📋 Создание таблицы cap_history...');
            
            try {
                DB::statement('
                    CREATE TABLE cap_history (
                        id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
                        cap_id BIGINT UNSIGNED NOT NULL,
                        source_message_id BIGINT UNSIGNED NOT NULL,
                        target_message_id BIGINT UNSIGNED NOT NULL,
                        match_key VARCHAR(255) NOT NULL,
                        old_values JSON NULL,
                        new_values JSON NULL,
                        changed_fields JSON NULL,
                        action VARCHAR(50) NOT NULL DEFAULT "updated",
                        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
                        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                        INDEX idx_cap_id (cap_id),
                        INDEX idx_source_message_id (source_message_id),
                        INDEX idx_target_message_id (target_message_id),
                        INDEX idx_match_key (match_key),
                        INDEX idx_cap_id_created_at (cap_id, created_at),
                        FOREIGN KEY (cap_id) REFERENCES caps(id) ON DELETE CASCADE,
                        FOREIGN KEY (source_message_id) REFERENCES messages(id) ON DELETE CASCADE,
                        FOREIGN KEY (target_message_id) REFERENCES messages(id) ON DELETE CASCADE
                    )
                ');
                
                $this->info('✅ Таблица cap_history создана успешно!');
            } catch (\Exception $e) {
                $this->error('❌ Ошибка создания таблицы: ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $this->info('✅ Таблица cap_history уже существует');
        }
        
        // Создаем тестовый чат
        $chat = Chat::firstOrCreate([
            'chat_id' => -888888,
            'type' => 'supergroup',
            'title' => 'Тестовый чат для обновления кап'
        ]);
        
        $this->info('📝 Создание тестовых сообщений...');
        
        // Создаем первое сообщение с капой
        $message1 = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 30 TestAff - TestBroker : RU/KZ' . PHP_EOL . '10-19' . PHP_EOL . '15.12',
            'user' => 'TestUser1',
            'telegram_message_id' => 2001,
            'telegram_user_id' => 2001,
            'created_at' => now()
        ]);
        
        $this->info("✅ Создано первое сообщение: {$message1->id}");
        
        // Анализируем первое сообщение
        $capAnalysisService = new CapAnalysisService();
        $result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $message1->message);
        
        $this->info("📊 Анализ первого сообщения: {$result1['cap_entries_count']} кап найдено");
        
        // Получаем созданную капу
        $cap1 = Cap::where('message_id', $message1->id)->first();
        
        if (!$cap1) {
            $this->error('❌ Не удалось найти созданную капу');
            return Command::FAILURE;
        }
        
        $this->info("✅ Первая капа создана: ID {$cap1->id}");
        $this->info("   - Аффилейт: {$cap1->affiliate_name}");
        $this->info("   - Брокер: {$cap1->broker_name}");
        $this->info("   - Гео: " . implode(', ', $cap1->geos ?? []));
        $this->info("   - Капа: " . implode(', ', $cap1->cap_amounts ?? []));
        $this->info("   - Расписание: {$cap1->schedule}");
        $this->info("   - Дата: {$cap1->date}");
        
        // Создаем второе сообщение с тем же аффилейт-брокер-гео, но другими значениями
        $message2 = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 50 TestAff - TestBroker : RU/KZ' . PHP_EOL . '24/7' . PHP_EOL . '20.12',
            'user' => 'TestUser2',
            'telegram_message_id' => 2002,
            'telegram_user_id' => 2002,
            'created_at' => now()->addMinutes(5)
        ]);
        
        $this->info("✅ Создано второе сообщение: {$message2->id}");
        
        // Анализируем второе сообщение (должно обновить первую капу)
        $result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $message2->message);
        
        $this->info("📊 Анализ второго сообщения:");
        $this->info("   - Создано кап: {$result2['created_caps']}");
        $this->info("   - Обновлено кап: {$result2['updated_caps']}");
        
        // Проверяем обновление первой капы
        $cap1->refresh();
        
        $this->info("🔄 Проверка обновления первой капы:");
        $this->info("   - Новая капа: " . implode(', ', $cap1->cap_amounts ?? []));
        $this->info("   - Новое расписание: {$cap1->schedule}");
        $this->info("   - Новая дата: {$cap1->date}");
        
        // Проверяем историю изменений
        $history = CapHistory::where('cap_id', $cap1->id)->get();
        
        $this->info("📜 История изменений:");
        if ($history->count() > 0) {
            foreach ($history as $entry) {
                $this->info("   - {$entry->action} в {$entry->created_at}");
                $this->info("     Изменены поля: " . implode(', ', $entry->changed_fields ?? []));
            }
        } else {
            $this->info("   - История пуста");
        }
        
        // Создаем третье сообщение с другим аффилейт-брокер-гео
        $message3 = Message::create([
            'chat_id' => $chat->id,
            'message' => 'CAP 25 OtherAff - OtherBroker : US/CA' . PHP_EOL . '08-20',
            'user' => 'TestUser3',
            'telegram_message_id' => 2003,
            'telegram_user_id' => 2003,
            'created_at' => now()->addMinutes(10)
        ]);
        
        $this->info("✅ Создано третье сообщение: {$message3->id}");
        
        // Анализируем третье сообщение (должно создать новую капу, не обновляя старую)
        $result3 = $capAnalysisService->analyzeAndSaveCapMessage($message3->id, $message3->message);
        
        $this->info("📊 Анализ третьего сообщения:");
        $this->info("   - Создано кап: {$result3['created_caps']}");
        $this->info("   - Обновлено кап: {$result3['updated_caps']}");
        
        // Проверяем общее количество кап
        $totalCaps = Cap::count();
        $this->info("📋 Общее количество кап в системе: {$totalCaps}");
        
        // Проверяем историю изменений для всех кап
        $totalHistory = CapHistory::count();
        $this->info("📜 Общее количество записей в истории: {$totalHistory}");
        
        $this->info('');
        $this->info('🎉 Тест завершен успешно!');
        $this->info('');
        $this->info('Сводка:');
        $this->info("- Создано сообщений: 3");
        $this->info("- Создано кап: {$totalCaps}");
        $this->info("- Записей в истории: {$totalHistory}");
        $this->info("- Обновлений по совпадению: {$result2['updated_caps']}");
        
        return Command::SUCCESS;
    }
} 