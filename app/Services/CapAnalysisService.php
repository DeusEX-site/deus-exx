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
        
        // Находим все отдельные капы в сообщении
        $capEntries = $this->extractSeparateCapEntries($messageText);
        
        // Создаем отдельную запись для каждой капы
        foreach ($capEntries as $capEntry) {
            Cap::create([
                'message_id' => $messageId,
                'cap_amounts' => [$capEntry['cap_amount']], // Одна капа на запись
                'total_amount' => $capEntry['total_amount'],
                'schedule' => $capEntry['schedule'],
                'date' => $capEntry['date'],
                'is_24_7' => $capEntry['is_24_7'],
                'affiliate_name' => $capEntry['affiliate_name'],
                'broker_name' => $capEntry['broker_name'],
                'geos' => $capEntry['geos'],
                'work_hours' => $capEntry['work_hours']
            ]);
        }
        
        return ['cap_entries_count' => count($capEntries)];
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
                'id' => $cap->message->id . '_' . $cap->id, // Уникальный ID для каждой записи
                'message' => $cap->message->message,
                'user' => $cap->message->display_name,
                'chat_name' => $cap->message->chat->name ?? 'Неизвестный чат',
                'timestamp' => $cap->created_at->format('d.m.Y H:i'),
                'analysis' => [
                    'has_cap_word' => true, // Если запись существует, значит cap word найден
                    'cap_amounts' => $cap->cap_amounts, // Это уже массив с одной капой
                    'total_amount' => $cap->total_amount,
                    'schedule' => $cap->schedule,
                    'date' => $cap->date,
                    'is_24_7' => $cap->is_24_7,
                    'affiliate_name' => $cap->affiliate_name,
                    'broker_name' => $cap->broker_name,
                    'geos' => $cap->geos ?? [],
                    'work_hours' => $cap->work_hours
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
                  ->orWhere('broker_name', 'LIKE', "%{$search}%")
                  ->orWhere('schedule', 'LIKE', "%{$search}%")
                  ->orWhereHas('message', function($subQ) use ($search) {
                      $subQ->where('message', 'LIKE', "%{$search}%");
                  });
            });
        }

        // Фильтр по гео
        if (!empty($filters['geo'])) {
            $query->whereJsonContains('geos', $filters['geo']);
        }

        // Фильтр по брокеру
        if (!empty($filters['broker'])) {
            $query->where('broker_name', 'LIKE', "%{$filters['broker']}%");
        }

        // Фильтр по аффилейту
        if (!empty($filters['affiliate'])) {
            $query->where('affiliate_name', 'LIKE', "%{$filters['affiliate']}%");
        }

        // Фильтр по расписанию
        if (!empty($filters['schedule'])) {
            switch ($filters['schedule']) {
                case 'has_schedule':
                    $query->whereNotNull('schedule');
                    break;
                case 'no_schedule':
                    $query->whereNull('schedule');
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
                    $query->whereNotNull('total_amount');
                    break;
                case 'no_total':
                    $query->whereNull('total_amount');
                    break;
            }
        }

        // Фильтр по наличию аффилейта
        if (!empty($filters['affiliate_presence'])) {
            switch ($filters['affiliate_presence']) {
                case 'has_affiliate':
                    $query->whereNotNull('affiliate_name');
                    break;
                case 'no_affiliate':
                    $query->whereNull('affiliate_name');
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
                'chat_name' => $cap->message->chat->name ?? 'Неизвестный чат',
                'timestamp' => $cap->created_at->format('d.m.Y H:i'),
                'analysis' => [
                    'has_cap_word' => true,
                    'cap_amounts' => $cap->cap_amounts,
                    'total_amount' => $cap->total_amount,
                    'schedule' => $cap->schedule,
                    'date' => $cap->date,
                    'is_24_7' => $cap->is_24_7,
                    'affiliate_name' => $cap->affiliate_name,
                    'broker_name' => $cap->broker_name,
                    'geos' => $cap->geos ?? [],
                    'work_hours' => $cap->work_hours
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
        // Получаем все уникальные значения
        $caps = Cap::whereNotNull('geos')
                   ->orWhereNotNull('broker_name')
                   ->orWhereNotNull('affiliate_name')
                   ->get();

        $geos = [];
        $brokers = [];
        $affiliates = [];

        foreach ($caps as $cap) {
            // Собираем гео
            if ($cap->geos) {
                $geos = array_merge($geos, $cap->geos);
            }

            // Собираем брокеров
            if ($cap->broker_name) {
                $brokers[] = $cap->broker_name;
            }

            // Собираем аффилейтов
            if ($cap->affiliate_name) {
                $affiliates[] = $cap->affiliate_name;
            }
        }

        return [
            'geos' => array_values(array_unique($geos)),
            'brokers' => array_values(array_unique($brokers)),
            'affiliates' => array_values(array_unique($affiliates))
        ];
    }

    /**
     * Извлекает отдельные записи кап из сообщения
     */
    private function extractSeparateCapEntries($messageText)
    {
        $capEntries = [];
        $lines = preg_split('/[\r\n]+/', $messageText);
        
        // Глобальная информация (может применяться ко всем капам)
        $globalSchedule = null;
        $globalDate = null;
        $globalIs24_7 = false;
        $globalWorkHours = null;
        
        // Извлекаем глобальную информацию
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ищем 24/7
            if (preg_match('/24\/7|24-7/', $line)) {
                $globalIs24_7 = true;
                $globalSchedule = '24/7';
            }
            
            // Ищем время работы (10-19, 09-18, etc.)
            if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $line, $matches)) {
                $globalWorkHours = $matches[0];
                $globalSchedule = $matches[0];
            }
            
            // Ищем даты (14.05, 25.12, etc.)
            if (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $line, $matches)) {
                $globalDate = $matches[0];
            }
        }
        
        // Ищем строки с капами
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Ищем cap в строке
            if (preg_match('/(?:cap|сар|сар|кап)\s*[\s:=]*(\d+)/iu', $line, $capMatch)) {
                $capAmount = intval($capMatch[1]);
                
                // Извлекаем аффилейт и брокера
                $affiliateName = null;
                $brokerName = null;
                if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)\s*:/', $line, $nameMatch)) {
                    $affiliateName = trim($nameMatch[1]);
                    $brokerName = trim($nameMatch[2]);
                    
                    // Убираем cap из названия аффилейта
                    $affiliateName = preg_replace('/^(?:cap|сар|сар|кап)\s*[\s:=]*\d+\s*/iu', '', $affiliateName);
                    $affiliateName = trim($affiliateName);
                }
                
                // Извлекаем гео
                $geos = [];
                if (preg_match('/:([^:\r\n]+)/', $line, $geoMatch)) {
                    $geoString = trim($geoMatch[1]);
                    $geoList = preg_split('/[,\/\s]+/', $geoString);
                    $geos = array_filter(array_map('trim', $geoList), function($geo) {
                        return strlen($geo) >= 2 && strlen($geo) <= 10 && preg_match('/^[A-Z]{2,}$/i', $geo);
                    });
                }
                
                // Ищем общий лимит (большие числа)
                $totalAmount = null;
                if (preg_match_all('/\b(\d+)\b/', $line, $numbers)) {
                    $allNumbers = array_map('intval', $numbers[1]);
                    $potentialTotals = array_filter($allNumbers, function($num) use ($capAmount) {
                        return $num > $capAmount && $num < 10000;
                    });
                    if (!empty($potentialTotals)) {
                        $totalAmount = max($potentialTotals);
                    }
                }
                
                // Создаем запись капы
                $capEntries[] = [
                    'cap_amount' => $capAmount,
                    'total_amount' => $totalAmount,
                    'schedule' => $globalSchedule,
                    'date' => $globalDate,
                    'is_24_7' => $globalIs24_7,
                    'affiliate_name' => $affiliateName,
                    'broker_name' => $brokerName,
                    'geos' => $geos,
                    'work_hours' => $globalWorkHours
                ];
            }
        }
        
        return $capEntries;
    }

    /**
     * Анализирует сообщение на наличие кап (без сохранения)
     */
    public function analyzeCapMessage($message)
    {
        $analysis = [
            'has_cap_word' => false,
            'cap_amount' => null,
            'total_amount' => null,
            'schedule' => null,
            'date' => null,
            'is_24_7' => false,
            'affiliate_name' => null,
            'broker_name' => null,
            'geos' => [],
            'work_hours' => null,
            'raw_numbers' => [],
            'cap_amounts' => [] // Все найденные cap значения
        ];
        
        // Проверяем наличие слов cap/сар/сар
        $capWords = ['cap', 'сар', 'сар', 'CAP', 'САР', 'САР', 'кап', 'КАП'];
        foreach ($capWords as $word) {
            if (stripos($message, $word) !== false) {
                $analysis['has_cap_word'] = true;
                break;
            }
        }
        
        // Ищем ВСЕ cap amounts в сообщении
        $capAmounts = $this->extractCapAmounts($message);
        $analysis['cap_amounts'] = $capAmounts;
        
        // Основной cap amount - для обратной совместимости (не используется в отображении)
        if (!empty($capAmounts)) {
            $analysis['cap_amount'] = $capAmounts[0]; // Первая найденная капа
        }
        
        // Ищем 24/7
        if (preg_match('/24\/7|24-7/', $message)) {
            $analysis['is_24_7'] = true;
            $analysis['schedule'] = '24/7';
        }
        
        // Ищем время работы (10-19, 09-18, etc.)
        if (preg_match('/(\d{1,2})-(\d{1,2})/', $message, $matches)) {
            $analysis['work_hours'] = $matches[0];
            $analysis['schedule'] = $matches[0];
        }
        
        // Ищем даты (14.05, 25.12, etc.)
        if (preg_match('/(\d{1,2})\.(\d{1,2})/', $message, $matches)) {
            $analysis['date'] = $matches[0];
        }
        
        // Ищем все числа в сообщении
        preg_match_all('/\b(\d+)\b/', $message, $numbers);
        if (!empty($numbers[1])) {
            $analysis['raw_numbers'] = array_map('intval', $numbers[1]);
            
            // Определяем total amount как максимальное число, которое больше cap amount
            $largestNumber = max($analysis['raw_numbers']);
            
            // Если есть числа больше любой капы и они разумных размеров
            if (!empty($capAmounts)) {
                $maxCap = max($capAmounts);
                $potentialTotals = array_filter($analysis['raw_numbers'], function($num) use ($maxCap) {
                    return $num > $maxCap && $num < 10000; // Разумный лимит
                });
                
                if (!empty($potentialTotals)) {
                    $analysis['total_amount'] = max($potentialTotals);
                }
            } else {
                // Если cap amount не найден, самое большое число может быть total
                if ($largestNumber >= 10 && $largestNumber < 10000) {
                    $analysis['total_amount'] = $largestNumber;
                }
            }
        }
        
        // Ищем названия (паттерн: название - название)
        if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)\s*:/', $message, $matches)) {
            $analysis['affiliate_name'] = trim($matches[1]);
            $analysis['broker_name'] = trim($matches[2]);
        }
        
        // Ищем гео после двоеточий (множественные)
        $allGeos = [];
        if (preg_match_all('/:([^:\r\n]+)/m', $message, $matches)) {
            foreach ($matches[1] as $geoString) {
                $geoString = trim($geoString);
                $geos = preg_split('/[,\/\s]+/', $geoString);
                $validGeos = array_filter(array_map('trim', $geos), function($geo) {
                    return strlen($geo) >= 2 && strlen($geo) <= 10 && preg_match('/^[A-Z]{2,}$/i', $geo);
                });
                $allGeos = array_merge($allGeos, $validGeos);
            }
        }
        
        // Убираем дубликаты и сортируем
        $analysis['geos'] = array_unique($allGeos);
        
        return $analysis;
    }
    
    /**
     * Извлекает все cap amounts из сообщения
     */
    private function extractCapAmounts($message)
    {
        $capAmounts = [];
        
        // Различные паттерны для поиска cap значений
        $patterns = [
            '/(?:cap|сар|сар|кап)\s*[\s:]*(\d+)/iu', // CAP 30, CAP: 30
            '/(?:cap|сар|сар|кап)\s*[-=]\s*(\d+)/iu', // CAP - 30, CAP = 30  
            '/(?:cap|сар|сар|кап)\s*(\d+)/iu', // CAP30 (без пробела)
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                foreach ($matches[1] as $match) {
                    $amount = intval($match);
                    // Фильтруем разумные значения cap (от 1 до 9999)
                    if ($amount > 0 && $amount < 10000) {
                        $capAmounts[] = $amount;
                    }
                }
            }
        }
        
        // Специальная логика для случаев когда один cap применяется к нескольким строкам
        $capAmounts = $this->handleMultilineCapDistribution($message, $capAmounts);
        
        // Убираем дубликаты и сортируем
        $capAmounts = array_unique($capAmounts);
        sort($capAmounts);
        
        return $capAmounts;
    }
    
    /**
     * Обрабатывает случаи когда один cap применяется к нескольким аффилейтам
     */
    private function handleMultilineCapDistribution($message, $capAmounts)
    {
        // Если найден только один cap, но есть несколько пар аффилейт-брокер
        if (count($capAmounts) === 1) {
            $affiliatePairs = $this->countAffiliateBrokerPairs($message);
            
            // Если найдено больше одной пары, дублируем cap для каждой
            if ($affiliatePairs > 1) {
                $singleCap = $capAmounts[0];
                $capAmounts = array_fill(0, $affiliatePairs, $singleCap);
            }
        }
        
        return $capAmounts;
    }
    
    /**
     * Подсчитывает количество пар аффилейт-брокер в сообщении
     */
    private function countAffiliateBrokerPairs($message)
    {
        // Ищем паттерны вида "Name - Name :" 
        $patterns = [
            '/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)\s*:/u',
            '/([A-Z][a-z]+)\s*-\s*([A-Z][a-z]+)/u' // Простые имена с заглавной буквы
        ];
        
        $pairs = 0;
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $message, $matches)) {
                $pairs += count($matches[0]);
            }
        }
        
        // Если не найдено пар по паттернам, попробуем по строкам
        if ($pairs === 0) {
            $lines = preg_split('/[\r\n]+/', trim($message));
            $linesWithAffiliate = 0;
            
            foreach ($lines as $line) {
                $line = trim($line);
                // Строка содержит гео коды или имена аффилейтов
                if (preg_match('/[A-Z]{2,}\/[A-Z]{2,}|[A-Z]{2,},[A-Z]{2,}/', $line) || 
                    preg_match('/[A-Za-z]+\s*-\s*[A-Za-z]+/', $line)) {
                    $linesWithAffiliate++;
                }
            }
            
            $pairs = max($pairs, $linesWithAffiliate);
        }
        
        return max($pairs, 1); // Минимум одна пара
    }
} 