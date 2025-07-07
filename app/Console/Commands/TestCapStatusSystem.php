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
        
        $this->info('📋 Этап 6: Простая команда RESTORE (восстановление из корзины)...');
        
        // Восстанавливаем удаленную капу простой командой в ответ на сообщение
        $simpleRestoreMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1006,
            'reply_to_message_id' => $resumeMessage->id, // Отвечаем на сообщение с капой
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "RESTORE"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleRestoreMessage->id, $simpleRestoreMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа восстановлена из корзины: {$result['message']}");
        } else {
            $this->error("❌ Ошибка восстановления капы из корзины");
            return;
        }
        
        // Проверяем статус (должен быть RUN после восстановления)
        $activeCap->refresh();
        if ($activeCap->status === 'RUN') {
            $this->info("✅ Статус капы изменен на RUN после RESTORE");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен после RESTORE");
            return;
        }
        
        $this->info('📋 Этап 7: Простая команда RUN (перезапуск активной капы)...');
        
        // Сначала остановим капу
        $stopMessage2 = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1007,
            'reply_to_message_id' => $resumeMessage->id,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "STOP"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($stopMessage2->id, $stopMessage2->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа остановлена для тестирования RUN");
        } else {
            $this->error("❌ Ошибка остановки капы для тестирования RUN");
            return;
        }
        
        // Теперь запускаем капу командой RUN
        $simpleRunMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1008,
            'reply_to_message_id' => $resumeMessage->id,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "RUN"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleRunMessage->id, $simpleRunMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа запущена командой RUN: {$result['message']}");
        } else {
            $this->error("❌ Ошибка запуска капы командой RUN");
            return;
        }
        
        // Проверяем статус
        $activeCap->refresh();
        if ($activeCap->status === 'RUN') {
            $this->info("✅ Статус капы изменен на RUN после команды RUN");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен после команды RUN");
            return;
        }
        
        $this->info('📋 Этап 8: Проверка фильтрации по статусу...');
        
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
        
        $this->info('📋 Этап 9: Проверка истории изменений...');
        
                // Проверяем историю активной капы (должно быть много записей: STOP + DELETE + RESTORE + STOP + RUN)
        $historyCount = $activeCap->history()->count();

        if ($historyCount >= 4) {
            $this->info("✅ Создано {$historyCount} записей в истории (STOP + DELETE + RESTORE + STOP + RUN)");
        } else {
            $this->error("❌ Ошибка: неверное количество записей в истории ({$historyCount})");
            return;
        }
        
        $this->info('📋 Этап 10: Тестирование обновления через reply_to_message...');
        
        // Создаем новую капу для тестирования обновления
        $newCapMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1010,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Affiliate: TestAffiliate\nRecipient: TestRecipient\nCap: 30\nGeo: DE\nSchedule: 24/7"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($newCapMessage->id, $newCapMessage->message);
        
        if ($result['cap_entries_count'] === 1) {
            $this->info("✅ Тестовая капа создана для обновления");
        } else {
            $this->error("❌ Ошибка создания тестовой капы");
            return;
        }
        
        // Обновляем капу через reply_to_message (указываем только Geo)
        $updateMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1011,
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на сообщение с капой
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Cap: 35\nGeo: DE" // Только Geo обязательно, Cap обновляем
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage->id, $updateMessage->message);
        
        if (isset($result['updated_entries_count']) && $result['updated_entries_count'] === 1) {
            $this->info("✅ Капа обновлена через reply_to_message");
        } else {
            $this->error("❌ Ошибка обновления капы через reply_to_message");
            return;
        }
        
        // Проверяем что лимит изменился
        $updatedCap = Cap::where('affiliate_name', 'TestAffiliate')
                         ->where('recipient_name', 'TestRecipient')
                         ->whereJsonContains('geos', 'DE')
                         ->where('status', 'RUN')
                         ->first();
        
        if ($updatedCap && $updatedCap->cap_amounts[0] === 35) {
            $this->info("✅ Лимит капы обновлен с 30 до 35");
        } else {
            $this->error("❌ Ошибка: лимит капы не обновился");
            return;
        }
        
        // Тестируем ошибку - обновление с неправильным гео
        $wrongGeoMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1012,
            'reply_to_message_id' => $newCapMessage->id,
            'user_id' => 1,
            'display_name' => 'Test User',
            'message' => "Cap: 40\nGeo: FR" // Неправильное гео
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($wrongGeoMessage->id, $wrongGeoMessage->message);
        
        if ($result['cap_entries_count'] === 1) {
            $this->info("✅ При неправильном гео создалась новая капа (как и должно быть)");
        } else {
            $this->error("❌ Ошибка: при неправильном гео должна создаться новая капа");
            return;
        }
        
        $this->info('📋 Этап 11: Тестирование ошибок...');
        
        // Тестируем ошибку - попытка изменить статус несуществующей капы
        $errorMessage = Message::create([
            'chat_id' => $chat->id,
            'message_id' => 1013,
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
            'message_id' => 1014,
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
        $allCaps = Cap::whereIn('affiliate_name', ['G06', 'TestAffiliate'])
                     ->get();
        
        $this->table([
            'ID', 'Affiliate', 'Recipient', 'Geo', 'Cap', 'Статус', 'Записей в истории'
        ], $allCaps->map(function($cap) {
            return [
                $cap->id,
                $cap->affiliate_name,
                $cap->recipient_name,
                implode(', ', $cap->geos),
                $cap->cap_amounts[0],
                $cap->status,
                $cap->history()->count()
            ];
        })->toArray());
        
        $this->info('📝 Функциональность готова к использованию!');
        $this->info('');
        $this->info('✨ Новые возможности:');
        $this->info('1. Команды RUN/STOP/DELETE/RESTORE полными данными или через reply_to_message');
        $this->info('2. Обновление кап через reply_to_message (требуется только Geo)');
        $this->info('3. Статусы кап: RUN (активные), STOP (остановленные), DELETE (удаленные)');
        $this->info('4. RESTORE - восстановление из корзины (DELETE → RUN)');
        $this->info('5. История изменений для всех операций');
        
        return 0;
    }
} 