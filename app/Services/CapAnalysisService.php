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
                'work_hours' => $capEntry['work_hours'],
                'highlighted_text' => $capEntry['highlighted_text']
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
                'chat_name' => $cap->message->chat->display_name ?? 'Неизвестный чат',
                'timestamp' => $cap->message->created_at->format('d.m.Y H:i'),
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
                    'work_hours' => $cap->work_hours,
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
            $query->where('broker_name', $filters['broker']);
        }

        // Фильтр по аффилейту
        if (!empty($filters['affiliate'])) {
            $query->where('affiliate_name', $filters['affiliate']);
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

        $caps = $query->leftJoin('messages', 'caps.message_id', '=', 'messages.id')
                     ->orderBy('messages.created_at', 'desc')
                     ->orderBy('caps.id', 'desc')
                     ->select('caps.*', 'messages.created_at as message_created_at')
                     ->get();
        
        $results = [];
        foreach ($caps as $cap) {

            
            $results[] = [
                'id' => $cap->message->id . '_' . $cap->id,
                'message' => $cap->message->message,
                'user' => $cap->message->display_name,
                'chat_name' => $cap->message->chat->display_name ?? 'Неизвестный чат',
                'timestamp' => $cap->message->created_at->format('d.m.Y H:i'),
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
                    'work_hours' => $cap->work_hours,
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
     * Список известных гео кодов
     */
    private function getGeoList()
    {
        return [
            'RU', 'KZ', 'UA', 'BY', 'UZ', 'TJ', 'KG', 'AM', 'AZ', 'GE', 'MD', 'LT', 'LV', 'EE',
            'DE', 'FR', 'IT', 'ES', 'PT', 'GB', 'UK', 'IE', 'NL', 'BE', 'AT', 'CH', 'SE', 'NO', 'DK', 'FI', 'PL', 'CZ', 'SK', 'HU', 'RO', 'BG', 'HR', 'SI', 'RS', 'BA', 'ME', 'MK', 'AL', 'GR', 'CY', 'MT',
            'US', 'CA', 'MX', 'BR', 'AR', 'CL', 'CO', 'PE', 'VE', 'UY', 'PY', 'EC', 'BO', 'GY', 'SR', 'FK',
            'AU', 'NZ', 'PG', 'FJ', 'NC', 'VU', 'SB', 'TO', 'WS', 'KI', 'TV', 'NR', 'PW', 'FM', 'MH',
            'CN', 'JP', 'KR', 'TW', 'HK', 'MO', 'SG', 'MY', 'TH', 'VN', 'PH', 'ID', 'BN', 'LA', 'KH', 'MM', 'IN', 'BD', 'PK', 'LK', 'NP', 'BT', 'MV', 'AF', 'IR', 'IQ', 'SY', 'LB', 'JO', 'PS', 'IL', 'TR', 'CY', 'SA', 'AE', 'QA', 'BH', 'KW', 'OM', 'YE',
            'EG', 'LY', 'TN', 'DZ', 'MA', 'SD', 'SS', 'ET', 'ER', 'DJ', 'SO', 'KE', 'UG', 'TZ', 'RW', 'BI', 'CD', 'CF', 'TD', 'CM', 'GQ', 'GA', 'ST', 'CG', 'AO', 'ZM', 'ZW', 'BW', 'NA', 'ZA', 'LS', 'SZ', 'MG', 'MU', 'SC', 'KM', 'MZ', 'MW', 'ZW'
        ];
    }

    /**
     * Извлекает отдельные записи кап из сообщения
     */
    private function extractSeparateCapEntries($messageText)
    {
        $capEntries = [];
        $lines = preg_split('/[\r\n]+/', $messageText);
        $geoList = $this->getGeoList();
        
        // Ищем CAP в сообщении
        $capAmount = null;
        
        // Поиск CAP
        foreach ($lines as $line) {
            $line = trim($line);
            if (preg_match('/(?:cap|сар|сар|кап)\s*[\s:=]*(\d+)/iu', $line, $capMatch)) {
                $capAmount = intval($capMatch[1]);
                break;
            }
        }
        
        // Если CAP не найден, пропускаем
        if (!$capAmount) {
            return $capEntries;
        }
        
        // Сначала находим ВСЕ CAP значения в тексте
        $allCapValues = [];
        foreach ($lines as $line) {
            if (preg_match_all('/(?:cap|сар|сар|кап)\s*[\s:=]*(\d+)/iu', $line, $capMatches)) {
                foreach ($capMatches[1] as $match) {
                    $capValue = intval($match);
                    if ($capValue > 0 && $capValue < 10000) {
                        $allCapValues[] = $capValue;
                    }
                }
            }
        }
        
        // Поиск общего тотала в строках без пар аффилейт-брокер
        $globalTotalAmount = null;
        
        // Ищем общий тотал в отдельных строках
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропускаем строки с CAP
            if (preg_match('/(?:cap|сар|сар|кап)/iu', $line)) {
                continue;
            }
            
            // Пропускаем строки с парами аффилейт-брокер
            if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)/', $line)) {
                // Проверяем что это не время работы
                $parts = explode('-', $line);
                if (count($parts) == 2) {
                    $part1 = trim($parts[0]);
                    $part2 = trim($parts[1]);
                    // Если обе части - только цифры, это время работы, не пропускаем
                    if (!(preg_match('/^\d{1,2}$/', $part1) && preg_match('/^\d{1,2}$/', $part2))) {
                        continue; // Это пара аффилейт-брокер, пропускаем
                    }
                }
            }
            
            // Пропускаем строки которые являются только датами
            if (preg_match('/^\d{1,2}\.\d{1,2}$/', $line)) {
                continue;
            }
            
            // Пропускаем строки которые являются только 24/7
            if (preg_match('/^24\/7$|^24-7$/', $line)) {
                continue;
            }
            
            // Ищем числа в строке
            preg_match_all('/\b(\d+)\b/', $line, $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $num = intval($match);
                    // Проверяем что это не CAP и это разумное число для тотала
                    if (!in_array($num, $allCapValues) && $num >= 50 && $num < 10000) {
                        $globalTotalAmount = $num;
                        break 2; // Выходим из обоих циклов
                    }
                }
            }
        }
        
        // Поиск глобальных параметров (применяются ко всем, если не указаны конкретно)
        $globalSchedule = null;
        $globalDate = null;
        $globalGeos = [];
        $globalWorkHours = null;
        
        // Ищем глобальные параметры в строках без пар аффилейт-брокер
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропускаем строки с CAP
            if (preg_match('/(?:cap|сар|сар|кап)/iu', $line)) {
                continue;
            }
            
            // СНАЧАЛА проверяем время работы (10-19, 09-18, etc.)
            if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $line, $matches)) {
                $globalWorkHours = $matches[0];
                $globalSchedule = $matches[0];
                continue; // Пропускаем дальнейшие проверки для этой строки
            }
            
            // Ищем 24/7
            if (preg_match('/24\/7|24-7/', $line)) {
                $globalSchedule = '24/7';
                continue;
            }
            
            // ПОСЛЕ проверки времени работы пропускаем строки с парами аффилейт-брокер
            if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)/', $line)) {
                // Но перед пропуском еще раз проверим, что это не время работы
                $parts = explode('-', $line);
                if (count($parts) == 2) {
                    $part1 = trim($parts[0]);
                    $part2 = trim($parts[1]);
                    // Если обе части - только цифры, это время работы
                    if (preg_match('/^\d{1,2}$/', $part1) && preg_match('/^\d{1,2}$/', $part2)) {
                        $globalWorkHours = $line;
                        $globalSchedule = $line;
                        continue;
                    }
                }
                continue; // Это действительно пара аффилейт-брокер
            }
            
            // Ищем даты (14.05, 25.12, etc.)
            if (preg_match('/^(\d{1,2})\.(\d{1,2})$/', $line, $matches)) {
                $globalDate = $matches[0] . '.' . date('Y'); // Добавляем текущий год
            }
            
            // Ищем гео в отдельных строках
            $lineGeos = [];
            $words = preg_split('/[\s,\/]+/', $line);
            foreach ($words as $word) {
                $word = trim(strtoupper($word));
                if (in_array($word, $geoList)) {
                    $lineGeos[] = $word;
                }
            }
            if (!empty($lineGeos)) {
                $globalGeos = array_merge($globalGeos, $lineGeos);
            }
        }
        
        // Устанавливаем значения по умолчанию
        if (!$globalSchedule) {
            $globalSchedule = '24/7';
        }
        // НЕ устанавливаем дату по умолчанию - оставляем null если не найдена
        // if (!$globalDate) {
        //     $globalDate = date('d.m.Y');
        // }
        
        // Поиск пар аффилейт-брокер
        $pairs = [];
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Пропускаем строки, которые являются временем работы (например 10-19, 09-18)
            if (preg_match('/^\d{1,2}-\d{1,2}$/', $line)) {
                continue;
            }
            
            // Ищем пары аффилейт-брокер (НЕ только цифры)
            if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)/', $line, $pairMatch)) {
                // Проверяем что это не время работы внутри строки
                $part1 = trim($pairMatch[1]);
                $part2 = trim($pairMatch[2]);
                
                // Если обе части - только цифры, это время работы, пропускаем
                if (preg_match('/^\d{1,2}$/', $part1) && preg_match('/^\d{1,2}$/', $part2)) {
                    continue;
                }
                
                // Проверяем есть ли CAP в этой строке (приоритет над общим CAP)
                $lineCapAmount = $capAmount; // по умолчанию используем общий CAP
                if (preg_match('/(?:cap|сар|сар|кап)\s*[\s:=]*(\d+)/iu', $line, $lineCapMatch)) {
                    $lineCapAmount = intval($lineCapMatch[1]);
                }
                
                // Ищем тотал специфичный для этой строки
                $lineTotalAmount = null;
                
                // Ищем все числа в этой строке
                preg_match_all('/\b(\d+)\b/', $line, $lineNumbers);
                if (!empty($lineNumbers[1])) {
                    $lineNumbersArray = array_map('intval', $lineNumbers[1]);
                    
                    // Ищем число которое НЕ является CAP и больше CAP
                    foreach ($lineNumbersArray as $num) {
                        if (!in_array($num, $allCapValues) && $num > $lineCapAmount && $num >= 50 && $num < 10000) {
                            $lineTotalAmount = $num;
                            break;
                        }
                    }
                }
                
                // Если не найден тотал в строке, используем общий тотал
                if (!$lineTotalAmount) {
                    $lineTotalAmount = $globalTotalAmount ?: -1; // -1 означает бесконечность
                }
                
                $affiliateName = trim($pairMatch[1]);
                $brokerPart = trim($pairMatch[2]);
                
                // Очищаем имя аффилиата более аккуратно
                $cleanAffiliateName = $affiliateName;
                
                // Убираем CAP из названия аффилейта (из любого места, не только начала)
                $cleanAffiliateName = preg_replace('/(?:cap|сар|сар|кап)\s*[\s:=]*\d+\s*/iu', '', $cleanAffiliateName);
                
                // Убираем все найденные CAP значения
                foreach ($allCapValues as $capValue) {
                    $cleanAffiliateName = preg_replace('/\b' . $capValue . '\b\s*/', '', $cleanAffiliateName);
                }
                
                // Убираем числа которые являются total amount только если они есть в строке
                if ($lineTotalAmount > 0) {
                    $cleanAffiliateName = preg_replace('/\b' . $lineTotalAmount . '\b\s*/', '', $cleanAffiliateName);
                }
                
                // Убираем начальные слова типа "по", "max", "до", "no" и т.д.
                $cleanAffiliateName = preg_replace('/^(по|max|до|макс|мах|no)\s+/iu', '', $cleanAffiliateName);
                
                $cleanAffiliateName = trim($cleanAffiliateName);
                
                // Разделяем брокера и гео из brokerPart
                $lineGeos = [];
                $brokerName = $brokerPart;
                
                // Сначала проверяем, есть ли расписание/даты в brokerPart и убираем их
                $cleanBrokerPart = $brokerPart;
                
                // Убираем 24/7 из broker part
                $cleanBrokerPart = preg_replace('/\s*24\/7\s*/', ' ', $cleanBrokerPart);
                $cleanBrokerPart = preg_replace('/\s*24-7\s*/', ' ', $cleanBrokerPart);
                
                // Убираем даты в формате ДД.ММ из broker part
                $cleanBrokerPart = preg_replace('/\s*\d{1,2}\.\d{1,2}\s*/', ' ', $cleanBrokerPart);
                
                // Убираем время работы в формате ЧЧ-ЧЧ из broker part
                $cleanBrokerPart = preg_replace('/\s*\d{1,2}-\d{1,2}\s*/', ' ', $cleanBrokerPart);
                
                // Убираем отдельные числа которые могут быть частью времени/даты
                // Но только если они не являются частью имени брокера
                $tempParts = preg_split('/[\s,\/]+/', $cleanBrokerPart);
                $filteredParts = [];
                
                foreach ($tempParts as $part) {
                    $part = trim($part);
                    if (empty($part)) continue;
                    
                    // Пропускаем отдельные числа от 1 до 31 (вероятно дни/месяцы)
                    if (preg_match('/^\d{1,2}$/', $part)) {
                        $num = intval($part);
                        if ($num >= 1 && $num <= 31) {
                            continue; // Пропускаем числа которые могут быть частью даты
                        }
                    }
                    
                    $filteredParts[] = $part;
                }
                
                $cleanBrokerPart = implode(' ', $filteredParts);
                
                // Сначала ищем гео в cleanBrokerPart и удаляем их из названия брокера
                $brokerWords = preg_split('/[\s,\/]+/', $cleanBrokerPart);
                $brokerNameWords = [];
                
                foreach ($brokerWords as $word) {
                    $word = trim($word);
                    if (empty($word)) continue;
                    
                    $wordUpper = strtoupper($word);
                    if (in_array($wordUpper, $geoList)) {
                        $lineGeos[] = $wordUpper;
                    } else {
                        $brokerNameWords[] = $word;
                    }
                }
                
                // Собираем название брокера из оставшихся слов
                $brokerName = trim(implode(' ', $brokerNameWords));
                
                // Поиск после двоеточия (дополнительные гео)
                if (preg_match('/:([^:\r\n]+)/', $line, $geoMatch)) {
                    $geoString = trim($geoMatch[1]);
                    $words = preg_split('/[\s,\/]+/', $geoString);
                    foreach ($words as $word) {
                        $word = trim(strtoupper($word));
                        if (in_array($word, $geoList)) {
                            $lineGeos[] = $word;
                        }
                    }
                }
                
                // Поиск гео в остальной части строки (если ещё не найдены)
                if (empty($lineGeos)) {
                    $words = preg_split('/[\s,\/]+/', $line);
                    foreach ($words as $word) {
                        $word = trim(strtoupper($word));
                        if (in_array($word, $geoList)) {
                            $lineGeos[] = $word;
                        }
                    }
                }
                
                // Убираем дубликаты гео
                $lineGeos = array_unique($lineGeos);
                
                // Поиск времени/даты в той же строке (приоритет над глобальными)
                $lineSchedule = null;
                $lineDate = null;
                $lineWorkHours = null;
                
                // Поиск 24/7 в строке
                if (preg_match('/24\/7|24-7/', $line)) {
                    $lineSchedule = '24/7';
                }
                
                // Поиск времени работы в строке
                if (preg_match('/(\d{1,2})-(\d{1,2})/', $line, $matches)) {
                    $lineWorkHours = $matches[0];
                    $lineSchedule = $matches[0];
                }
                
                // Поиск даты в строке
                if (preg_match('/(\d{1,2})\.(\d{1,2})/', $line, $matches)) {
                    $lineDate = $matches[0] . '.' . date('Y');
                }
                
                // Если не найдено в строке, используем глобальные
                if (!$lineSchedule) {
                    $lineSchedule = $globalSchedule;
                }
                if (!$lineDate) {
                    $lineDate = $globalDate;
                }
                if (!$lineWorkHours) {
                    $lineWorkHours = $globalWorkHours;
                }
                
                $pairs[] = [
                    'affiliate_name' => $cleanAffiliateName ?: null,
                    'broker_name' => $brokerName ?: null,
                    'geos' => !empty($lineGeos) ? $lineGeos : $globalGeos,
                    'schedule' => $lineSchedule,
                    'date' => $lineDate,
                    'work_hours' => $lineWorkHours,
                    'highlighted_text' => $line,
                    'cap_amount' => $lineCapAmount, // Добавляем индивидуальный CAP для каждой пары
                    'total_amount' => $lineTotalAmount // Добавляем индивидуальный тотал для каждой пары
                ];
            }
        }
        
        // Если пары не найдены, создаем одну запись с глобальными параметрами
        if (empty($pairs)) {
            // Ищем тотал во всем сообщении
            $messageTotalAmount = null;
            preg_match_all('/\b(\d+)\b/', $messageText, $allMessageNumbers);
            if (!empty($allMessageNumbers[1])) {
                $messageNumbersArray = array_map('intval', $allMessageNumbers[1]);
                
                // Ищем число которое НЕ является CAP и больше CAP
                foreach ($messageNumbersArray as $num) {
                    if (!in_array($num, $allCapValues) && $num > $capAmount && $num >= 50 && $num < 10000) {
                        $messageTotalAmount = $num;
                        break;
                    }
                }
            }
            
            $pairs[] = [
                'affiliate_name' => null,
                'broker_name' => null,
                'geos' => $globalGeos,
                'schedule' => $globalSchedule,
                'date' => $globalDate,
                'work_hours' => $globalWorkHours,
                'highlighted_text' => $messageText,
                'cap_amount' => $capAmount, // Общий CAP для случая без пар
                'total_amount' => $messageTotalAmount ?: -1 // Тотал из всего сообщения или бесконечность
            ];
        }
        
        // Создаем записи для каждой пары
        foreach ($pairs as $pair) {
            $capEntries[] = [
                'cap_amount' => $pair['cap_amount'], // Используем индивидуальный CAP каждой пары
                'total_amount' => $pair['total_amount'], // Используем индивидуальный тотал каждой пары
                'schedule' => $pair['schedule'],
                'date' => $pair['date'],
                'is_24_7' => $pair['schedule'] === '24/7',
                'affiliate_name' => $pair['affiliate_name'],
                'broker_name' => $pair['broker_name'],
                'geos' => $pair['geos'],
                'work_hours' => $pair['work_hours'],
                'highlighted_text' => $pair['highlighted_text']
            ];
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