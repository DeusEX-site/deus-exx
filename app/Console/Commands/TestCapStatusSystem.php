<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Services\CapAnalysisService;

class TestCapStatusSystem extends Command
{
    protected $signature = 'test:cap-status-system';
    protected $description = 'Тестирует систему управления статусом кап (RUN/STOP/DELETE)';

    public function handle()
    {
        $this->info('🧪 Тестирование системы управления статусом кап...');
        
        $capAnalysisService = new CapAnalysisService();
        
        // Создаем тестовый чат
        $chat = Chat::firstOrCreate([
            'chat_id' => -1001234567890,
            'display_name' => 'Test Chat (Status System)',
            'display_order' => 1
        ]);
        
        // Очищаем предыдущие тестовые данные
        Cap::where('affiliate_name', 'G06')->delete();
        
        $this->info('📋 Этап 1: Создание новой капы...');
        
        // Создаем капу
        $createMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1001,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 20\nGeo: AT\nSchedule: 24/7"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($createMessage->id, $createMessage->message);
        
        $this->info("✅ Создана капа: {$result['cap_entries_count']} записей");
        
        // Проверяем что капа создана со статусом RUN
        $cap = Cap::where('affiliate_name', 'G06')
                 ->where('recipient_name', 'TMedia')
                 ->whereJsonContains('geos', 'AT')
                 ->first();
        
        if ($cap && $cap->status === 'RUN') {
            $this->info("✅ Капа создана со статусом RUN (по умолчанию)");
        } else {
            $this->error("❌ Ошибка: капа не создана или неверный статус");
            return;
        }
        
        $this->info('📋 Этап 2: Остановка капы (полная команда)...');
        
        // Останавливаем капу полной командой
        $stopMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1002,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 20\nGeo: AT\nSTOP"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($stopMessage->id, $stopMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа остановлена: {$result['message']}");
        } else {
            $this->error("❌ Ошибка остановки капы");
            return;
        }
        
        // Проверяем статус
        $cap->refresh();
        if ($cap->status === 'STOP') {
            $this->info("✅ Статус капы изменен на STOP");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен");
            return;
        }
        
        $this->info('📋 Этап 3: Возобновление капы (создание новой)...');
        
        // Создаем новую капу (так как остановленная исключается из дубликатов)
        $resumeMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1003,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 25\nGeo: AT\nSchedule: 24/7"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($resumeMessage->id, $resumeMessage->message);
        
        if ($result['cap_entries_count'] === 1) {
            $this->info("✅ Новая капа создана (старая была остановлена)");
        } else {
            $this->error("❌ Ошибка создания новой капы");
            return;
        }
        
        // Получаем новую активную капу
        $activeCap = Cap::where('affiliate_name', 'G06')
                       ->where('recipient_name', 'TMedia')
                       ->whereJsonContains('geos', 'AT')
                       ->where('status', 'RUN')
                       ->first();
        
        if (!$activeCap) {
            $this->error("❌ Ошибка: активная капа не найдена");
            return;
        }
        
        $this->info('📋 Этап 4: Простая команда STOP (через reply_to_message)...');
        
        // Останавливаем капу простой командой в ответ на сообщение
        $simpleStopMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1004,
            'reply_to_message_id' => $resumeMessage->id, // Отвечаем на сообщение с капой
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "STOP"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleStopMessage->id, $simpleStopMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа остановлена простой командой: {$result['message']}");
        } else {
            $this->error("❌ Ошибка остановки капы простой командой");
            return;
        }
        
        // Проверяем статус
        $activeCap->refresh();
        if ($activeCap->status === 'STOP') {
            $this->info("✅ Статус капы изменен на STOP через reply_to_message");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен");
            return;
        }
        
        $this->info('📋 Этап 5: Простая команда DELETE (через reply_to_message)...');
        
        // Удаляем капу простой командой в ответ на сообщение
        $simpleDeleteMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1005,
            'reply_to_message_id' => $resumeMessage->id, // Отвечаем на сообщение с капой
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "DELETE"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleDeleteMessage->id, $simpleDeleteMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа удалена простой командой: {$result['message']}");
        } else {
            $this->error("❌ Ошибка удаления капы простой командой");
            return;
        }
        
        // Проверяем статус
        $activeCap->refresh();
        if ($activeCap->status === 'DELETE') {
            $this->info("✅ Статус капы изменен на DELETE через reply_to_message");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен");
            return;
        }
        
        $this->info('📋 Этап 6: Проверка фильтрации по статусу...');
        
        // Проверяем что удаленная капа не показывается в поиске
        $activeCaps = $capAnalysisService->searchCaps(null, $chat->id);
        
        if (count($activeCaps) === 0) {
            $this->info("✅ Удаленная капа не отображается в поиске активных кап");
        } else {
            $this->error("❌ Ошибка: удаленная капа отображается в поиске");
            return;
        }
        
        // Проверяем поиск с фильтром по статусу DELETE
        $deletedCaps = $capAnalysisService->searchCapsWithFilters(null, $chat->id, ['status' => 'DELETE']);
        
        if (count($deletedCaps) === 1) {
            $this->info("✅ Удаленная капа найдена при поиске с фильтром DELETE");
        } else {
            $this->error("❌ Ошибка: удаленная капа не найдена при поиске с фильтром DELETE");
            return;
        }
        
        $this->info('📋 Этап 7: Проверка истории изменений...');
        
        // Проверяем историю активной капы (которая была остановлена и удалена)
        $historyCount = $activeCap->history()->count();
        
        if ($historyCount === 2) {
            $this->info("✅ Создано {$historyCount} записей в истории (STOP + DELETE)");
        } else {
            $this->error("❌ Ошибка: неверное количество записей в истории ({$historyCount})");
            return;
        }
        
        $this->info('📋 Этап 8: Тестирование ошибок...');
        
        // Тестируем ошибку - попытка изменить статус несуществующей капы
        $errorMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1006,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Affiliate: NonExistent\nRecipient: NonExistent\nCap: 999\nGeo: XX\nSTOP"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($errorMessage->id, $errorMessage->message);
        
        if (isset($result['error']) && $result['error'] === 'Капа не найдена') {
            $this->info("✅ Корректная обработка ошибки для несуществующей капы");
        } else {
            $this->error("❌ Ошибка: неверная обработка ошибки для несуществующей капы");
            return;
        }
        
        // Тестируем ошибку - простая команда без reply_to_message
        $noReplyMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1007,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "STOP"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($noReplyMessage->id, $noReplyMessage->message);
        
        if (isset($result['error']) && $result['error'] === 'Команда должна быть ответом на сообщение с капой') {
            $this->info("✅ Корректная обработка ошибки для команды без reply_to_message");
        } else {
            $this->error("❌ Ошибка: неверная обработка ошибки для команды без reply_to_message");
            return;
        }
        
        $this->info('🎉 Все тесты пройдены успешно!');
        
        // Выводим сводку по всем созданным капам
        $allCaps = Cap::where('affiliate_name', 'G06')
                     ->where('recipient_name', 'TMedia')
                     ->whereJsonContains('geos', 'AT')
                     ->get();
        
        $this->table([
            'ID', 'Cap', 'Статус', 'Дата статуса', 'Записей в истории'
        ], $allCaps->map(function($cap) {
            return [
                $cap->id,
                $cap->cap_amounts[0],
                $cap->status,
                $cap->status_updated_at?->format('d.m.Y H:i:s'),
                $cap->history()->count()
            ];
        })->toArray());
        
        $this->info('📝 Функциональность готова к использованию!');
        
        return 0;
    }
} 