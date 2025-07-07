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
        
        // Проверяем, является ли сообщение стандартной капой
        if (!$this->isStandardCapMessage($messageText)) {
            return ['cap_entries_count' => 0];
        }
        
        // Парсим стандартное сообщение
        $capCombinations = $this->parseStandardCapMessage($messageText);
        
        if ($capCombinations && is_array($capCombinations)) {
            $createdCount = 0;
            $updatedCount = 0;
            
            // Получаем текущее сообщение для проверки reply_to_message
            $currentMessage = Message::find($messageId);
            
            foreach ($capCombinations as $capData) {
                // Поскольку теперь одна капа = одно гео, берем первое гео из массива
                $geo = $capData['geos'][0] ?? null;
                
                if (!$geo) {
                    continue; // Пропускаем если нет гео
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
                                     
                    // Проверяем что гео совпадает (это обязательное условие для обновления через reply)
                    if ($existingCap && !in_array($geo, $existingCap->geos)) {
                        $existingCap = null; // Сбрасываем если гео не совпадает
                    }
                }
                
                // Если капа не найдена через reply_to_message, ищем дубликат обычным способом
                if (!$existingCap) {
                    $existingCap = Cap::findDuplicate(
                        $capData['affiliate_name'],
                        $capData['recipient_name'], 
                        $geo
                    );
                }
                
                if ($existingCap) {
                    // Найден дубликат - определяем какие поля нужно обновить
                    $updateData = $this->getFieldsToUpdate($existingCap, $capData, $geo, $messageText, $messageText);
                    
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
                        'cap_amounts' => [$capData['cap_amount']],
                        'total_amount' => $capData['total_amount'],
                        'schedule' => $capData['schedule'],
                        'date' => $capData['date'],
                        'is_24_7' => $capData['is_24_7'],
                        'affiliate_name' => $capData['affiliate_name'],
                        'recipient_name' => $capData['recipient_name'],
                        'geos' => [$geo], // Одно гео
                        'work_hours' => $capData['work_hours'],
                        'language' => $capData['language'],
                        'funnel' => $capData['funnel'],
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
    private function getFieldsToUpdate($existingCap, $newCapData, $geo, $messageText, $originalMessage)
    {
        $updateData = [];
        
        // CAP amount - всегда обновляем если указан и не пустой (это обязательное поле)
        if (isset($newCapData['cap_amount']) && $newCapData['cap_amount'] > 0 && $existingCap->cap_amounts[0] != $newCapData['cap_amount']) {
            $updateData['cap_amounts'] = [$newCapData['cap_amount']];
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
        if (preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/i', trim($messageText))) {
            return true;
        }
        
        // Полные команды с полями
        return preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/m', $messageText) ||
               (preg_match('/^Affiliate:\s*(.+)$/m', $messageText) &&
                preg_match('/^Recipient:\s*(.+)$/m', $messageText) &&
                preg_match('/^Cap:\s*(.+)$/m', $messageText) &&
                preg_match('/^Geo:\s*(.+)$/m', $messageText) &&
                preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/m', $messageText));
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
     * Обрабатывает команды управления статусом (RUN/STOP/DELETE/RESTORE)
     */
    private function processStatusCommand($messageId, $messageText)
    {
        // Определяем команду
        $command = null;
        if (preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/i', trim($messageText))) {
            $command = strtoupper(trim($messageText));
        } elseif (preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/m', $messageText, $matches)) {
            $command = $matches[1];
        }

        if (!$command) {
            return ['cap_entries_count' => 0, 'error' => 'Неверная команда'];
        }

        // Получаем текущее сообщение для проверки reply_to_message
        $currentMessage = Message::find($messageId);
        if (!$currentMessage) {
            return ['cap_entries_count' => 0, 'error' => 'Сообщение не найдено'];
        }

        // Если это простая команда, ищем капу по reply_to_message
        if (preg_match('/^(RUN|STOP|DELETE|RESTORE)\s*$/i', trim($messageText))) {
            if (!$currentMessage->reply_to_message_id) {
                return ['cap_entries_count' => 0, 'error' => 'Команда должна быть ответом на сообщение с капой'];
            }

            // Определяем допустимые статусы для поиска в зависимости от команды
            $allowedStatuses = match($command) {
                'RUN' => ['STOP'], // Возобновить можно только остановленную
                'STOP' => ['RUN'], // Остановить можно только активную
                'DELETE' => ['RUN', 'STOP'], // Удалить можно активную или остановленную
                'RESTORE' => ['DELETE'], // Восстановить можно только удаленную
                default => ['RUN', 'STOP', 'DELETE']
            };

            // Ищем изначальную капу через цепочку reply_to_message
            $existingCap = $this->findOriginalCap($currentMessage->reply_to_message_id);
            
            // Проверяем, что капа найдена и имеет подходящий статус
            if ($existingCap && !in_array($existingCap->status, $allowedStatuses)) {
                $existingCap = null;
            }

            if (!$existingCap) {
                $statusText = implode(', ', $allowedStatuses);
                return ['cap_entries_count' => 0, 'error' => "Капа не найдена или имеет неподходящий статус. Для команды {$command} требуется статус: {$statusText}"];
            }

            // Создаем запись в истории
            CapHistory::createFromCap($existingCap);

            // Подготавливаем данные для обновления
            $updateData = [
                'status' => $command === 'RESTORE' ? 'RUN' : $command, // RESTORE меняет на RUN
                'status_updated_at' => now(),
                // НЕ обновляем message_id - он должен оставаться ID изначального сообщения с капой
            ];

            // При DELETE не меняем highlighted_text, для остальных команд обновляем
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
                'message' => "Капа {$existingCap->affiliate_name} → {$existingCap->recipient_name} ({$existingCap->geos[0]}, {$existingCap->cap_amounts[0]}) {$action}"
            ];
        }

        // Полная команда с указанием всех полей
        $affiliate = null;
        $recipient = null;
        $cap = null;
        $geo = null;

        if (preg_match('/^Affiliate:\s*(.+)$/m', $messageText, $matches)) {
            $affiliate = trim($matches[1]);
        }

        if (preg_match('/^Recipient:\s*(.+)$/m', $messageText, $matches)) {
            $recipient = trim($matches[1]);
        }

        if (preg_match('/^Cap:\s*(.+)$/m', $messageText, $matches)) {
            $capValue = trim($matches[1]);
            if (is_numeric($capValue)) {
                $cap = intval($capValue);
            }
        }

        if (preg_match('/^Geo:\s*(.+)$/m', $messageText, $matches)) {
            $geo = trim($matches[1]);
        }

        // Проверяем, что все обязательные поля заполнены
        if (!$affiliate || !$recipient || !$cap || !$geo) {
            return ['cap_entries_count' => 0, 'error' => 'Не все обязательные поля заполнены'];
        }

        // Определяем допустимые статусы для поиска в зависимости от команды
        $allowedStatuses = match($command) {
            'RUN' => ['STOP'], // Возобновить можно только остановленную
            'STOP' => ['RUN'], // Остановить можно только активную
            'DELETE' => ['RUN', 'STOP'], // Удалить можно активную или остановленную
            'RESTORE' => ['DELETE'], // Восстановить можно только удаленную
            default => ['RUN', 'STOP', 'DELETE']
        };

        // Ищем капу для изменения статуса
        $existingCap = Cap::where('affiliate_name', $affiliate)
                          ->where('recipient_name', $recipient)
                          ->whereJsonContains('geos', $geo)
                          ->whereJsonContains('cap_amounts', $cap)
                          ->whereIn('status', $allowedStatuses)
                          ->first();

        if (!$existingCap) {
            $statusText = implode(', ', $allowedStatuses);
            return ['cap_entries_count' => 0, 'error' => "Капа не найдена или имеет неподходящий статус. Для команды {$command} требуется статус: {$statusText}"];
        }

        // Создаем запись в истории перед изменением статуса
        CapHistory::createFromCap($existingCap);

        // Подготавливаем данные для обновления
        $updateData = [
            'status' => $command === 'RESTORE' ? 'RUN' : $command, // RESTORE меняет на RUN
            'status_updated_at' => now(),
            // НЕ обновляем message_id - он должен оставаться ID изначального сообщения с капой
        ];

        // При DELETE не меняем highlighted_text, для остальных команд обновляем
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
            'total' => '/^Total:\s*(.*)$/m',
            'schedule' => '/^Schedule:\s*(.*)$/m',
            'date' => '/^Date:\s*(.*)$/m',
            'language' => '/^Language:\s*(.*)$/m',
            'funnel' => '/^Funnel:\s*(.*)$/m',
            'pending_acq' => '/^Pending ACQ:\s*(.*)$/m',
            'freeze_status' => '/^Freeze status on ACQ:\s*(.*)$/m'
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
            'total' => '/^Total:\s*(.*)$/m',
            'schedule' => '/^Schedule:\s*(.*)$/m',
            'date' => '/^Date:\s*(.*)$/m',
            'language' => '/^Language:\s*(.*)$/m',
            'funnel' => '/^Funnel:\s*(.*)$/m',
            'pending_acq' => '/^Pending ACQ:\s*(.*)$/m',
            'freeze_status' => '/^Freeze status on ACQ:\s*(.*)$/m'
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
        $hasAffiliate = preg_match('/^Affiliate:\s*(.+)$/m', $messageText);
        $hasRecipient = preg_match('/^Recipient:\s*(.+)$/m', $messageText);
        $hasCap = preg_match('/^Cap:\s*(.+)$/m', $messageText);
        $hasGeo = preg_match('/^Geo:\s*(.+)$/m', $messageText);
        
        return $hasAffiliate && $hasRecipient && $hasCap && $hasGeo;
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
     * Специальный парсер для Schedule с учетом GMT
     */
    private function parseScheduleValues($value)
    {
        if ($this->isEmpty($value)) {
            return [];
        }
        
        $schedules = [];
        $value = trim($value);
        
        // Сначала разбиваем по запятым и пробелам
        $allParts = preg_split('/[,\s]+/', $value);
        
        $i = 0;
        while ($i < count($allParts)) {
            $part = trim($allParts[$i]);
            if ($this->isEmpty($part)) {
                $i++;
                continue;
            }
            
            // Проверяем на 24/7
            if (preg_match('/^(24\/7|24-7)$/i', $part)) {
                $schedules[] = '24/7';
                $i++;
                continue;
            }
            
            // Проверяем на время формата HH:MM/HH:MM
            if (preg_match('/^\d{1,2}:\d{2}\/\d{1,2}:\d{2}$/', $part)) {
                // Проверяем следующий элемент на GMT
                if ($i + 1 < count($allParts) && preg_match('/^GMT[+-]\d{1,2}:\d{2}$/i', $allParts[$i + 1])) {
                    $schedules[] = $part . ' ' . $allParts[$i + 1];
                    $i += 2; // Пропускаем GMT
                } else {
                    $schedules[] = $part;
                    $i++;
                }
                continue;
            }
            
            // Если это GMT без времени перед ним, игнорируем
            if (preg_match('/^GMT[+-]\d{1,2}:\d{2}$/i', $part)) {
                $i++;
                continue;
            }
            
            $i++;
        }
        
        return array_filter($schedules, function($val) {
            return !$this->isEmpty($val);
        });
    }

    /**
     * Парсит время из Schedule (например: "18:00/01:00 GMT+03:00")
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
        
        // Убираем лишние пробелы
        $schedule = preg_replace('/\s+/', ' ', $schedule);
        
        // Парсим время с GMT: "18:00/01:00 GMT+03:00"
        if (preg_match('/(\d{1,2}:\d{2})\/(\d{1,2}:\d{2})\s+(GMT[+-]\d{1,2}:\d{2})/i', $schedule, $matches)) {
            $timeOnly = $matches[1] . '/' . $matches[2];
            return [
                'schedule' => $timeOnly,
                'work_hours' => $timeOnly,
                'is_24_7' => false,
                'start_time' => $matches[1],
                'end_time' => $matches[2],
                'timezone' => $matches[3]
            ];
        }
        
        // Парсим время без GMT: "18:00/01:00"
        if (preg_match('/(\d{1,2}:\d{2})\/(\d{1,2}:\d{2})/', $schedule, $matches)) {
            $timeOnly = $matches[1] . '/' . $matches[2];
            return [
                'schedule' => $timeOnly,
                'work_hours' => $timeOnly,
                'is_24_7' => false,
                'start_time' => $matches[1],
                'end_time' => $matches[2],
                'timezone' => null
            ];
        }
        
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
        // Парсим аффилейта и получателя (обязательные единичные поля)
        $affiliate = null;
        $recipient = null;

        if (preg_match('/^Affiliate:\s*(.+)$/m', $messageText, $matches)) {
            $affiliateValue = trim($matches[1]);
            if (!$this->isEmpty($affiliateValue)) {
                $affiliate = $affiliateValue;
            }
        }

        if (preg_match('/^Recipient:\s*(.+)$/m', $messageText, $matches)) {
            $recipientValue = trim($matches[1]);
            if (!$this->isEmpty($recipientValue)) {
                $recipient = $recipientValue;
            }
        }

        // Проверяем обязательные единичные поля
        if (!$affiliate || !$recipient) {
            return null;
        }

        // Парсим списки Cap и Geo (обязательные)
        $caps = [];
        $geos = [];

        if (preg_match('/^Cap:\s*(.+)$/m', $messageText, $matches)) {
            $capValues = $this->parseMultipleValues($matches[1]);
            foreach ($capValues as $cap) {
                if (is_numeric($cap)) {
                    $caps[] = intval($cap);
                }
            }
        }

        if (preg_match('/^Geo:\s*(.+)$/m', $messageText, $matches)) {
            $geos = $this->parseMultipleValues($matches[1]);
        }

        // Проверяем, что Cap и Geo заполнены и их количество совпадает
        if (empty($caps) || empty($geos) || count($caps) !== count($geos)) {
            return null;
        }

        // Парсим необязательные списки
        $languages = [];
        $funnels = [];
        $totals = [];
        $schedules = [];
        $pendingAcqs = [];
        $freezeStatuses = [];
        $dates = [];

        if (preg_match('/^Language:\s*(.*)$/m', $messageText, $matches)) {
            $languageValue = trim($matches[1]);
            if (!$this->isEmpty($languageValue)) {
                $languages = $this->parseMultipleValues($languageValue);
            } else {
                $languages = ['en']; // Значение по умолчанию для пустого поля
            }
        }

        if (preg_match('/^Funnel:\s*(.*)$/m', $messageText, $matches)) {
            $funnelValue = trim($matches[1]);
            if (!$this->isEmpty($funnelValue)) {
                $funnels = $this->parseMultipleValues($funnelValue, true); // Только запятые
            } else {
                $funnels = [null]; // Значение по умолчанию для пустого поля
            }
        }

        if (preg_match('/^Total:\s*(.*)$/m', $messageText, $matches)) {
            $totalValue = trim($matches[1]);
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

        if (preg_match('/^Schedule:\s*(.*)$/m', $messageText, $matches)) {
            $scheduleValue = trim($matches[1]);
            if (!$this->isEmpty($scheduleValue)) {
                $schedules = $this->parseScheduleValues($scheduleValue); // Специальный парсер для Schedule
            } else {
                $schedules = ['24/7']; // Значение по умолчанию для пустого поля
            }
        }

        if (preg_match('/^Pending ACQ:\s*(.*)$/m', $messageText, $matches)) {
            $pendingValue = trim($matches[1]);
            if (!$this->isEmpty($pendingValue)) {
                $pendingValues = $this->parseMultipleValues($pendingValue, true); // Только запятые
                foreach ($pendingValues as $pending) {
                    $pendingAcqs[] = in_array(strtolower($pending), ['yes', 'true', '1', 'да']);
                }
            } else {
                $pendingAcqs = [false]; // Значение по умолчанию для пустого поля
            }
        }

        if (preg_match('/^Freeze status on ACQ:\s*(.*)$/m', $messageText, $matches)) {
            $freezeValue = trim($matches[1]);
            if (!$this->isEmpty($freezeValue)) {
                $freezeValues = $this->parseMultipleValues($freezeValue, true); // Только запятые
                foreach ($freezeValues as $freeze) {
                    $freezeStatuses[] = in_array(strtolower($freeze), ['yes', 'true', '1', 'да']);
                }
            } else {
                $freezeStatuses = [false]; // Значение по умолчанию для пустого поля
            }
        }

        if (preg_match('/^Date:\s*(.*)$/m', $messageText, $matches)) {
            $dateValue = trim($matches[1]);
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

        // Создаем записи с привязкой по порядку
        $combinations = [];
        for ($i = 0; $i < $count; $i++) {
            // Берем значения по индексу или значения по умолчанию
            $language = isset($languages[$i]) ? $languages[$i] : 'en';
            $funnel = isset($funnels[$i]) ? $funnels[$i] : null;
            $total = isset($totals[$i]) ? $totals[$i] : -1; // Бесконечность
            $schedule = isset($schedules[$i]) ? $schedules[$i] : '24/7';
            $pendingAcq = isset($pendingAcqs[$i]) ? $pendingAcqs[$i] : false;
            $freezeStatus = isset($freezeStatuses[$i]) ? $freezeStatuses[$i] : false;
            $date = isset($dates[$i]) ? $dates[$i] : null;

            // Парсим время из расписания
            $scheduleData = $this->parseScheduleTime($schedule);

            $combination = [
                'affiliate_name' => $affiliate,
                'recipient_name' => $recipient,
                'cap_amount' => $caps[$i],
                'geos' => [$geos[$i]],
                'language' => $language,
                'funnel' => $funnel,
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
        }

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
        if (isset($filters['status'])) {
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
                // Возвращаем данные первой комбинации для обратной совместимости
                $capData = $capCombinations[0];
                
                return [
                    'has_cap_word' => true,
                    'cap_amount' => $capData['cap_amount'],
                    'cap_amounts' => [$capData['cap_amount']],
                    'total_amount' => $capData['total_amount'],
                    'schedule' => $capData['schedule'],
                    'start_time' => $capData['start_time'],
                    'end_time' => $capData['end_time'],
                    'timezone' => $capData['timezone'],
                    'date' => $capData['date'],
                    'is_24_7' => $capData['is_24_7'],
                    'affiliate_name' => $capData['affiliate_name'],
                    'recipient_name' => $capData['recipient_name'],
                    'geos' => $capData['geos'],
                    'work_hours' => $capData['work_hours'],
                    'language' => $capData['language'],
                    'funnel' => $capData['funnel'],
                    'pending_acq' => $capData['pending_acq'],
                    'freeze_status_on_acq' => $capData['freeze_status_on_acq'],
                    'raw_numbers' => [$capData['cap_amount']]
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
} 