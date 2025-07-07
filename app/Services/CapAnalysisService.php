<?php

namespace App\Services;

use App\Models\Cap;
use App\Models\Message;

class CapAnalysisService
{
    /**
     * Анализирует сообщение на наличие кап и сохраняет в таблицу
     */
    public function analyzeAndSaveCapMessage($messageId, $messageText)
    {
        // Удаляем старые записи для этого сообщения
        Cap::where('message_id', $messageId)->delete();
        
        // Проверяем, является ли сообщение стандартной капой
        if (!$this->isStandardCapMessage($messageText)) {
            return ['cap_entries_count' => 0];
        }
        
        // Парсим стандартное сообщение
        $capCombinations = $this->parseStandardCapMessage($messageText);
        
        if ($capCombinations && is_array($capCombinations)) {
            $createdCount = 0;
            
            foreach ($capCombinations as $capData) {
            Cap::create([
                'message_id' => $messageId,
                    'cap_amounts' => [$capData['cap_amount']],
                    'total_amount' => $capData['total_amount'],
                    'schedule' => $capData['schedule'],
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
                    'highlighted_text' => $messageText
                ]);
                $createdCount++;
            }
            
            return ['cap_entries_count' => $createdCount];
        }
        
        return ['cap_entries_count' => 0];
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
        
        // Разбиваем по запятым сначала
        $segments = explode(',', $value);
        
        foreach ($segments as $segment) {
            $segment = trim($segment);
            
            // Ищем паттерн времени с возможным GMT
            // Примеры: "18:00/01:00", "18:00/02:00 GMT+03:00", "24/7"
            if (preg_match('/(\d{1,2}:\d{2}\/\d{1,2}:\d{2})\s*(GMT[+-]\d{1,2}:\d{2})?/i', $segment, $matches)) {
                // Время найдено, добавляем как один элемент
                $schedules[] = trim($matches[0]);
            } else if (preg_match('/24\/7|24-7/i', $segment)) {
                // 24/7 найдено
                $schedules[] = '24/7';
            } else {
                // Разбиваем по пробелам для остальных случаев
                $parts = preg_split('/\s+/', $segment);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if (!$this->isEmpty($part)) {
                        // Проверяем, является ли это валидным расписанием
                        if (preg_match('/\d{1,2}:\d{2}\/\d{1,2}:\d{2}|24\/7|24-7/i', $part)) {
                            $schedules[] = $part;
                        }
                        // GMT отдельно игнорируем
                    }
                }
            }
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
        
        // Парсим время вида "18:00/01:00 GMT+03:00"
        if (preg_match('/(\d{1,2}:\d{2})\/(\d{1,2}:\d{2})\s*(GMT[+-]\d{1,2}:\d{2})?/i', $schedule, $matches)) {
            $timeOnly = $matches[1] . '/' . $matches[2]; // Только время без GMT
            return [
                'schedule' => $timeOnly,
                'work_hours' => $timeOnly,
                'is_24_7' => false,
                'start_time' => $matches[1],
                'end_time' => $matches[2],
                'timezone' => isset($matches[3]) ? $matches[3] : null
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

        if (preg_match('/^Language:\s*(.+)$/m', $messageText, $matches)) {
            $languages = $this->parseMultipleValues($matches[1]);
        }

        if (preg_match('/^Funnel:\s*(.+)$/m', $messageText, $matches)) {
            $funnels = $this->parseMultipleValues($matches[1], true); // Только запятые
        }

        if (preg_match('/^Total:\s*(.+)$/m', $messageText, $matches)) {
            $totalValues = $this->parseMultipleValues($matches[1]);
            foreach ($totalValues as $total) {
                if (is_numeric($total)) {
                    $totals[] = intval($total);
                } else {
                    $totals[] = -1; // Бесконечность для нечисловых значений
                }
            }
        }

        if (preg_match('/^Schedule:\s*(.+)$/m', $messageText, $matches)) {
            $schedules = $this->parseScheduleValues($matches[1]); // Специальный парсер для Schedule
        }

        if (preg_match('/^Pending ACQ:\s*(.+)$/m', $messageText, $matches)) {
            $pendingValues = $this->parseMultipleValues($matches[1], true); // Только запятые
            foreach ($pendingValues as $pending) {
                $pendingAcqs[] = in_array(strtolower($pending), ['yes', 'true', '1', 'да']);
            }
        }

        if (preg_match('/^Freeze status on ACQ:\s*(.+)$/m', $messageText, $matches)) {
            $freezeValues = $this->parseMultipleValues($matches[1], true); // Только запятые
            foreach ($freezeValues as $freeze) {
                $freezeStatuses[] = in_array(strtolower($freeze), ['yes', 'true', '1', 'да']);
            }
        }

        if (preg_match('/^Date:\s*(.+)$/m', $messageText, $matches)) {
            $dateValues = $this->parseMultipleValues($matches[1], true); // Только запятые
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
     * Поиск кап из базы данных
     */
    public function searchCaps($search = null, $chatId = null)
    {
        $caps = Cap::searchCaps($search, $chatId)->get();
        
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
                    'highlighted_text' => $cap->highlighted_text
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
                    'highlighted_text' => $cap->highlighted_text
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
        $caps = Cap::whereNotNull('geos')
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
            'funnels' => array_values(array_unique($funnels))
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