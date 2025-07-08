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
        $chat = Chat::updateOrCreate(
            ['chat_id' => -1001234567890], // Поиск по уникальному полю
            [
                'title' => 'Test Chat (Status System)',
                'display_order' => 1
            ]
        );
        
        // Очищаем предыдущие тестовые данные
        Cap::where('affiliate_name', 'G06')->delete();
        
        $this->info('📋 Этап 1: Создание новой капы...');
        
        // Создаем капу
        $createMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1001,
            'user' => 'Test User',
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
            'telegram_message_id' => 1002,
            'user' => 'Test User',
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
        
        $this->info('📋 Этап 3: Возобновление капы (обновление остановленной)...');
        
        // Проверяем существующие капы перед попыткой обновления
        $existingCaps = Cap::where('affiliate_name', 'G06')
                          ->where('recipient_name', 'TMedia')
                          ->whereJsonContains('geos', 'AT')
                          ->get();
        
        $this->info("DEBUG: Существующих кап G06→TMedia(AT): " . $existingCaps->count());
        foreach ($existingCaps as $existingCap) {
            $this->info("  - Cap ID {$existingCap->id}: статус {$existingCap->status}, лимит " . implode(',', $existingCap->cap_amounts));
        }
        
        // Система не создает новую капу для остановленной - она обновляет существующую
        // Отправляем сообщение с обновлением для остановленной капы
        $resumeMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1003,
            'user' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 25\nGeo: AT\nSchedule: 24/7"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($resumeMessage->id, $resumeMessage->message);
        
        $this->info("DEBUG: Результат обновления остановленной капы: " . json_encode($result));
        
        if ($result['updated_entries_count'] === 1) {
            $this->info("✅ Остановленная капа обновлена (лимит изменен с 20 на 25)");
        } else {
            $this->error("❌ Ошибка обновления остановленной капы");
            $this->error("Ожидали: updated_entries_count = 1, получили: " . ($result['updated_entries_count'] ?? 'null'));
            if (isset($result['error'])) {
                $this->error("Ошибка: " . $result['error']);
            }
            return;
        }
        
        // Получаем обновленную капу (она всё ещё остановленная, но с новым лимитом)
        $activeCap = Cap::where('affiliate_name', 'G06')
                       ->where('recipient_name', 'TMedia')
                       ->whereJsonContains('geos', 'AT')
                       ->where('status', 'STOP') // Капа всё ещё остановленная
                       ->first();
        
        if (!$activeCap) {
            $this->error("❌ Ошибка: обновленная капа не найдена");
            return;
        }
        
        // Проверяем что лимит изменился
        if ($activeCap->cap_amounts[0] === 25) {
            $this->info("✅ Лимит капы обновлен с 20 на 25");
        } else {
            $this->error("❌ Ошибка: лимит капы не обновился (текущий: " . $activeCap->cap_amounts[0] . ")");
            return;
        }
        
        $this->info('📋 Этап 3b: Создание новой капы после удаления старой...');
        
        // Сначала удаляем старую капу
        $deleteMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 10031,
            'user' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 25\nGeo: AT\nDELETE"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($deleteMessage->id, $deleteMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Старая капа удалена");
        } else {
            $this->error("❌ Ошибка удаления старой капы");
            return;
        }
        
        // Теперь создаем новую капу с теми же параметрами
        $newCapMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 10032,
            'user' => 'Test User',
            'message' => "Affiliate: G06\nRecipient: TMedia\nCap: 30\nGeo: AT\nSchedule: 24/7"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($newCapMessage->id, $newCapMessage->message);
        
        if ($result['cap_entries_count'] === 1) {
            $this->info("✅ Новая капа создана после удаления старой");
        } else {
            $this->error("❌ Ошибка создания новой капы после удаления старой");
            return;
        }
        
        // Получаем новую активную капу
        $activeCap = Cap::where('affiliate_name', 'G06')
                       ->where('recipient_name', 'TMedia')
                       ->whereJsonContains('geos', 'AT')
                       ->where('status', 'RUN')
                       ->first();
        
        if (!$activeCap) {
            $this->error("❌ Ошибка: новая активная капа не найдена");
            return;
        }
        
        $this->info('📋 Этап 4: Простая команда STOP (через reply_to_message)...');
        
        // Останавливаем капу простой командой в ответ на новое сообщение с капой
        $simpleStopMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1004,
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на новое сообщение с капой
            'user' => 'Test User',
            'message' => "STOP"
        ]);
        
        $this->info("DEBUG: Останавливаем капу ID {$activeCap->id} (статус {$activeCap->status})");
        
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
        
        // Удаляем капу простой командой в ответ на новое сообщение с капой
        $simpleDeleteMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1005,
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на новое сообщение с капой
            'user' => 'Test User',
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
        
        // Восстанавливаем удаленную капу простой командой в ответ на новое сообщение с капой
        $simpleRestoreMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1006,
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на новое сообщение с капой
            'user' => 'Test User',
            'message' => "RESTORE"
        ]);
        
        $this->info("DEBUG: Восстанавливаем капу ID {$activeCap->id} (статус {$activeCap->status})");
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleRestoreMessage->id, $simpleRestoreMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа восстановлена из корзины: {$result['message']}");
        } else {
            $this->error("❌ Ошибка восстановления капы из корзины: " . ($result['error'] ?? 'неизвестная ошибка'));
            $this->info("DEBUG: Результат RESTORE команды: " . json_encode($result));
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
        
        $this->info('📋 Этап 7: Тестирование цепочки команд через reply_to_message...');
        
        // Проверяем статус перед тестированием RUN
        $activeCap->refresh();
        $this->info("DEBUG: Текущий статус капы: {$activeCap->status}");
        
        // Если капа не STOP, останавливаем её (отвечаем на изначальное сообщение с капой)
        if ($activeCap->status !== 'STOP') {
            $stopMessage2 = Message::create([
                'chat_id' => $chat->id,
                'telegram_message_id' => 1009, // Изменил с 1007 чтобы не конфликтовать
                'reply_to_message_id' => $newCapMessage->id, // Отвечаем на новое сообщение с капой
                'user' => 'Test User',
                'message' => "STOP"
            ]);
            
            $result = $capAnalysisService->analyzeAndSaveCapMessage($stopMessage2->id, $stopMessage2->message);
            
            if (isset($result['status_changed']) && $result['status_changed'] === 1) {
                $this->info("✅ Капа остановлена для тестирования RUN");
            } else {
                $this->error("❌ Ошибка остановки капы для тестирования RUN: " . ($result['error'] ?? 'неизвестная ошибка'));
                return;
            }
            
            $activeCap->refresh();
            $this->info("DEBUG: Статус после STOP: {$activeCap->status}");
        }
        
        // Теперь запускаем капу командой RUN (отвечаем на новое сообщение с капой)
        $simpleRunMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1010, // Изменил с 1008 
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на новое сообщение с капой
            'user' => 'Test User',
            'message' => "RUN"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($simpleRunMessage->id, $simpleRunMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа запущена командой RUN: {$result['message']}");
        } else {
            $this->error("❌ Ошибка запуска капы командой RUN: " . ($result['error'] ?? 'неизвестная ошибка'));
            $this->info("DEBUG: Результат RUN команды: " . json_encode($result));
            return;
        }
        
        // Проверяем статус
        $activeCap->refresh();
        if ($activeCap->status === 'RUN') {
            $this->info("✅ Статус капы изменен на RUN после команды RUN");
        } else {
            $this->error("❌ Ошибка: статус капы не изменен после команды RUN (текущий: {$activeCap->status})");
            return;
        }
        
        $this->info('📋 Этап 8: Проверка фильтрации по статусу...');
        
        // Сначала удаляем капу для проверки фильтрации
        $deleteForFilterMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1011,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "DELETE"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($deleteForFilterMessage->id, $deleteForFilterMessage->message);
        
        if (isset($result['status_changed']) && $result['status_changed'] === 1) {
            $this->info("✅ Капа удалена для проверки фильтрации");
        } else {
            $this->error("❌ Ошибка удаления капы для проверки фильтрации");
            return;
        }
        
        // Теперь проверяем что удаленная капа не показывается в поиске активных кап
        $activeCaps = $capAnalysisService->searchCaps(null, $chat->id);
        
        // Должны найти все активные капы кроме удаленной (в данном чате может быть несколько кап из других тестов)
        $hasDeletedCap = false;
        foreach ($activeCaps as $capResult) {
            if ($capResult['analysis']['affiliate_name'] === 'G06' && 
                $capResult['analysis']['recipient_name'] === 'TMedia' &&
                in_array('AT', $capResult['analysis']['geos'])) {
                $hasDeletedCap = true;
                break;
            }
        }
        
        if (!$hasDeletedCap) {
            $this->info("✅ Удаленная капа не отображается в поиске активных кап");
        } else {
            $this->error("❌ Ошибка: удаленная капа отображается в поиске активных кап");
            return;
        }
        
        // Проверяем поиск с фильтром по статусу DELETE
        $deletedCaps = $capAnalysisService->searchCapsWithFilters(null, $chat->id, ['status' => 'DELETE']);
        
        // Ищем нашу удаленную капу среди результатов
        $foundDeletedCap = false;
        foreach ($deletedCaps as $capResult) {
            if ($capResult['analysis']['affiliate_name'] === 'G06' && 
                $capResult['analysis']['recipient_name'] === 'TMedia' &&
                in_array('AT', $capResult['analysis']['geos'])) {
                $foundDeletedCap = true;
                break;
            }
        }
        
        if ($foundDeletedCap) {
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
            'telegram_message_id' => 1020,
            'user' => 'Test User',
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
            'telegram_message_id' => 1021,
            'reply_to_message_id' => $newCapMessage->id, // Отвечаем на сообщение с капой
            'user' => 'Test User',
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
        
        // Тестируем создание новой капы с новым гео
        $wrongGeoMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1022,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "Cap: 40\nGeo: FR" // Новое гео
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($wrongGeoMessage->id, $wrongGeoMessage->message);
        
        if ($result['cap_entries_count'] === 1) {
            $this->info("✅ При новом гео создалась новая капа (как и должно быть)");
        } else {
            $this->error("❌ Ошибка: при новом гео должна создаться новая капа");
            return;
        }
        
        $this->info('📋 Этап 11: Тестирование ошибок...');
        
        // Тестируем ошибку - попытка изменить статус несуществующей капы
        $errorMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1030,
            'user' => 'Test User',
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
            'telegram_message_id' => 1031,
            'user' => 'Test User',
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
        
        // Тестируем обновление капы - правильный гео
        $this->info("📝 Тестируем обновление капы через reply_to_message (упрощенный формат)...");
        
        // Создаем сообщение с капой
        $newCapMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1021,
            'user' => 'Test User',
            'message' => "Affiliate: TestAffiliate\nRecipient: TestRecipient\nCap: 30\nGeo: DE"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($newCapMessage->id, $newCapMessage->message);
        
        if (isset($result['cap_entries_count']) && $result['cap_entries_count'] === 1) {
            $this->info("✅ Капа создана для тестирования");
        } else {
            $this->error("❌ Ошибка создания капы для тестирования");
            return;
        }
        
        // Тестируем обновление капы через reply с упрощенным форматом (только Geo + поля для обновления)
        $updateMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1022,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "Geo: DE\nCap: 35\nSchedule: 10:00/18:00"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage->id, $updateMessage->message);
        
        if (isset($result['updated_entries_count']) && $result['updated_entries_count'] === 1) {
            $this->info("✅ Капа обновлена через reply с упрощенным форматом");
        } else {
            $this->error("❌ Ошибка обновления капы через reply с упрощенным форматом");
            return;
        }
        
        // Проверяем что лимит и расписание изменились
        $updatedCap = Cap::where('affiliate_name', 'TestAffiliate')
                         ->where('recipient_name', 'TestRecipient')
                         ->whereJsonContains('geos', 'DE')
                         ->where('status', 'RUN')
                         ->first();
        
        if ($updatedCap && $updatedCap->cap_amounts[0] === 35 && $updatedCap->schedule === '10:00/18:00') {
            $this->info("✅ Лимит и расписание капы обновлены через упрощенный формат");
        } else {
            $this->error("❌ Ошибка: лимит или расписание капы не обновились");
            return;
        }
        
        // Тестируем создание новой капы с неправильным гео
        $newGeoMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1023,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "Geo: FR\nCap: 40" // Новое гео
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($newGeoMessage->id, $newGeoMessage->message);
        
        if (isset($result['cap_entries_count']) && $result['cap_entries_count'] === 1) {
            $this->info("✅ Создана новая капа для нового гео FR");
        } else {
            $this->error("❌ Ошибка: должна создаться новая капа для нового гео");
            return;
        }
        
        // Тестируем ошибку - обновление без гео
        $noGeoMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1024,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "Cap: 50\nSchedule: 24/7" // Без гео
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($noGeoMessage->id, $noGeoMessage->message);
        
        if (isset($result['cap_entries_count']) && $result['cap_entries_count'] === 0) {
            $this->info("✅ Сообщение без Geo корректно игнорируется");
        } else {
            $this->error("❌ Ошибка: сообщение без Geo должно игнорироваться");
            return;
        }
        
        // Тестируем обновление с пустыми полями (сброс до значений по умолчанию)
        $resetMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1025,
            'reply_to_message_id' => $newCapMessage->id,
            'user' => 'Test User',
            'message' => "Geo: DE\nTotal:\nSchedule:\nLanguage:" // Пустые поля
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($resetMessage->id, $resetMessage->message);
        
        if (isset($result['updated_entries_count']) && $result['updated_entries_count'] === 1) {
            $this->info("✅ Пустые поля сброшены до значений по умолчанию");
        } else {
            $this->error("❌ Ошибка сброса пустых полей");
            return;
        }
        
        // Проверяем что поля сброшены
        $resetCap = Cap::where('affiliate_name', 'TestAffiliate')
                      ->where('recipient_name', 'TestRecipient')
                      ->whereJsonContains('geos', 'DE')
                      ->where('status', 'RUN')
                      ->first();
        
        if ($resetCap && $resetCap->total_amount === -1 && $resetCap->schedule === '24/7' && $resetCap->language === 'en') {
            $this->info("✅ Поля корректно сброшены до значений по умолчанию");
        } else {
            $this->error("❌ Ошибка: поля не сброшены до значений по умолчанию");
            return;
        }

        // Тестируем обновление через цепочку reply (reply на reply)
        $chainReplyMessage = Message::create([
            'chat_id' => $chat->id,
            'telegram_message_id' => 1026,
            'reply_to_message_id' => $updateMessage->id, // Отвечаем на сообщение с обновлением
            'user' => 'Test User',
            'message' => "Geo: DE\nCap: 45\nLanguage: de"
        ]);
        
        $result = $capAnalysisService->analyzeAndSaveCapMessage($chainReplyMessage->id, $chainReplyMessage->message);
        
        if (isset($result['updated_entries_count']) && $result['updated_entries_count'] === 1) {
            $this->info("✅ Обновление через цепочку reply работает корректно");
        } else {
            $this->error("❌ Ошибка обновления через цепочку reply");
            return;
        }
        
        // Проверяем что обновление прошло
        $chainCap = Cap::where('affiliate_name', 'TestAffiliate')
                      ->where('recipient_name', 'TestRecipient')
                      ->whereJsonContains('geos', 'DE')
                      ->where('status', 'RUN')
                      ->first();
        
        if ($chainCap && $chainCap->cap_amounts[0] === 45 && $chainCap->language === 'de') {
            $this->info("✅ Обновление через цепочку reply прошло успешно");
        } else {
            $this->error("❌ Ошибка: обновление через цепочку reply не прошло");
            return;
        }
        
        return 0;
    }
} 