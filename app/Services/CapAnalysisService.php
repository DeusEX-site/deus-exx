<?php

namespace App\Services;

use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;

class CapAnalysisService
{
    /**
     * Анализирует сообщение на наличие кап и сохраняет в таблицу
     */
    public function analyzeAndSaveCapMessage($messageId, $messageText)
    {
        // Проверяем команды управления статусом
        if ($this->isStatusCommand($messageText)) {
            return $this->processStatusCommand($messageId, $messageText);
        }
        
        // Получаем текущее сообщение для проверки reply_to_message
        $currentMessage = Message::find($messageId);
        
        // Проверяем, является ли сообщение обновлением капы через reply
        if ($currentMessage && $currentMessage->reply_to_message_id && $this->isUpdateCapMessage($messageText)) {
            return $this->processCapUpdate($messageId, $messageText, $currentMessage);
        }
        
        // Проверяем на наличие нескольких блоков кап
        $blocks = preg_split('/\n\s*\n/', $messageText);
        $allCombinations = [];
        
        foreach ($blocks as $block) {
            $block = trim($block);
            if (empty($block)) continue;
            
            // Проверяем, является ли блок стандартной капой
            if ($this->isStandardCapMessage($block)) {
                $capCombinations = $this->parseStandardCapMessage($block);
                if ($capCombinations && is_array($capCombinations)) {
                    $allCombinations = array_merge($allCombinations, $capCombinations);
                }
            }
        }
        
        // Если не нашли блоков, пробуем парсить как единое сообщение
        if (empty($allCombinations) && $this->isStandardCapMessage($messageText)) {
            $allCombinations = $this->parseStandardCapMessage($messageText) ?? [];
        }
        
        if (!empty($allCombinations)) {
            $createdCount = 0;
            $updatedCount = 0;
            
            foreach ($allCombinations as $capData) {
                // Проверяем что geos является массивом и не пустым
                $geos = $capData['geos'] ?? [];
                if (!is_array($geos) || empty($geos)) {
                    continue; // Пропускаем если нет geos
                }
                
                // Проверяем что cap_amounts является массивом и не пустым
                $capAmounts = $capData['cap_amounts'] ?? [];
                if (!is_array($capAmounts) || empty($capAmounts)) {
                    continue; // Пропускаем если нет cap_amounts
                }
                
                // Дополнительная валидация - проверяем все обязательные поля
                if (!$capData['affiliate_name'] || !$capData['recipient_name']) {
                    continue; // Пропускаем если нет обязательных полей
                }
                
                $existingCap = null;
                
                // Проверяем есть ли reply_to_message и ищем капу по цепочке сообщений
                if ($currentMessage && $currentMessage->reply_to_message_id) {
                    // Ищем изначальную капу через цепочку reply_to_message
                    $existingCap = $this->findOriginalCap($currentMessage->reply_to_message_id);
                    
                    // Проверяем, что капа найдена и имеет подходящий статус
                    if ($existingCap && !in_array($existingCap->status, ['RUN', 'STOP'])) {
                        $existingCap = null;
                    }
                                     
                    // Проверяем что хотя бы одно гео совпадает (это обязательное условие для обновления через reply)
                    if ($existingCap) {
                        $hasMatchingGeo = false;
                        foreach ($geos as $geo) {
                            if (in_array($geo, $existingCap->geos)) {
                                $hasMatchingGeo = true;
                                break;
                            }
                        }
                        if (!$hasMatchingGeo) {
                            $existingCap = null; // Сбрасываем если ни одно гео не совпадает
                        }
                    }
                }
                
                // Если капа не найдена через reply_to_message, ищем дубликат обычным способом
                if (!$existingCap) {
                    // Ищем дубликат по любому из гео
                    foreach ($geos as $geo) {
                        $existingCap = Cap::findDuplicate(
                            $capData['affiliate_name'],
                            $capData['recipient_name'], 
                            $geo
                        );
                        if ($existingCap) {
                            break; // Найден дубликат, выходим из цикла
                        }
                    }
                }
                
                if ($existingCap) {
                    // Найден дубликат - определяем какие поля нужно обновить
                    $updateData = $this->getFieldsToUpdate($existingCap, $capData, $geos, $messageText, $messageText);
                    
                    if (!empty($updateData)) {
                        CapHistory::createFromCap($existingCap);
                        
                        // НЕ обновляем message_id - он должен оставаться ID изначального сообщения с капой
                        // $updateData['message_id'] = $messageId;
                        
                        $existingCap->update($updateData);
                        
                        $updatedCount++;
                    }
                    // Если нет изменений - ничего не делаем, просто пропускаем
                } else {
                    // Дубликат не найден - создаем новую запись
                    Cap::create([
                        'message_id' => $messageId,
                        'cap_amounts' => $capAmounts, // Массив cap amounts
                        'total_amount' => $capData['total_amount'],
                        'schedule' => $capData['schedule'],
                        'date' => $capData['date'],
                        'is_24_7' => $capData['is_24_7'],
                        'affiliate_name' => $capData['affiliate_name'],
                        'recipient_name' => $capData['recipient_name'],
                        'geos' => $geos, // Массив geos
                        'work_hours' => $capData['work_hours'],
                        'language' => $capData['language'],
                        'funnel' => $capData['funnel'],
                        'test' => $capData['test'],
                        'pending_acq' => $capData['pending_acq'],
                        'freeze_status_on_acq' => $capData['freeze_status_on_acq'],
                        'start_time' => $capData['start_time'],
                        'end_time' => $capData['end_time'],
                        'timezone' => $capData['timezone'],
                        'highlighted_text' => $messageText,
                        'status' => 'RUN', // По умолчанию новые капы активны
                        'status_updated_at' => now()
                    ]);
                    
                    $createdCount++;
                }
            }
            
            return [
                'cap_entries_count' => $createdCount,
                'updated_entries_count' => $updatedCount,
                'total_processed' => $createdCount + $updatedCount
            ];
        }
        
        return ['cap_entries_count' => 0];
    }

    /**
     * Определяет, какие поля нужно обновить (только указанные в сообщении и измененные)
     */
    private function getFieldsToUpdate($existingCap, $newCapData, $geos, $messageText, $originalMessage)
    {
        $updateData = [];
        
        // CAP amounts - всегда обновляем если указан и не пустой (это обязательное поле)
        if (isset($newCapData['cap_amounts']) && is_array($newCapData['cap_amounts']) && !empty($newCapData['cap_amounts'])) {
            // Сравниваем массивы cap_amounts
            if (json_encode($existingCap->cap_amounts) != json_encode($newCapData['cap_amounts'])) {
                $updateData['cap_amounts'] = $newCapData['cap_amounts'];
            }
        }
        
        // GEOs - обновляем если указаны
        if (is_array($geos) && !empty($geos)) {
            // Сравниваем массивы geos
            if (json_encode($existingCap->geos) != json_encode($geos)) {
                $updateData['geos'] = $geos;
            }
        }
        
        // Total - обновляем если указан в сообщении (даже если пустой - сбрасываем до значения по умолчанию)
        if ($this->isFieldSpecifiedInMessage($messageText, 'total')) {
            $rawTotalValue = $this->getRawFieldValue($messageText, 'total');
            $newTotal = $this->isEmpty($rawTotalValue) ? -1 : ($newCapData['total_amount'] ?? -1); // По умолчанию бесконечность
            
            if ($existingCap->total_amount != $newTotal) {
                $updateData['total_amount'] = $newTotal;
            }
        }
        
        // Schedule - обновляем если указан в сообщении (даже если пустой - сбрасываем до 24/7)
        if ($this->isFieldSpecifiedInMessage($messageText, 'schedule')) {
            $rawScheduleValue = $this->getRawFieldValue($messageText, 'schedule');
            
            if ($this->isEmpty($rawScheduleValue)) {
                // Сбрасываем до значений по умолчанию
                if ($existingCap->schedule != '24/7' || 
                    $existingCap->work_hours != '24/7' || 
                    !$existingCap->is_24_7 || 
                    $existingCap->start_time !== null || 
                    $existingCap->end_time !== null || 
                    $existingCap->timezone !== null) {
                    $updateData['schedule'] = '24/7';
                    $updateData['work_hours'] = '24/7';
                    $updateData['is_24_7'] = true;
                    $updateData['start_time'] = null;
                    $updateData['end_time'] = null;
                    $updateData['timezone'] = null;
                }
            } else {
                if ($existingCap->schedule != ($newCapData['schedule'] ?? '24/7') ||
                    $existingCap->work_hours != ($newCapData['work_hours'] ?? '24/7') ||
                    $existingCap->is_24_7 != ($newCapData['is_24_7'] ?? true) ||
                    $existingCap->start_time != ($newCapData['start_time'] ?? null) ||
                    $existingCap->end_time != ($newCapData['end_time'] ?? null) ||
                    $existingCap->timezone != ($newCapData['timezone'] ?? null)) {
                    $updateData['schedule'] = $newCapData['schedule'] ?? '24/7';
                    $updateData['work_hours'] = $newCapData['work_hours'] ?? '24/7';
                    $updateData['is_24_7'] = $newCapData['is_24_7'] ?? true;
                    $updateData['start_time'] = $newCapData['start_time'] ?? null;
                    $updateData['end_time'] = $newCapData['end_time'] ?? null;
                    $updateData['timezone'] = $newCapData['timezone'] ?? null;
                }
            }
        }
        
        // Date - обновляем если указан в сообщении (даже если пустой - сбрасываем до null)
        if ($this->isFieldSpecifiedInMessage($messageText, 'date')) {
            $rawDateValue = $this->getRawFieldValue($messageText, 'date');
            $newDate = $this->isEmpty($rawDateValue) ? null : ($newCapData['date'] ?? null); // По умолчанию null
            
            if ($existingCap->date != $newDate) {
                $updateData['date'] = $newDate;
            }
        }
        
        // Language - обновляем если указан в сообщении (даже если пустой - сбрасываем до 'en')
        if ($this->isFieldSpecifiedInMessage($messageText, 'language')) {
            $rawLanguageValue = $this->getRawFieldValue($messageText, 'language');
            $newLanguage = $this->isEmpty($rawLanguageValue) ? 'en' : ($newCapData['language'] ?? 'en'); // По умолчанию английский
            
            if ($existingCap->language != $newLanguage) {
                $updateData['language'] = $newLanguage;
            }
        }
        
        // Funnel - обновляем если указан в сообщении (даже если пустой - сбрасываем до null)
        if ($this->isFieldSpecifiedInMessage($messageText, 'funnel')) {
            $rawFunnelValue = $this->getRawFieldValue($messageText, 'funnel');
            $newFunnel = $this->isEmpty($rawFunnelValue) ? null : ($newCapData['funnel'] ?? null); // По умолчанию null
            
            if ($existingCap->funnel != $newFunnel) {
                $updateData['funnel'] = $newFunnel;
            }
        }
        
        // Test - обновляем если указан в сообщении (даже если пустой - сбрасываем до null)
        if ($this->isFieldSpecifiedInMessage($messageText, 'test')) {
            $rawTestValue = $this->getRawFieldValue($messageText, 'test');
            $newTest = $this->isEmpty($rawTestValue) ? null : ($newCapData['test'] ?? null); // По умолчанию null
            
            if ($existingCap->test != $newTest) {
                $updateData['test'] = $newTest;
            }
        }
        
        // Pending ACQ - обновляем если указан в сообщении (даже если пустой - сбрасываем до false)
        if ($this->isFieldSpecifiedInMessage($messageText, 'pending_acq')) {
            $rawPendingValue = $this->getRawFieldValue($messageText, 'pending_acq');
            $newPending = $this->isEmpty($rawPendingValue) ? false : ($newCapData['pending_acq'] ?? false); // По умолчанию false
            
            if ($existingCap->pending_acq != $newPending) {
                $updateData['pending_acq'] = $newPending;
            }
        }
        
        // Freeze status - обновляем если указан в сообщении (даже если пустой - сбрасываем до false)
        if ($this->isFieldSpecifiedInMessage($messageText, 'freeze_status')) {
            $rawFreezeValue = $this->getRawFieldValue($messageText, 'freeze_status');
            $newFreeze = $this->isEmpty($rawFreezeValue) ? false : ($newCapData['freeze_status_on_acq'] ?? false); // По умолчанию false
            
            if ($existingCap->freeze_status_on_acq != $newFreeze) {
                $updateData['freeze_status_on_acq'] = $newFreeze;
            }
        }
        
        // Highlighted text - всегда обновляем при новом сообщении
        if ($existingCap->highlighted_text != $messageText) {
            $updateData['highlighted_text'] = $messageText;
        }
        
        // НЕ обновляем message_id - он должен оставаться ID изначального сообщения с капой
        
        return $updateData;
    }

    /**
     * Проверяет, является ли сообщение командой управления статусом
     */
    private function isStatusCommand($messageText)
    {
        // Простые команды (RUN, STOP, DELETE, RESTORE)
        if (preg_match('/^(run|stop|delete|restore)\s*$/i', trim($messageText))) {
            return true;
        }
        
        // Полные команды с полями
        return preg_match('/^(run|stop|delete|restore)\s*$/mi', $messageText) ||
               (preg_match('/^affiliate:\s*(.*)$/mi', $messageText) &&
                preg_match('/^recipient:\s*(.*)$/mi', $messageText) &&
                preg_match('/^cap:\s*(.*)$/mi', $messageText) &&
                preg_match('/^geo:\s*(.*)$/mi', $messageText) &&
                preg_match('/^(run|stop|delete|restore)\s*$/mi', $messageText));
    }

    /**
     * Находит изначальную капу через цепочку reply_to_message
     */
    private function findOriginalCap($messageId)
    {
        $currentMessageId = $messageId;
        $visited = [];
        
        // Ограничиваем глубину поиска для избежания бесконечных циклов
        $maxDepth = 20;
        $depth = 0;
        
        while ($currentMessageId && $depth < $maxDepth) {
            if (in_array($currentMessageId, $visited)) {
                // Цикл обнаружен, прекращаем поиск
                break;
            }
            
            $visited[] = $currentMessageId;
            
            // Проверяем, есть ли капа для этого сообщения
            $cap = Cap::where('message_id', $currentMessageId)->first();
            if ($cap) {
                // Найдена капа - возвращаем её
                return $cap;
            }
            
            // Ищем родительское сообщение по message_id (Telegram ID)
            $message = Message::where('message_id', $currentMessageId)->first();
            if (!$message || !$message->reply_to_message_id) {
                // Дошли до корня или сообщение не найдено
                break;
            }
            
            $currentMessageId = $message->reply_to_message_id;
            $depth++;
        }
        
        // Если не нашли капу в цепочке, возвращаем null
        return null;
    }

    /**
     * Обрабатывает команды управления статусом (RUN/STOP/DELETE/RESTORE) с поддержкой множественных кап
     */
    private function processStatusCommand($messageId, $messageText)
    {
        // Определяем команду
        $command = null;
        if (preg_match('/^(run|stop|delete|restore)\s*$/i', trim($messageText))) {
            $command = strtoupper(trim($messageText));
        } elseif (preg_match('/^(run|stop|delete|restore)\s*$/mi', $messageText, $matches)) {
            $command = strtoupper($matches[1]);
        }

        if (!$command) {
            return ['cap_entries_count' => 0, 'error' => 'Неверная команда'];
        }

        // Получаем текущее сообщение для проверки reply_to_message
        $currentMessage = Message::find($messageId);
        if (!$currentMessage) {
            return ['cap_entries_count' => 0, 'error' => 'Сообщение не найдено'];
            }

            // Определяем допустимые статусы для поиска в зависимости от команды
            $allowedStatuses = match($command) {
                'RUN' => ['STOP'], // Возобновить можно только остановленную
                'STOP' => ['RUN'], // Остановить можно только активную
                'DELETE' => ['RUN', 'STOP'], // Удалить можно активную или остановленную
                'RESTORE' => ['DELETE'], // Восстановить можно только удаленную
                default => ['RUN', 'STOP', 'DELETE']
            };

        // Если это простая команда (только run/stop/delete/restore)
        if (preg_match('/^(run|stop|delete|restore)\s*$/i', trim($messageText))) {
            if (!$currentMessage->reply_to_message_id) {
                return ['cap_entries_count' => 0, 'error' => 'Команда должна быть ответом на сообщение с капой'];
            }

            // Ищем все капы в сообщении, на которое отвечаем
            $originalMessageId = $this->findOriginalMessageId($currentMessage->reply_to_message_id);
            if (!$originalMessageId) {
                return ['cap_entries_count' => 0, 'error' => 'Оригинальное сообщение не найдено'];
            }

            // Находим все капы связанные с этим сообщением
            $caps = Cap::where('message_id', $originalMessageId)
                       ->whereIn('status', $allowedStatuses)
                       ->get();

            if ($caps->isEmpty()) {
                $statusText = implode(', ', $allowedStatuses);
                return ['cap_entries_count' => 0, 'error' => "Капы не найдены или имеют неподходящий статус. Для команды {$command} требуется статус: {$statusText}"];
            }

            $updatedCount = 0;
            $messages = [];

            foreach ($caps as $cap) {
            // Создаем запись в истории
                CapHistory::createFromCap($cap);

            // Подготавливаем данные для обновления
            $updateData = [
                    'status' => $command === 'RESTORE' ? 'RUN' : $command,
                'status_updated_at' => now(),
            ];

                // При DELETE не меняем highlighted_text
            if ($command !== 'DELETE') {
                $updateData['highlighted_text'] = $messageText;
            }

            // Обновляем статус
                $cap->update($updateData);
                $updatedCount++;

                $action = match($command) {
                    'RUN' => 'возобновлена',
                    'STOP' => 'остановлена', 
                    'DELETE' => 'удалена',
                    'RESTORE' => 'восстановлена из корзины',
                    default => 'обновлена'
                };

                                    $geoStr = is_array($cap->geos) && !empty($cap->geos) ? $cap->geos[0] : 'Unknown';
                    $capStr = is_array($cap->cap_amounts) && !empty($cap->cap_amounts) ? $cap->cap_amounts[0] : 'Unknown';
                    $messages[] = "{$cap->affiliate_name} → {$cap->recipient_name} ({$geoStr}, {$capStr}) {$action}";
            }
            
            return [
                'cap_entries_count' => 0,
                'updated_entries_count' => $updatedCount,
                'status_changed' => $updatedCount,
                'message' => "Обновлено кап: {$updatedCount}. " . implode('; ', $messages)
            ];
        }

        // Полная команда с указанием полей
        // Парсим поля
        $geo = null;
        if (preg_match('/^geo:\s*(.*)$/m', $messageText, $matches)) {
            $geo = trim($matches[1]);
        }

        // Если в команде есть reply_to_message_id
        if ($currentMessage->reply_to_message_id) {
            // Ищем оригинальное сообщение
            $originalMessageId = $this->findOriginalMessageId($currentMessage->reply_to_message_id);
            if (!$originalMessageId) {
                return ['cap_entries_count' => 0, 'error' => 'Оригинальное сообщение не найдено'];
            }

            // Если Geo указан - ищем конкретную капу
            if ($geo) {
                $cap = Cap::where('message_id', $originalMessageId)
                          ->whereJsonContains('geos', $geo)
                          ->whereIn('status', $allowedStatuses)
                          ->first();

                if (!$cap) {
                    $statusText = implode(', ', $allowedStatuses);
                    return ['cap_entries_count' => 0, 'error' => "Капа с гео {$geo} не найдена или имеет неподходящий статус. Для команды {$command} требуется статус: {$statusText}"];
                }

                // Обновляем одну капу
                CapHistory::createFromCap($cap);

                $updateData = [
                    'status' => $command === 'RESTORE' ? 'RUN' : $command,
                    'status_updated_at' => now(),
                ];

                if ($command !== 'DELETE') {
                    $updateData['highlighted_text'] = $messageText;
                }

                $cap->update($updateData);

            $action = match($command) {
                'RUN' => 'возобновлена',
                'STOP' => 'остановлена', 
                'DELETE' => 'удалена',
                'RESTORE' => 'восстановлена из корзины',
                default => 'обновлена'
            };
            
            return [
                'cap_entries_count' => 0,
                'updated_entries_count' => 1,
                'status_changed' => 1,
                    'message' => "Капа {$cap->affiliate_name} → {$cap->recipient_name} ({$geo}, " . 
                           (is_array($cap->cap_amounts) && !empty($cap->cap_amounts) ? $cap->cap_amounts[0] : 'Unknown') . ") {$action}"
                ];
            } else {
                // Если Geo не указан - обновляем все капы в сообщении
                $caps = Cap::where('message_id', $originalMessageId)
                           ->whereIn('status', $allowedStatuses)
                           ->get();

                if ($caps->isEmpty()) {
                    $statusText = implode(', ', $allowedStatuses);
                    return ['cap_entries_count' => 0, 'error' => "Капы не найдены или имеют неподходящий статус. Для команды {$command} требуется статус: {$statusText}"];
                }

                $updatedCount = 0;
                $messages = [];

                foreach ($caps as $cap) {
                    CapHistory::createFromCap($cap);

                    $updateData = [
                        'status' => $command === 'RESTORE' ? 'RUN' : $command,
                        'status_updated_at' => now(),
                    ];

                    if ($command !== 'DELETE') {
                        $updateData['highlighted_text'] = $messageText;
                    }

                    $cap->update($updateData);
                    $updatedCount++;

                    $action = match($command) {
                        'RUN' => 'возобновлена',
                        'STOP' => 'остановлена', 
                        'DELETE' => 'удалена',
                        'RESTORE' => 'восстановлена из корзины',
                        default => 'обновлена'
                    };

                    $geoStr = is_array($cap->geos) && !empty($cap->geos) ? $cap->geos[0] : 'Unknown';
                    $capStr = is_array($cap->cap_amounts) && !empty($cap->cap_amounts) ? $cap->cap_amounts[0] : 'Unknown';
                    $messages[] = "{$cap->affiliate_name} → {$cap->recipient_name} ({$geoStr}, {$capStr}) {$action}";
                }

                return [
                    'cap_entries_count' => 0,
                    'updated_entries_count' => $updatedCount,
                    'status_changed' => $updatedCount,
                    'message' => "Обновлено кап: {$updatedCount}. " . implode('; ', $messages)
                ];
            }
        }

        // Старая логика для обратной совместимости (когда указаны все поля)
        $affiliate = null;
        $recipient = null;
        $cap = null;

        if (preg_match('/^affiliate:\s*(.*)$/m', $messageText, $matches)) {
            $affiliate = trim($matches[1]);
        }

        if (preg_match('/^recipient:\s*(.*)$/m', $messageText, $matches)) {
            $recipient = trim($matches[1]);
        }

        if (preg_match('/^cap:\s*(.*)$/mi', $messageText, $matches)) {
            $capValue = trim($matches[1]);
            if (is_numeric($capValue)) {
                $cap = intval($capValue);
            }
        }

        // Проверяем, что все обязательные поля заполнены
        if (!$affiliate || !$recipient || !$cap || !$geo) {
            return ['cap_entries_count' => 0, 'error' => 'Не все обязательные поля заполнены'];
        }

        // Ищем капу для изменения статуса
        $existingCap = Cap::where('affiliate_name', $affiliate)
                          ->where('recipient_name', $recipient)
                          ->whereJsonContains('geos', $geo)
                          ->whereJsonContains('cap_amounts', $cap)
                          ->whereIn('status', $allowedStatuses)
                          ->first();

        if (!$existingCap) {
            return ['cap_entries_count' => 0, 'error' => 'Капа не найдена'];
        }

        // Создаем запись в истории перед изменением статуса
        CapHistory::createFromCap($existingCap);

        // Подготавливаем данные для обновления
        $updateData = [
            'status' => $command === 'RESTORE' ? 'RUN' : $command,
            'status_updated_at' => now(),
        ];

        // При DELETE не меняем highlighted_text
        if ($command !== 'DELETE') {
            $updateData['highlighted_text'] = $messageText;
        }

        // Обновляем статус
        $existingCap->update($updateData);

        $action = match($command) {
            'RUN' => 'возобновлена',
            'STOP' => 'остановлена', 
            'DELETE' => 'удалена',
            'RESTORE' => 'восстановлена из корзины',
            default => 'обновлена'
        };
        
        return [
            'cap_entries_count' => 0,
            'updated_entries_count' => 1,
            'status_changed' => 1,
            'message' => "Капа {$affiliate} → {$recipient} ({$geo}, {$cap}) {$action}"
        ];
    }
    
    /**
     * Проверяет, есть ли определенное поле в исходном тексте сообщения (независимо от того, пустое оно или нет)
     */
    private function isFieldSpecifiedInMessage($messageText, $fieldName)
    {
        $patterns = [
            'total' => '/^total:\s*(.*)$/m',
            'schedule' => '/^schedule:\s*(.*)$/m',
            'date' => '/^date:\s*(.*)$/m',
            'language' => '/^language:\s*(.*)$/m',
            'funnel' => '/^funnel:\s*(.*)$/m',
            'test' => '/^test:\s*(.*)$/m',
            'pending_acq' => '/^pending acq:\s*(.*)$/m',
            'freeze_status_on_acq' => '/^freeze status on acq:\s*(.*)$/m'
        ];
        
        if (!isset($patterns[$fieldName])) {
            return false;
        }
        
        return preg_match($patterns[$fieldName], $messageText);
    }
    
    /**
     * Получает сырое значение поля из сообщения
     */
    private function getRawFieldValue($messageText, $fieldName)
    {
        $patterns = [
            'total' => '/^total:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'schedule' => '/^schedule:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', 
            'date' => '/^date:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'language' => '/^language:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'funnel' => '/^funnel:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'test' => '/^test:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'pending_acq' => '/^pending acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m',
            'freeze_status_on_acq' => '/^freeze status on acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m'
        ];
        
        if (!isset($patterns[$fieldName])) {
            return null;
        }
        
        if (preg_match($patterns[$fieldName], $messageText, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Проверяет, является ли сообщение стандартной капой
     */
    private function isStandardCapMessage($messageText)
    {
        // Ищем обязательные поля: Affiliate, Recipient, Cap, Geo
        // ВАЖНО: Проверяем не только наличие полей, но и что они имеют непустые значения
        $hasAffiliate = preg_match('/^affiliate:\s*(.*)$/mi', $messageText, $affiliateMatch);
        $hasRecipient = preg_match('/^recipient:\s*(.*)$/mi', $messageText, $recipientMatch);
        $hasCap = preg_match('/^cap:\s*(.*)$/mi', $messageText, $capMatch);
        $hasGeo = preg_match('/^geo:\s*(.*)$/mi', $messageText, $geoMatch);
        
        // Проверяем, что поля найдены И имеют непустые значения
        if (!$hasAffiliate || !$hasRecipient || !$hasCap || !$hasGeo) {
            return false;
        }
        
        // Проверяем, что значения не пустые
        $affiliateValue = trim($affiliateMatch[1]);
        $recipientValue = trim($recipientMatch[1]);
        $capValue = trim($capMatch[1]);
        $geoValue = trim($geoMatch[1]);
        
        return !$this->isEmpty($affiliateValue) && 
               !$this->isEmpty($recipientValue) && 
               !$this->isEmpty($capValue) && 
               !$this->isEmpty($geoValue);
    }

    /**
     * Проверяет, является ли значение пустым (пустая строка, "-", или только пробелы)
     */
    private function isEmpty($value)
    {
        if ($value === null) return true;
        $trimmed = trim($value);
        return $trimmed === '' || $trimmed === '-';
    }

    /**
     * Парсит значения, разделенные запятыми или пробелами
     */
    private function parseMultipleValues($value, $separateByCommaOnly = false)
    {
        if ($this->isEmpty($value)) {
            return [];
        }
        
        if ($separateByCommaOnly) {
            // Только запятые для Funnel, Pending ACQ, Freeze status, Date
            $values = explode(',', $value);
        } else {
            // Запятые и пробелы для Cap, Geo, Language, Total
            $values = preg_split('/[,\s]+/', $value);
        }
        
        return array_filter(array_map('trim', $values), function($val) {
            return !$this->isEmpty($val);
        });
    }

    /**
     * Специальный парсер для Schedule с учетом различных форматов
     */
    private function parseScheduleValues($value)
    {
        if ($this->isEmpty($value)) {
            return [];
        }
        
        $schedules = [];
        $value = trim($value);
        
        // Сначала извлекаем общий часовой пояс в конце строки (если есть)
        $commonTimezone = '';
        if (preg_match('/\s+(Gmt|gmt|GMT|гмт|ГМТ|Гмт)?([+-]\d{1,2})(:\d{2})?\s*$/i', $value, $tzMatches)) {
            $commonTimezone = ' GMT' . $tzMatches[2];
            if (!isset($tzMatches[3])) {
                $commonTimezone .= ':00';
            } else {
                $commonTimezone .= $tzMatches[3];
            }
            // Удаляем общий часовой пояс из строки для парсинга расписаний
            $value = preg_replace('/\s+(Gmt|gmt|GMT|гмт|ГМТ|Гмт)?([+-]\d{1,2})(:\d{2})?\s*$/i', '', $value);
        }
        
        // Пытаемся определить формат разделителя
        // Проверяем наличие запятых
        if (strpos($value, ',') !== false) {
            // Если есть запятые, используем их как основной разделитель
            $parts = explode(',', $value);
                } else {
            // Иначе пытаемся разделить по паттернам времени
            // Ищем все вхождения времени в различных форматах
            $pattern = '/(?:(?:\d{1,2}[:.]\d{2}|\d{1,2})\s*[-–\/]\s*(?:\d{1,2}[:.]\d{2}|\d{1,2}))/';
            if (preg_match_all($pattern, $value, $matches)) {
                $parts = $matches[0];
            } else {
                // Если не нашли паттерны, возвращаем как одно расписание
                return [$value . $commonTimezone];
            }
        }
        
        // Обрабатываем каждую часть
        foreach ($parts as $part) {
            $schedule = trim($part);
            if (!$this->isEmpty($schedule)) {
                // Добавляем общий часовой пояс к каждому расписанию, если он был
                $schedules[] = $schedule . $commonTimezone;
            }
        }
        
        return $schedules;
    }

    /**
     * Парсит время из Schedule с поддержкой различных форматов
     */
    private function parseScheduleTime($schedule)
    {
        $schedule = trim($schedule);
        
        if ($this->isEmpty($schedule) || preg_match('/24\/7|24-7/i', $schedule)) {
            return [
                'schedule' => '24/7',
                'work_hours' => '24/7',
                'is_24_7' => true,
                'start_time' => null,
                'end_time' => null,
                'timezone' => null
            ];
        }
        
        // Поддержка формата 24/5 (24 часа в день, 5 дней в неделю)
        if (preg_match('/24\/5|24-5/i', $schedule)) {
            return [
                'schedule' => '24/5',
                'work_hours' => '24/5',
                'is_24_7' => false,
                'start_time' => null,
                'end_time' => null,
                'timezone' => null
            ];
        }
        
        // Убираем лишние пробелы
        $schedule = preg_replace('/\s+/', ' ', $schedule);
        
        // Преобразуем различные форматы времени к единому виду
        $normalizedSchedule = $schedule;
        $timezone = null;
        
        // Извлекаем часовой пояс (поддержка +3, GMT+3, GMT+03:00, etc.)
        if (preg_match('/\s*(Gmt|gmt|GMT|гмт|ГМТ|Гмт)?([+-]\d{1,2})(:\d{2})?\s*$/i', $normalizedSchedule, $tzMatches)) {
            $timezone = 'GMT' . $tzMatches[2];
            if (!isset($tzMatches[3])) {
                $timezone .= ':00';
            } else {
                $timezone .= $tzMatches[3];
            }
            // Удаляем часовой пояс из строки для дальнейшей обработки
            $normalizedSchedule = preg_replace('/\s*(Gmt|gmt|GMT|гмт|ГМТ|Гмт)?([+-]\d{1,2})(:\d{2})?\s*$/i', '', $normalizedSchedule);
        }
        
        // Нормализуем разделители времени (точки на двоеточия)
        $normalizedSchedule = str_replace('.', ':', $normalizedSchedule);
        
        // Обработка различных форматов
        $startTime = null;
        $endTime = null;
        
        // Формат: "8:30 - 14:30" или "8.30 - 14.30" (уже нормализовано)
        if (preg_match('/(\d{1,2}:\d{2})\s*[-–]\s*(\d{1,2}:\d{2})/', $normalizedSchedule, $matches)) {
            $startTime = $matches[1];
            $endTime = $matches[2];
        }
        // Формат: "10-19" или "10 - 19" (без минут)
        elseif (preg_match('/(\d{1,2})\s*[-–]\s*(\d{1,2})(?!:|\.)/', $normalizedSchedule, $matches)) {
            $startTime = $matches[1] . ':00';
            $endTime = $matches[2] . ':00';
        }
        // Формат: "18:00/01:00" (слэш вместо дефиса)
        elseif (preg_match('/(\d{1,2}:\d{2})\/(\d{1,2}:\d{2})/', $normalizedSchedule, $matches)) {
            $startTime = $matches[1];
            $endTime = $matches[2];
        }
        // Формат: "10/19" (слэш, без минут)
        elseif (preg_match('/(\d{1,2})\/(\d{1,2})/', $normalizedSchedule, $matches)) {
            $startTime = $matches[1] . ':00';
            $endTime = $matches[2] . ':00';
        }
        
        if ($startTime && $endTime) {
            $timeOnly = $startTime . '/' . $endTime;
            return [
                'schedule' => $timeOnly,
                'work_hours' => $timeOnly,
                'is_24_7' => false,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'timezone' => $timezone
            ];
        }
        
        // Если не удалось распарсить, возвращаем как есть
        return [
            'schedule' => $schedule,
            'work_hours' => $schedule,
            'is_24_7' => false,
            'start_time' => null,
            'end_time' => null,
            'timezone' => null
        ];
    }

    /**
     * Парсит стандартное сообщение капы с привязкой по порядку
     */
    private function parseStandardCapMessage($messageText)
    {
        // Парсим аффилейта и получателя (поддерживаем пустые поля)
        $affiliate = null;
        $recipient = null;

        if (preg_match('/^affiliate:\s*(.*)$/m', $messageText, $matches)) {
            $affiliateValue = trim($matches[1]);
            $affiliate = $this->isEmpty($affiliateValue) ? null : $affiliateValue;
        }

        if (preg_match('/^recipient:\s*(.*)$/m', $messageText, $matches)) {
            $recipientValue = trim($matches[1]);
            $recipient = $this->isEmpty($recipientValue) ? null : $recipientValue;
        }

        // Парсим списки Cap и Geo (поддерживаем пустые поля)
        $caps = [];
        $geos = [];

        // Поддержка cap
        if (preg_match('/^cap:\s*(.*)$/mi', $messageText, $matches)) {
            $capValue = trim($matches[1]);
            if (!$this->isEmpty($capValue)) {
                $capValues = $this->parseMultipleValues($capValue);
                foreach ($capValues as $cap) {
                    if (is_numeric($cap)) {
                        $caps[] = intval($cap);
                    }
                }
            }
        }

        if (preg_match('/^geo:\s*(.*)$/mi', $messageText, $matches)) {
            $geoValue = trim($matches[1]);
            if (!$this->isEmpty($geoValue)) {
                $geos = $this->parseMultipleValues($geoValue);
            }
        }

        // Проверяем, что и cap, и geo имеют значения
        if (empty($caps) || empty($geos)) {
            return null; // Отклоняем сообщения с пустыми cap или geo
        }
        
        // Проверяем, что количество cap совпадает с количеством geo
        if (count($caps) !== count($geos)) {
            return null; // Отклоняем сообщения с несовпадающими количествами
        }

        // Парсим необязательные списки
        $languages = [];
        $funnels = [];
        $totals = [];
        $schedules = [];
        $pendingAcqs = [];
        $freezeStatuses = [];
        $dates = [];

        // Обработка Language с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^language:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $languageValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $languageValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($languageValue)) {
                $languages = $this->parseMultipleValues($languageValue);
            } else {
                $languages = ['en']; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Funnel с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^funnel:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $funnelValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $funnelValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($funnelValue)) {
                $funnels = $this->parseMultipleValues($funnelValue, true); // Только запятые
            } else {
                $funnels = [null]; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Test с учетом возможных дубликатов и пустых значений
        $tests = [];
        if (preg_match_all('/^test:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $testValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $testValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($testValue)) {
                $tests = $this->parseMultipleValues($testValue, true); // Только запятые
            } else {
                $tests = [null]; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Total с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^total:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $totalValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $totalValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($totalValue)) {
                $totalValues = $this->parseMultipleValues($totalValue);
                foreach ($totalValues as $total) {
                    if (is_numeric($total)) {
                        $totals[] = intval($total);
                    } else {
                        $totals[] = -1; // Бесконечность для нечисловых значений
                    }
                }
            } else {
                $totals = [-1]; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Schedule с учетом возможных дубликатов
        if (preg_match_all('/^schedule:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            // Если найдено несколько полей Schedule, выбираем непустое
            $scheduleValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $scheduleValue = $trimmed;
                    break; // Берем первое непустое значение
                }
            }
            
            if (!$this->isEmpty($scheduleValue)) {
                $schedules = $this->parseScheduleValues($scheduleValue); // Специальный парсер для Schedule
            } else {
                $schedules = ['24/7']; // Значение по умолчанию если все поля пустые
            }
        }

        // Обработка Pending ACQ с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^pending acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $pendingValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $pendingValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($pendingValue)) {
                $pendingValues = $this->parseMultipleValues($pendingValue, true); // Только запятые
                foreach ($pendingValues as $pending) {
                    $pendingAcqs[] = in_array(strtolower($pending), ['yes', 'true', '1', 'да']);
                }
            } else {
                $pendingAcqs = [false]; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Freeze status on ACQ с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^freeze status on acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $freezeValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $freezeValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($freezeValue)) {
                $freezeValues = $this->parseMultipleValues($freezeValue, true); // Только запятые
                foreach ($freezeValues as $freeze) {
                    $freezeStatuses[] = in_array(strtolower($freeze), ['yes', 'true', '1', 'да']);
                }
            } else {
                $freezeStatuses = [false]; // Значение по умолчанию для пустого поля
            }
        }

        // Обработка Date с учетом возможных дубликатов и пустых значений
        if (preg_match_all('/^Date:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $dateValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $dateValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($dateValue)) {
                $dateValues = $this->parseMultipleValues($dateValue); // Пробелы и запятые
                foreach ($dateValues as $date) {
                    if (!$this->isEmpty($date)) {
                        // Если в дате нет года, добавляем текущий год
                        if (preg_match('/^\d{1,2}\.\d{1,2}$/', $date)) {
                            $currentYear = date('Y');
                            $dates[] = $date . '.' . $currentYear;
                        } else {
                            $dates[] = $date;
                        }
                    } else {
                        $dates[] = null; // Бесконечность
                    }
                }
            } else {
                $dates = [null]; // Значение по умолчанию для пустого поля
            }
        }

        // Определяем количество записей
        $count = count($caps);

        // Если у необязательного поля только одно значение, применяем его ко всем записям
        if (count($languages) === 1) {
            $languages = array_fill(0, $count, $languages[0]);
        }
        if (count($funnels) === 1) {
            $funnels = array_fill(0, $count, $funnels[0]);
        }
        if (count($tests) === 1) {
            $tests = array_fill(0, $count, $tests[0]);
        }
        if (count($totals) === 1) {
            $totals = array_fill(0, $count, $totals[0]);
        }
        if (count($schedules) === 1) {
            $schedules = array_fill(0, $count, $schedules[0]);
        }
        if (count($pendingAcqs) === 1) {
            $pendingAcqs = array_fill(0, $count, $pendingAcqs[0]);
        }
        if (count($freezeStatuses) === 1) {
            $freezeStatuses = array_fill(0, $count, $freezeStatuses[0]);
        }
        if (count($dates) === 1) {
            $dates = array_fill(0, $count, $dates[0]);
        }

        // Создаем одну запись с массивами cap_amounts и geos
        $combinations = [];
        
        // Берем значения по индексу или значения по умолчанию
        $language = isset($languages[0]) ? $languages[0] : 'en';
        $funnel = isset($funnels[0]) ? $funnels[0] : null;
        $test = isset($tests[0]) ? $tests[0] : null;
        $total = isset($totals[0]) ? $totals[0] : -1; // Бесконечность
        $schedule = isset($schedules[0]) ? $schedules[0] : '24/7';
        $pendingAcq = isset($pendingAcqs[0]) ? $pendingAcqs[0] : false;
        $freezeStatus = isset($freezeStatuses[0]) ? $freezeStatuses[0] : false;
        $date = isset($dates[0]) ? $dates[0] : null;

        // Парсим время из расписания
        $scheduleData = $this->parseScheduleTime($schedule);

        $combination = [
            'affiliate_name' => $affiliate,
            'recipient_name' => $recipient,
            'cap_amounts' => $caps, // Массив всех cap amounts
            'geos' => $geos, // Массив всех geos
            'language' => $language,
            'funnel' => $funnel,
            'test' => $test,
            'total_amount' => $total,
            'schedule' => $scheduleData['schedule'],
            'work_hours' => $scheduleData['work_hours'],
            'is_24_7' => $scheduleData['is_24_7'],
            'start_time' => $scheduleData['start_time'],
            'end_time' => $scheduleData['end_time'],
            'timezone' => $scheduleData['timezone'],
            'date' => $date,
            'pending_acq' => $pendingAcq,
            'freeze_status_on_acq' => $freezeStatus
        ];

        $combinations[] = $combination;

        return $combinations;
    }

    /**
     * Поиск кап из базы данных (RUN и STOP по умолчанию, скрываем DELETE)
     */
    public function searchCaps($search = null, $chatId = null)
    {
        $caps = Cap::searchCaps($search, $chatId, false)->get(); // false = включаем STOP
        
        $results = [];
        foreach ($caps as $cap) {
            $results[] = [
                'id' => $cap->message->id . '_' . $cap->id,
                'message' => $cap->message->message,
                'user' => $cap->message->display_name,
                'chat_name' => $cap->message->chat->display_name ?? 'Неизвестный чат',
                'timestamp' => $cap->created_at->format('d.m.Y H:i'),
                'analysis' => [
                    'has_cap_word' => true,
                    'cap_amounts' => $cap->cap_amounts,
                    'total_amount' => $cap->total_amount,
                    'schedule' => $cap->schedule,
                    'start_time' => $cap->start_time,
                    'end_time' => $cap->end_time,
                    'timezone' => $cap->timezone,
                    'date' => $cap->date,
                    'is_24_7' => $cap->is_24_7,
                    'affiliate_name' => $cap->affiliate_name,
                    'recipient_name' => $cap->recipient_name,
                    'geos' => $cap->geos ?? [],
                    'work_hours' => $cap->work_hours,
                    'language' => $cap->language,
                    'funnel' => $cap->funnel,
                    'pending_acq' => $cap->pending_acq,
                    'freeze_status_on_acq' => $cap->freeze_status_on_acq,
                    'highlighted_text' => $cap->highlighted_text,
                    'status' => $cap->status,
                    'status_updated_at' => $cap->status_updated_at?->format('d.m.Y H:i')
                ]
            ];
        }
        
        return $results;
    }

    /**
     * Поиск кап с дополнительными фильтрами
     */
    public function searchCapsWithFilters($search = null, $chatId = null, $filters = [])
    {
        $query = Cap::with(['message' => function($q) {
            $q->with('chat');
        }]);

        // Фильтр по статусу (по умолчанию RUN и STOP, скрываем DELETE)
        if (isset($filters['status']) && !empty($filters['status'])) {
            if ($filters['status'] === 'all') {
                // Показать все включая удаленные
                // Не добавляем дополнительных условий
            } elseif (in_array($filters['status'], ['RUN', 'STOP', 'DELETE'])) {
                $query->where('status', $filters['status']);
            }
        } else {
            // По умолчанию показываем активные и остановленные, скрываем удаленные
            $query->whereIn('status', ['RUN', 'STOP']);
        }

        // Фильтр по чату
        if ($chatId) {
            $query->whereHas('message', function($q) use ($chatId) {
                $q->where('chat_id', $chatId);
            });
        }

        // Поиск по тексту
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('affiliate_name', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('schedule', 'LIKE', "%{$search}%")
                  ->orWhere('language', 'LIKE', "%{$search}%")
                  ->orWhere('funnel', 'LIKE', "%{$search}%")
                  ->orWhereHas('message', function($subQ) use ($search) {
                      $subQ->where('message', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Фильтр по гео
        if (!empty($filters['geo'])) {
            $query->whereJsonContains('geos', $filters['geo']);
        }

        // Фильтр по получателю
        if (!empty($filters['recipient'])) {
            $query->where('recipient_name', $filters['recipient']);
        }

        // Фильтр по аффилейту
        if (!empty($filters['affiliate'])) {
            $query->where('affiliate_name', $filters['affiliate']);
        }

        // Фильтр по языку
        if (!empty($filters['language'])) {
            $query->where('language', $filters['language']);
        }

        // Фильтр по воронке
        if (!empty($filters['funnel'])) {
            $query->where('funnel', $filters['funnel']);
        }

        // Фильтр по pending ACQ
        if (isset($filters['pending_acq'])) {
            $query->where('pending_acq', $filters['pending_acq']);
        }

        // Фильтр по freeze status
        if (isset($filters['freeze_status_on_acq'])) {
            $query->where('freeze_status_on_acq', $filters['freeze_status_on_acq']);
        }

        // Фильтр по расписанию
        if (!empty($filters['schedule'])) {
            switch ($filters['schedule']) {
                case 'has_schedule':
                    $query->whereNotNull('schedule')
                          ->where('schedule', '!=', '24/7');
                    break;
                case '24_7':
                    $query->where('is_24_7', true);
                    break;
            }
        }

        // Фильтр по общему лимиту
        if (!empty($filters['total'])) {
            switch ($filters['total']) {
                case 'has_total':
                    $query->whereNotNull('total_amount')
                          ->where('total_amount', '!=', -1);
                    break;
                case 'infinity':
                    $query->where('total_amount', -1);
                    break;
            }
        }

        $caps = $query->orderBy('created_at', 'desc')->get();
        
        $results = [];
        foreach ($caps as $cap) {
            $results[] = [
                'id' => $cap->message->id . '_' . $cap->id,
                'message' => $cap->message->message,
                'user' => $cap->message->display_name,
                'chat_name' => $cap->message->chat->display_name ?? 'Неизвестный чат',
                'timestamp' => $cap->created_at->format('d.m.Y H:i'),
                'analysis' => [
                    'has_cap_word' => true,
                    'cap_amounts' => $cap->cap_amounts,
                    'total_amount' => $cap->total_amount,
                    'schedule' => $cap->schedule,
                    'start_time' => $cap->start_time,
                    'end_time' => $cap->end_time,
                    'timezone' => $cap->timezone,
                    'date' => $cap->date,
                    'is_24_7' => $cap->is_24_7,
                    'affiliate_name' => $cap->affiliate_name,
                    'recipient_name' => $cap->recipient_name,
                    'geos' => $cap->geos ?? [],
                    'work_hours' => $cap->work_hours,
                    'language' => $cap->language,
                    'funnel' => $cap->funnel,
                    'pending_acq' => $cap->pending_acq,
                    'freeze_status_on_acq' => $cap->freeze_status_on_acq,
                    'highlighted_text' => $cap->highlighted_text,
                    'status' => $cap->status,
                    'status_updated_at' => $cap->status_updated_at?->format('d.m.Y H:i')
                ]
            ];
        }
        
        return $results;
    }

    /**
     * Получение списков для фильтров
     */
    public function getFilterOptions()
    {
        // Получаем опции из активных и остановленных кап (исключаем удаленные)
        $caps = Cap::whereIn('status', ['RUN', 'STOP'])
                   ->whereNotNull('geos')
                   ->orWhereNotNull('recipient_name')
                   ->orWhereNotNull('affiliate_name')
                   ->orWhereNotNull('language')
                   ->orWhereNotNull('funnel')
                   ->get();

        $geos = [];
        $recipients = [];
        $affiliates = [];
        $languages = [];
        $funnels = [];

        foreach ($caps as $cap) {
            // Собираем гео
            if ($cap->geos) {
                $geos = array_merge($geos, $cap->geos);
            }

            // Собираем получателей
            if ($cap->recipient_name) {
                $recipients[] = $cap->recipient_name;
            }

            // Собираем аффилейтов
            if ($cap->affiliate_name) {
                $affiliates[] = $cap->affiliate_name;
            }

            // Собираем языки
            if ($cap->language) {
                $languages[] = $cap->language;
            }

            // Собираем воронки
            if ($cap->funnel) {
                $funnels[] = $cap->funnel;
            }
        }

        return [
            'geos' => array_values(array_unique($geos)),
            'recipients' => array_values(array_unique($recipients)),
            'affiliates' => array_values(array_unique($affiliates)),
            'languages' => array_values(array_unique($languages)),
            'funnels' => array_values(array_unique($funnels)),
            'statuses' => [
                ['value' => '', 'label' => 'Все (кроме удаленных)'],
                ['value' => 'RUN', 'label' => 'Активные'],
                ['value' => 'STOP', 'label' => 'Остановленные'], 
                ['value' => 'DELETE', 'label' => 'Корзина (удаленные)'],
                ['value' => 'all', 'label' => 'Все (включая удаленные)']
            ]
        ];
    }

    /**
     * Анализирует сообщение на наличие кап (без сохранения) - для обратной совместимости
     */
    public function analyzeCapMessage($message)
    {
        if ($this->isStandardCapMessage($message)) {
            $capCombinations = $this->parseStandardCapMessage($message);
            
            if ($capCombinations && is_array($capCombinations) && count($capCombinations) > 0) {
                // Собираем все cap amounts и geos из всех комбинаций
                $allCapAmounts = [];
                $allGeos = [];
                
                foreach ($capCombinations as $capData) {
                    $allCapAmounts[] = $capData['cap_amount'];
                    $allGeos = array_merge($allGeos, $capData['geos']);
                }
                
                // Возвращаем данные первой комбинации с объединенными массивами для обратной совместимости
                $firstCapData = $capCombinations[0];
                
                return [
                    'has_cap_word' => true,
                    'cap_amount' => $firstCapData['cap_amount'],
                    'cap_amounts' => $allCapAmounts, // Все cap amounts
                    'total_amount' => $firstCapData['total_amount'],
                    'schedule' => $firstCapData['schedule'],
                    'start_time' => $firstCapData['start_time'],
                    'end_time' => $firstCapData['end_time'],
                    'timezone' => $firstCapData['timezone'],
                    'date' => $firstCapData['date'],
                    'is_24_7' => $firstCapData['is_24_7'],
                    'affiliate_name' => $firstCapData['affiliate_name'],
                    'recipient_name' => $firstCapData['recipient_name'],
                    'geos' => array_unique($allGeos), // Все уникальные geos
                    'work_hours' => $firstCapData['work_hours'],
                    'language' => $firstCapData['language'],
                    'funnel' => $firstCapData['funnel'],
                    'pending_acq' => $firstCapData['pending_acq'],
                    'freeze_status_on_acq' => $firstCapData['freeze_status_on_acq'],
                    'raw_numbers' => $allCapAmounts // Все cap amounts
                ];
            }
        }
        
        return [
            'has_cap_word' => false,
            'cap_amount' => null,
            'cap_amounts' => [],
            'total_amount' => null,
            'schedule' => null,
            'start_time' => null,
            'end_time' => null,
            'timezone' => null,
            'date' => null,
            'is_24_7' => false,
            'affiliate_name' => null,
            'recipient_name' => null,
            'geos' => [],
            'work_hours' => null,
            'language' => null,
            'funnel' => null,
            'pending_acq' => false,
            'freeze_status_on_acq' => false,
            'raw_numbers' => []
        ];
    }

    /**
     * Проверяет, является ли сообщение обновлением капы через reply
     */
    private function isUpdateCapMessage($messageText)
    {
        // Проверяем, что нет обязательных полей для создания новой капы
        $hasAffiliate = preg_match('/^affiliate:\s*(.*)$/m', $messageText);
        $hasRecipient = preg_match('/^recipient:\s*(.*)$/m', $messageText);
        
        // Это обновление капы если нет Affiliate или Recipient
        // Geo может быть необязателен, если в оригинальной капе только одно гео
        return (!$hasAffiliate || !$hasRecipient);
    }

    /**
     * Обрабатывает обновление капы через reply с поддержкой многоколоночных связей
     */
    private function processCapUpdate($messageId, $messageText, $currentMessage)
    {
        // Парсим множественные значения Geo из сообщения (как в первом методе)
        $geos = [];
        if (preg_match('/^geo:\s*(.*)$/m', $messageText, $matches)) {
            $geos = $this->parseMultipleValues($matches[1]);
        }
        
        // Ищем изначальную капу через цепочку reply_to_message
        $originalCap = $this->findOriginalCap($currentMessage->reply_to_message_id);
        
        if (!$originalCap) {
            return ['cap_entries_count' => 0, 'error' => 'Капа не найдена в сообщении, на которое отвечаете'];
        }
        
        // Проверяем, что капа имеет подходящий статус
        if (!in_array($originalCap->status, ['RUN', 'STOP'])) {
            return ['cap_entries_count' => 0, 'error' => 'Нельзя обновить капу со статусом ' . $originalCap->status];
        }
        
        // Определяем, есть ли пустые значения в сообщении
        $hasEmptyValues = $this->hasEmptyFieldValues($messageText);
        
        // Получаем все капы для этого сообщения для дальнейшего анализа
        $allCaps = Cap::where('message_id', $this->findOriginalMessageId($currentMessage->reply_to_message_id))
                      ->whereIn('status', ['RUN', 'STOP'])
                      ->get();
        
        // Проверяем, есть ли в сообщении больше одной капы
        $hasMultipleCaps = $allCaps->count() > 1;
        
        // Проверяем, есть ли параметры с одним значением (не пустые)
        $hasSingleValueParams = $this->hasSingleValueParams($messageText);
        
        // Если есть пустые значения и Geo не указаны, обновляем все капы в сообщении
        if ($hasEmptyValues && empty($geos)) {
            $allGeos = [];
            foreach ($allCaps as $cap) {
                $allGeos = array_merge($allGeos, $cap->geos);
            }
            $geos = array_unique($allGeos);
        }
        // Если Geo не указан, больше одной капы в сообщении и есть параметр с одним значением -> применить ко всем капам
        elseif (empty($geos) && $hasMultipleCaps && $hasSingleValueParams) {
            $allGeos = [];
            foreach ($allCaps as $cap) {
                $allGeos = array_merge($allGeos, $cap->geos);
            }
            $geos = array_unique($allGeos);
        }
        // Если нет пустых значений и Geo не указаны, используем логику как раньше
        elseif (empty($geos) && count($originalCap->geos) === 1) {
            $geos = $originalCap->geos;
        }
        elseif (empty($geos) && count($originalCap->geos) > 1) {
            return ['cap_entries_count' => 0, 'error' => 'Geo обязателен для обновления капы с несколькими гео'];
        }
        
        // Парсим остальные поля (множественные значения)
        $caps = [];
        $languages = [];
        $funnels = [];
        $totals = [];
        $schedules = [];
        $pendingAcqs = [];
        $freezeStatuses = [];
        $dates = [];

        // Cap
        if (preg_match('/^cap:\s*(.*)$/mi', $messageText, $matches)) {
            $capValues = $this->parseMultipleValues($matches[1]);
            foreach ($capValues as $cap) {
                if (is_numeric($cap)) {
                    $caps[] = intval($cap);
                }
            }
        }
        
        // Total
        if (preg_match_all('/^total:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $totalValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $totalValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($totalValue)) {
                $totalValues = $this->parseMultipleValues($totalValue);
                foreach ($totalValues as $total) {
                    if (is_numeric($total)) {
                        $totals[] = intval($total);
            } else {
                        $totals[] = -1; // Бесконечность для нечисловых значений
                    }
                }
            } else {
                $totals = [-1]; // Значение по умолчанию для пустого поля
            }
        }
        
        // Schedule
        if (preg_match_all('/^schedule:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $scheduleValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $scheduleValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($scheduleValue)) {
                $schedules = $this->parseScheduleValues($scheduleValue);
            } else {
                $schedules = ['24/7'];
            }
        }
        
        // Date
        if (preg_match_all('/^Date:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $dateValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $dateValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($dateValue)) {
                $dateValues = $this->parseMultipleValues($dateValue);
                foreach ($dateValues as $date) {
                    if (!$this->isEmpty($date)) {
                        // Если в дате нет года, добавляем текущий год
                        if (preg_match('/^\d{1,2}\.\d{1,2}$/', $date)) {
                            $currentYear = date('Y');
                            $dates[] = $date . '.' . $currentYear;
                        } else {
                            $dates[] = $date;
                        }
                    } else {
                        $dates[] = null;
                    }
                }
            } else {
                $dates = [null];
            }
        }
        
        // Language
        if (preg_match_all('/^language:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $languageValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $languageValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($languageValue)) {
                $languages = $this->parseMultipleValues($languageValue);
            } else {
                $languages = ['en'];
            }
        }
        
        // Funnel
        if (preg_match_all('/^funnel:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $funnelValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $funnelValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($funnelValue)) {
                $funnels = $this->parseMultipleValues($funnelValue, true); // Только запятые
            } else {
                $funnels = [null];
            }
        }
        
        // Test
        if (preg_match_all('/^test:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $testValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $testValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($testValue)) {
                $tests = $this->parseMultipleValues($testValue, true); // Только запятые
            } else {
                $tests = [null];
            }
        }
        
        // Pending ACQ
        if (preg_match_all('/^pending acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $pendingValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $pendingValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($pendingValue)) {
                $pendingValues = $this->parseMultipleValues($pendingValue, true);
                foreach ($pendingValues as $pending) {
                    $pendingAcqs[] = in_array(strtolower($pending), ['yes', 'true', '1', 'да']);
                }
            } else {
                $pendingAcqs = [false];
            }
        }
        
        // Freeze status on ACQ
        if (preg_match_all('/^freeze status on acq:\s*([^:\n\r]*?)(?:\s*\w+:|$)/m', $messageText, $matches)) {
            $freezeValue = null;
            foreach ($matches[1] as $match) {
                $trimmed = trim($match);
                if (!$this->isEmpty($trimmed)) {
                    $freezeValue = $trimmed;
                    break;
                }
            }
            
            if (!$this->isEmpty($freezeValue)) {
                $freezeValues = $this->parseMultipleValues($freezeValue, true);
                foreach ($freezeValues as $freeze) {
                    $freezeStatuses[] = in_array(strtolower($freeze), ['yes', 'true', '1', 'да']);
                }
            } else {
                $freezeStatuses = [false];
            }
        }
        
        // Проверяем количество элементов
        // Разрешаем один Cap для всех Geo (применяется ко всем капам)
        if (!empty($caps) && count($caps) !== count($geos) && count($caps) !== 1) {
            return ['cap_entries_count' => 0, 'error' => 'Количество значений Cap должно совпадать с количеством Geo или быть равно 1 (для применения ко всем)'];
        }
        
        $updatedCount = 0;
        $createdCount = 0;
        $messages = [];
        
        // Определяем количество записей для обновления
        $count = count($geos);
        
        // Если у необязательного поля только одно значение, применяем его ко всем записям
        // Или если это сброс для всех кап в сообщении
        // Или если это обновление одного параметра для всех кап
        if (count($caps) === 1 || ($hasEmptyValues && empty($caps)) || ($hasMultipleCaps && $hasSingleValueParams && count($caps) === 1)) {
            $caps = count($caps) > 0 ? array_fill(0, $count, $caps[0]) : [];
        }
        if (count($languages) === 1 || ($hasEmptyValues && count($languages) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($languages) === 1)) {
            $languages = count($languages) > 0 ? array_fill(0, $count, $languages[0]) : [];
        }
        if (count($funnels) === 1 || ($hasEmptyValues && count($funnels) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($funnels) === 1)) {
            $funnels = count($funnels) > 0 ? array_fill(0, $count, $funnels[0]) : [];
        }
        if (count($tests) === 1 || ($hasEmptyValues && count($tests) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($tests) === 1)) {
            $tests = count($tests) > 0 ? array_fill(0, $count, $tests[0]) : [];
        }
        if (count($totals) === 1 || ($hasEmptyValues && count($totals) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($totals) === 1)) {
            $totals = count($totals) > 0 ? array_fill(0, $count, $totals[0]) : [];
        }
        if (count($schedules) === 1 || ($hasEmptyValues && count($schedules) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($schedules) === 1)) {
            $schedules = count($schedules) > 0 ? array_fill(0, $count, $schedules[0]) : [];
        }
        if (count($pendingAcqs) === 1 || ($hasEmptyValues && count($pendingAcqs) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($pendingAcqs) === 1)) {
            $pendingAcqs = count($pendingAcqs) > 0 ? array_fill(0, $count, $pendingAcqs[0]) : [];
        }
        if (count($freezeStatuses) === 1 || ($hasEmptyValues && count($freezeStatuses) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($freezeStatuses) === 1)) {
            $freezeStatuses = count($freezeStatuses) > 0 ? array_fill(0, $count, $freezeStatuses[0]) : [];
        }
        if (count($dates) === 1 || ($hasEmptyValues && count($dates) <= 1) || ($hasMultipleCaps && $hasSingleValueParams && count($dates) === 1)) {
            $dates = count($dates) > 0 ? array_fill(0, $count, $dates[0]) : [];
        }
        
        // Обрабатываем каждое гео отдельно
        for ($i = 0; $i < $count; $i++) {
            $geo = $geos[$i];
            
            // Ищем капу для этого гео
            $existingCap = Cap::where('affiliate_name', $originalCap->affiliate_name)
                              ->where('recipient_name', $originalCap->recipient_name)
                              ->whereJsonContains('geos', $geo)
                              ->whereIn('status', ['RUN', 'STOP'])
                              ->first();
                              
            if (!$existingCap) {
                // Капа для этого гео не найдена - создаем новую на основе исходной капы
                $newCapData = [
                    'message_id' => $originalCap->message_id, // Привязываем к исходному сообщению
                    'cap_amounts' => [isset($caps[$i]) ? $caps[$i] : 
                                     (is_array($originalCap->cap_amounts) && !empty($originalCap->cap_amounts) ? $originalCap->cap_amounts[0] : 0)],
                    'total_amount' => isset($totals[$i]) ? $totals[$i] : $originalCap->total_amount,
                    'affiliate_name' => $originalCap->affiliate_name,
                    'recipient_name' => $originalCap->recipient_name,
                    'geos' => [$geo], // Новое гео
                    'language' => isset($languages[$i]) ? $languages[$i] : $originalCap->language,
                    'funnel' => isset($funnels[$i]) ? $funnels[$i] : $originalCap->funnel,
                    'test' => isset($tests[$i]) ? $tests[$i] : $originalCap->test,
                    'date' => isset($dates[$i]) ? $dates[$i] : $originalCap->date,
                    'pending_acq' => isset($pendingAcqs[$i]) ? $pendingAcqs[$i] : $originalCap->pending_acq,
                    'freeze_status_on_acq' => isset($freezeStatuses[$i]) ? $freezeStatuses[$i] : $originalCap->freeze_status_on_acq,
                    'status' => 'RUN',
                    'status_updated_at' => now()
                ];
                
                // Обрабатываем расписание для новой капы
                if (isset($schedules[$i])) {
                    $scheduleData = $this->parseScheduleTime($schedules[$i]);
                    $newCapData['schedule'] = $scheduleData['schedule'];
                    $newCapData['work_hours'] = $scheduleData['work_hours'];
                    $newCapData['is_24_7'] = $scheduleData['is_24_7'];
                    $newCapData['start_time'] = $scheduleData['start_time'];
                    $newCapData['end_time'] = $scheduleData['end_time'];
                    $newCapData['timezone'] = $scheduleData['timezone'];
                } else {
                    $newCapData['schedule'] = $originalCap->schedule;
                    $newCapData['work_hours'] = $originalCap->work_hours;
                    $newCapData['is_24_7'] = $originalCap->is_24_7;
                    $newCapData['start_time'] = $originalCap->start_time;
                    $newCapData['end_time'] = $originalCap->end_time;
                    $newCapData['timezone'] = $originalCap->timezone;
                }
                
                $newCapData['highlighted_text'] = $messageText;
                
                Cap::create($newCapData);
                $createdCount++;
                $messages[] = "Создана новая капа для гео {$geo}";
                continue;
            }
            
            // Проверяем что это та же цепочка сообщений
            $foundOriginalCap = $this->findOriginalCap($existingCap->message_id);
            if (!$foundOriginalCap || $foundOriginalCap->id !== $originalCap->id) {
                $messages[] = "Капа для гео {$geo} не относится к этой цепочке сообщений";
                continue;
            }
            
            // Создаем данные для обновления
            $updateCapData = [
                'cap_amount' => isset($caps[$i]) ? $caps[$i] : 
                               (is_array($existingCap->cap_amounts) && !empty($existingCap->cap_amounts) ? $existingCap->cap_amounts[0] : 0),
                'total_amount' => isset($totals[$i]) ? $totals[$i] : $existingCap->total_amount,
                'language' => isset($languages[$i]) ? $languages[$i] : $existingCap->language,
                'funnel' => isset($funnels[$i]) ? $funnels[$i] : $existingCap->funnel,
                'test' => isset($tests[$i]) ? $tests[$i] : $existingCap->test,
                'date' => isset($dates[$i]) ? $dates[$i] : $existingCap->date,
                'pending_acq' => isset($pendingAcqs[$i]) ? $pendingAcqs[$i] : $existingCap->pending_acq,
                'freeze_status_on_acq' => isset($freezeStatuses[$i]) ? $freezeStatuses[$i] : $existingCap->freeze_status_on_acq,
            ];
            
            // Если поле указано как пустое в сообщении, применяем значение по умолчанию
            if ($hasEmptyValues) {
                if ($this->isFieldSpecifiedInMessage($messageText, 'language') && $this->isEmpty($this->getRawFieldValue($messageText, 'language'))) {
                    $updateCapData['language'] = 'en'; // Значение по умолчанию
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'funnel') && $this->isEmpty($this->getRawFieldValue($messageText, 'funnel'))) {
                    $updateCapData['funnel'] = null; // Значение по умолчанию
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'test') && $this->isEmpty($this->getRawFieldValue($messageText, 'test'))) {
                    $updateCapData['test'] = null; // Значение по умолчанию
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'date') && $this->isEmpty($this->getRawFieldValue($messageText, 'date'))) {
                    $updateCapData['date'] = null; // Значение по умолчанию
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'total') && $this->isEmpty($this->getRawFieldValue($messageText, 'total'))) {
                    $updateCapData['total_amount'] = -1; // Значение по умолчанию (бесконечность)
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'pending_acq') && $this->isEmpty($this->getRawFieldValue($messageText, 'pending_acq'))) {
                    $updateCapData['pending_acq'] = false; // Значение по умолчанию
                }
                if ($this->isFieldSpecifiedInMessage($messageText, 'freeze_status_on_acq') && $this->isEmpty($this->getRawFieldValue($messageText, 'freeze_status_on_acq'))) {
                    $updateCapData['freeze_status_on_acq'] = false; // Значение по умолчанию
                }
            }
            
            // Обрабатываем расписание
            if (isset($schedules[$i])) {
                $scheduleData = $this->parseScheduleTime($schedules[$i]);
                $updateCapData['schedule'] = $scheduleData['schedule'];
                $updateCapData['work_hours'] = $scheduleData['work_hours'];
                $updateCapData['is_24_7'] = $scheduleData['is_24_7'];
                $updateCapData['start_time'] = $scheduleData['start_time'];
                $updateCapData['end_time'] = $scheduleData['end_time'];
                $updateCapData['timezone'] = $scheduleData['timezone'];
            } elseif ($hasEmptyValues && $this->isFieldSpecifiedInMessage($messageText, 'schedule') && $this->isEmpty($this->getRawFieldValue($messageText, 'schedule'))) {
                // Если это сброс расписания, устанавливаем значения по умолчанию
                $updateCapData['schedule'] = '24/7';
                $updateCapData['work_hours'] = '24/7';
                $updateCapData['is_24_7'] = true;
                $updateCapData['start_time'] = null;
                $updateCapData['end_time'] = null;
                $updateCapData['timezone'] = null;
            } else {
                $updateCapData['schedule'] = $existingCap->schedule;
                $updateCapData['work_hours'] = $existingCap->work_hours;
                $updateCapData['is_24_7'] = $existingCap->is_24_7;
                $updateCapData['start_time'] = $existingCap->start_time;
                $updateCapData['end_time'] = $existingCap->end_time;
                $updateCapData['timezone'] = $existingCap->timezone;
        }
        
        // Определяем какие поля нужно обновить
        $updateData = $this->getFieldsToUpdate($existingCap, $updateCapData, $geo, $messageText, $messageText);
        
        if (!empty($updateData)) {
            CapHistory::createFromCap($existingCap);
            $existingCap->update($updateData);
                $updatedCount++;
                $messages[] = "Капа для гео {$geo} обновлена";
            }
        }
            
        if ($updatedCount > 0 || $createdCount > 0) {
            return [
                'cap_entries_count' => $createdCount,
                'updated_entries_count' => $updatedCount,
                'total_processed' => $createdCount + $updatedCount,
                'message' => implode('. ', $messages)
            ];
        }
        
        return ['cap_entries_count' => 0, 'updated_entries_count' => 0, 'message' => 'Нет изменений для обновления'];
    }

    /**
     * Проверяет, есть ли пустые значения в сообщении для полей, которые могут быть сброшены
     */
    private function hasEmptyFieldValues($messageText)
    {
        $fieldsToCheck = [
            'schedule' => '/^schedule:\s*(.*)$/m',
            'date' => '/^date:\s*(.*)$/m',
            'language' => '/^language:\s*(.*)$/m',
            'funnel' => '/^funnel:\s*(.*)$/m',
            'total' => '/^total:\s*(.*)$/m',
            'pending_acq' => '/^pending acq:\s*(.*)$/m',
            'freeze_status_on_acq' => '/^freeze status on acq:\s*(.*)$/m'
        ];
        
        foreach ($fieldsToCheck as $field => $pattern) {
            if (preg_match($pattern, $messageText, $matches)) {
                $value = trim($matches[1]);
                if ($this->isEmpty($value)) {
                    return true; // Найдено пустое значение
                }
            }
        }
        
        return false;
    }

    /**
     * Проверяет, есть ли в сообщении параметры с одним значением (не пустые)
     */
    private function hasSingleValueParams($messageText)
    {
        $fieldsToCheck = [
            'cap' => '/^cap:\s*(.+)$/mi',
            'schedule' => '/^schedule:\s*(.+)$/m',
            'date' => '/^date:\s*(.+)$/m',
            'language' => '/^language:\s*(.+)$/m',
            'funnel' => '/^funnel:\s*(.+)$/m',
            'total' => '/^total:\s*(.+)$/m',
            'pending_acq' => '/^pending acq:\s*(.+)$/m',
            'freeze_status_on_acq' => '/^freeze status on acq:\s*(.+)$/m'
        ];
        
        foreach ($fieldsToCheck as $field => $pattern) {
            if (preg_match($pattern, $messageText, $matches)) {
                $value = trim($matches[1]);
                if (!$this->isEmpty($value)) {
                    // Проверяем, что это одно значение (не множественное)
                    $values = $this->parseMultipleValues($value, $field === 'funnel');
                    if (count($values) === 1) {
                        return true; // Найден параметр с одним значением
                    }
                }
            }
        }
        
        return false;
    }

    /**
     * Находит ID оригинального сообщения с капами через цепочку reply_to_message
     */
    private function findOriginalMessageId($messageId)
    {
        $currentMessageId = $messageId;
        $visited = [];
        
        // Ограничиваем глубину поиска
        $maxDepth = 20;
        $depth = 0;
        
        while ($currentMessageId && $depth < $maxDepth) {
            if (in_array($currentMessageId, $visited)) {
                break;
            }
            
            $visited[] = $currentMessageId;
            
            // Проверяем, есть ли капы для этого сообщения
            $hasCaps = Cap::where('message_id', $currentMessageId)->exists();
            if ($hasCaps) {
                return $currentMessageId;
            }
            
            // Ищем родительское сообщение
            $message = Message::where('message_id', $currentMessageId)->first();
            if (!$message || !$message->reply_to_message_id) {
                break;
            }
            
            $currentMessageId = $message->reply_to_message_id;
            $depth++;
        }
        
        return null;
    }
} 