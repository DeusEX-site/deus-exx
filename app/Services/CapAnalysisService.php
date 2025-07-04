<?php

namespace App\Services;

class CapAnalysisService
{
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