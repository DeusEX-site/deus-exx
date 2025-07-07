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
        $capData = $this->parseStandardCapMessage($messageText);
        
        if ($capData) {
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
            
            return ['cap_entries_count' => 1];
        }
        
        return ['cap_entries_count' => 0];
    }

    /**
     * Проверяет, является ли сообщение стандартной капой
     */
    private function isStandardCapMessage($messageText)
    {
        // Ищем ключевые поля: Affiliate, Recipient, Cap
        $hasAffiliate = preg_match('/^Affiliate:\s*(.+)$/m', $messageText);
        $hasRecipient = preg_match('/^Recipient:\s*(.+)$/m', $messageText);
        $hasCap = preg_match('/^Cap:\s*(.+)$/m', $messageText);
        
        return $hasAffiliate && $hasRecipient && $hasCap;
    }

    /**
     * Парсит стандартное сообщение капы
     */
    private function parseStandardCapMessage($messageText)
    {
        $data = [
            'affiliate_name' => null,
            'recipient_name' => null,
            'cap_amount' => null,
            'total_amount' => -1, // По умолчанию бесконечность
            'schedule' => '24/7', // По умолчанию 24/7
            'date' => null, // По умолчанию бесконечность
            'is_24_7' => true,
            'geos' => [],
            'work_hours' => null,
            'language' => null,
            'funnel' => null,
            'pending_acq' => false,
            'freeze_status_on_acq' => false
        ];

        // Парсим каждое поле
        if (preg_match('/^Affiliate:\s*(.+)$/m', $messageText, $matches)) {
            $data['affiliate_name'] = trim($matches[1]);
        }

        if (preg_match('/^Recipient:\s*(.+)$/m', $messageText, $matches)) {
            $data['recipient_name'] = trim($matches[1]);
        }

        if (preg_match('/^Cap:\s*(.+)$/m', $messageText, $matches)) {
            $capValue = trim($matches[1]);
            
            // Проверяем, есть ли слеш в значении Cap (например Cap: 15/150)
            if (strpos($capValue, '/') !== false) {
                // Если есть слеш, Total = бесконечность, берем первое число как Cap
                $parts = explode('/', $capValue);
                $data['cap_amount'] = intval(trim($parts[0]));
                $data['total_amount'] = -1; // Бесконечность
            } else {
                $data['cap_amount'] = intval($capValue);
            }
        }

        if (preg_match('/^Total:\s*(.+)$/m', $messageText, $matches)) {
            $totalValue = trim($matches[1]);
            if (!empty($totalValue) && is_numeric($totalValue)) {
                $data['total_amount'] = intval($totalValue);
            }
            // Если Total пустое или не число, остается -1 (бесконечность)
        }

        if (preg_match('/^Geo:\s*(.+)$/m', $messageText, $matches)) {
            $geoValue = trim($matches[1]);
            if (!empty($geoValue)) {
                // Разделяем по запятым, слешам и пробелам
                $geos = preg_split('/[,\/\s]+/', $geoValue);
                $data['geos'] = array_filter(array_map('trim', $geos), function($geo) {
                    return !empty($geo) && strlen($geo) >= 2;
                });
            }
        }

        if (preg_match('/^Language:\s*(.+)$/m', $messageText, $matches)) {
            $data['language'] = trim($matches[1]);
        }

        if (preg_match('/^Funnel:\s*(.+)$/m', $messageText, $matches)) {
            $data['funnel'] = trim($matches[1]);
        }

        if (preg_match('/^Schedule:\s*(.+)$/m', $messageText, $matches)) {
            $scheduleValue = trim($matches[1]);
            if (!empty($scheduleValue)) {
                $data['schedule'] = $scheduleValue;
                $data['work_hours'] = $scheduleValue;
                $data['is_24_7'] = false;
                
                // Проверяем, является ли это 24/7
                if (preg_match('/24\/7|24-7/i', $scheduleValue)) {
                    $data['is_24_7'] = true;
                }
            }
            // Если Schedule пустое, остается 24/7
        }

        if (preg_match('/^Date:\s*(.+)$/m', $messageText, $matches)) {
            $dateValue = trim($matches[1]);
            if (!empty($dateValue)) {
                $data['date'] = $dateValue;
            }
            // Если Date пустое, остается null (бесконечность)
        }

        if (preg_match('/^Pending ACQ:\s*(.+)$/m', $messageText, $matches)) {
            $pendingValue = strtolower(trim($matches[1]));
            $data['pending_acq'] = in_array($pendingValue, ['yes', 'true', '1', 'да']);
        }

        if (preg_match('/^Freeze status on ACQ:\s*(.+)$/m', $messageText, $matches)) {
            $freezeValue = strtolower(trim($matches[1]));
            $data['freeze_status_on_acq'] = in_array($freezeValue, ['yes', 'true', '1', 'да']);
        }

        // Проверяем, что обязательные поля заполнены
        if ($data['cap_amount'] && $data['affiliate_name'] && $data['recipient_name']) {
            return $data;
        }

        return null;
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
            $capData = $this->parseStandardCapMessage($message);
            
            if ($capData) {
                return [
                    'has_cap_word' => true,
                    'cap_amount' => $capData['cap_amount'],
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