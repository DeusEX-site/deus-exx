<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Services\CapAnalysisService;

class TestFullSystem extends Command
{
    protected $signature = 'test:full-system';
    protected $description = 'Полное тестирование системы с исправленной логикой обновления';

    public function handle()
    {
        $this->info('🧪 Полное тестирование системы cap analysis...');
        $this->info('Тестирует: создание, обновление через reply, управление статусом, пропуск дубликатов');
        $this->info('');

        // Создаем тестовый чат
        $chat = Chat::updateOrCreate(
            ['chat_id' => -1001000000001],
            [
                'type' => 'supergroup',
                'title' => 'Test Full System Chat',
                'is_active' => true,
            ]
        );

        // Очищаем старые данные
        Message::where('chat_id', $chat->id)->delete();
        Cap::whereIn('message_id', function($query) use ($chat) {
            $query->select('id')->from('messages')->where('chat_id', $chat->id);
        })->delete();

        $capAnalysisService = new CapAnalysisService();
        
        // Тест 1: Создание новой капы
        $this->info('📋 Тест 1: Создание новой капы');
        $message1 = Message::create([
            'chat_id' => $chat->id,
            'message' => "Affiliate: TestAff1\nRecipient: TestBroker1\nCap: 30\nGeo: RU\nSchedule: 24/7",
            'user' => 'TestUser',
            'telegram_message_id' => 1001,
            'telegram_user_id' => 123456,
            'created_at' => now(),
        ]);

        $result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $message1->message);
        
        if ($result1['cap_entries_count'] === 1) {
            $this->info('✅ Капа создана успешно');
        } else {
            $this->error('❌ Ошибка создания капы');
            return Command::FAILURE;
        }

        // Тест 2: Попытка создания дубликата (должен пропуститься)
        $this->info('📋 Тест 2: Попытка создания дубликата (должен пропуститься)');
        $message2 = Message::create([
            'chat_id' => $chat->id,
            'message' => "Affiliate: TestAff1\nRecipient: TestBroker1\nCap: 50\nGeo: RU\nSchedule: 10-18",
            'user' => 'TestUser',
            'telegram_message_id' => 1002,
            'telegram_user_id' => 123456,
            'created_at' => now(),
        ]);

        $result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $message2->message);
        
        if ($result2['cap_entries_count'] === 0 && ($result2['updated_entries_count'] ?? 0) === 0) {
            $this->info('✅ Дубликат правильно пропущен');
        } else {
            $this->error('❌ Дубликат не пропущен (создано: ' . $result2['cap_entries_count'] . ', обновлено: ' . ($result2['updated_entries_count'] ?? 0) . ')');
            return Command::FAILURE;
        }

        // Проверяем что в БД все еще одна капа с исходными данными
        $cap = Cap::where('affiliate_name', 'TestAff1')
                  ->where('recipient_name', 'TestBroker1')
                  ->where('geo', 'RU')
                  ->first();

        if ($cap && $cap->cap_amount === 30) {
            $this->info('✅ Данные капы не изменились (cap_amount = 30)');
        } else {
            $this->error('❌ Данные капы изменились неожиданно');
            return Command::FAILURE;
        }

        // Тест 3: Обновление капы через reply_to_message
        $this->info('📋 Тест 3: Обновление капы через reply_to_message');
        $message3 = Message::create([
            'chat_id' => $chat->id,
            'message' => "Cap: 75\nGeo: RU",
            'user' => 'TestUser',
            'telegram_message_id' => 1003,
            'telegram_user_id' => 123456,
            'reply_to_message_id' => $message1->id,
            'created_at' => now(),
        ]);

        $result3 = $capAnalysisService->analyzeAndSaveCapMessage($message3->id, $message3->message);
        
        if (($result3['updated_entries_count'] ?? 0) === 1) {
            $this->info('✅ Капа обновлена через reply');
        } else {
            $this->error('❌ Ошибка обновления через reply');
            return Command::FAILURE;
        }

        // Проверяем что данные обновились
        $cap->refresh();
        if ($cap->cap_amount === 75) {
            $this->info('✅ Cap amount обновлен до 75');
        } else {
            $this->error('❌ Cap amount не обновился');
            return Command::FAILURE;
        }

        // Тест 4: Создание капы с новым гео
        $this->info('📋 Тест 4: Создание капы с новым гео');
        $message4 = Message::create([
            'chat_id' => $chat->id,
            'message' => "Affiliate: TestAff1\nRecipient: TestBroker1\nCap: 25\nGeo: DE\nSchedule: 24/7",
            'user' => 'TestUser',
            'telegram_message_id' => 1004,
            'telegram_user_id' => 123456,
            'created_at' => now(),
        ]);

        $result4 = $capAnalysisService->analyzeAndSaveCapMessage($message4->id, $message4->message);
        
        if ($result4['cap_entries_count'] === 1) {
            $this->info('✅ Капа с новым гео создана');
        } else {
            $this->error('❌ Ошибка создания капы с новым гео');
            return Command::FAILURE;
        }

        // Проверяем что теперь в БД 2 капы
        $capsCount = Cap::where('affiliate_name', 'TestAff1')
                        ->where('recipient_name', 'TestBroker1')
                        ->count();

        if ($capsCount === 2) {
            $this->info('✅ В БД теперь 2 капы (RU и DE)');
        } else {
            $this->error('❌ Неверное количество кап в БД: ' . $capsCount);
            return Command::FAILURE;
        }

        // Тест 5: Команда STOP через reply
        $this->info('📋 Тест 5: Команда STOP через reply');
        $message5 = Message::create([
            'chat_id' => $chat->id,
            'message' => "STOP",
            'user' => 'TestUser',
            'telegram_message_id' => 1005,
            'telegram_user_id' => 123456,
            'reply_to_message_id' => $message1->id,
            'created_at' => now(),
        ]);

        $result5 = $capAnalysisService->analyzeAndSaveCapMessage($message5->id, $message5->message);
        
        if (($result5['updated_entries_count'] ?? 0) === 1) {
            $this->info('✅ Команда STOP выполнена');
        } else {
            $this->error('❌ Ошибка выполнения команды STOP');
            return Command::FAILURE;
        }

        // Проверяем статус
        $cap->refresh();
        if ($cap->status === 'STOP') {
            $this->info('✅ Статус изменен на STOP');
        } else {
            $this->error('❌ Статус не изменился');
            return Command::FAILURE;
        }

        // Тест 6: Команда RUN через reply
        $this->info('📋 Тест 6: Команда RUN через reply');
        $message6 = Message::create([
            'chat_id' => $chat->id,
            'message' => "RUN",
            'user' => 'TestUser',
            'telegram_message_id' => 1006,
            'telegram_user_id' => 123456,
            'reply_to_message_id' => $message1->id,
            'created_at' => now(),
        ]);

        $result6 = $capAnalysisService->analyzeAndSaveCapMessage($message6->id, $message6->message);
        
        if (($result6['updated_entries_count'] ?? 0) === 1) {
            $this->info('✅ Команда RUN выполнена');
        } else {
            $this->error('❌ Ошибка выполнения команды RUN');
            return Command::FAILURE;
        }

        // Проверяем статус
        $cap->refresh();
        if ($cap->status === 'RUN') {
            $this->info('✅ Статус изменен на RUN');
        } else {
            $this->error('❌ Статус не изменился');
            return Command::FAILURE;
        }

        // Тест 7: Мультикапа (несколько кап в одном сообщении)
        $this->info('📋 Тест 7: Мультикапа (несколько кап в одном сообщении)');
        $message7 = Message::create([
            'chat_id' => $chat->id,
            'message' => "Affiliate: MultiAff\nRecipient: MultiBroker\nCap: 20 30\nGeo: FR IT\nSchedule: 24/7",
            'user' => 'TestUser',
            'telegram_message_id' => 1007,
            'telegram_user_id' => 123456,
            'created_at' => now(),
        ]);

        $result7 = $capAnalysisService->analyzeAndSaveCapMessage($message7->id, $message7->message);
        
        if ($result7['cap_entries_count'] === 2) {
            $this->info('✅ Мультикапа создана (2 записи)');
        } else {
            $this->error('❌ Ошибка создания мультикапы (создано: ' . $result7['cap_entries_count'] . ')');
            return Command::FAILURE;
        }

        // Проверяем что записи созданы правильно
        $frCap = Cap::where('affiliate_name', 'MultiAff')->where('geo', 'FR')->first();
        $itCap = Cap::where('affiliate_name', 'MultiAff')->where('geo', 'IT')->first();

        if ($frCap && $frCap->cap_amount === 20 && $itCap && $itCap->cap_amount === 30) {
            $this->info('✅ Мультикапа создана корректно (FR: 20, IT: 30)');
        } else {
            $this->error('❌ Мультикапа создана некорректно');
            return Command::FAILURE;
        }

        // Финальная сводка
        $this->info('');
        $this->info('🎉 Все тесты пройдены успешно!');
        $this->info('');

        // Показываем итоговое состояние БД
        $allCaps = Cap::all();
        $this->info('📊 Итоговое состояние БД:');
        foreach ($allCaps as $cap) {
            $this->info("  - {$cap->affiliate_name} → {$cap->recipient_name} ({$cap->geo}, {$cap->cap_amount}) [{$cap->status}]");
        }
        $this->info("  Всего кап: {$allCaps->count()}");

        return Command::SUCCESS;
    }
} 