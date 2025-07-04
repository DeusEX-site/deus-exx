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
            'raw_numbers' => []
        ];
        
        // Проверяем наличие слов cap/сар/сар
        $capWords = ['cap', 'сар', 'сар', 'CAP', 'САР', 'САР', 'кап', 'КАП'];
        foreach ($capWords as $word) {
            if (stripos($message, $word) !== false) {
                $analysis['has_cap_word'] = true;
                break;
            }
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
        
        // Ищем все числа
        preg_match_all('/\b(\d+)\b/', $message, $numbers);
        if (!empty($numbers[1])) {
            $analysis['raw_numbers'] = array_map('intval', $numbers[1]);
            
            // Анализируем числа
            foreach ($analysis['raw_numbers'] as $number) {
                // Пропускаем очевидные даты и времена
                if ($number > 31 && $number < 2000) {
                    // Ищем cap amount (обычно после слова cap)
                    $capPattern = '/(?:cap|сар|сар|кап)\s*(\d+)/i';
                    if (preg_match($capPattern, $message, $capMatch)) {
                        $analysis['cap_amount'] = intval($capMatch[1]);
                    }
                    
                    // Если число не после cap слова, это может быть total amount
                    if (!$analysis['cap_amount'] || $number > $analysis['cap_amount']) {
                        $analysis['total_amount'] = max($analysis['total_amount'] ?: 0, $number);
                    }
                }
            }
        }
        
        // Ищем названия (паттерн: название - название)
        if (preg_match('/([a-zA-Zа-яА-Я\s]+)\s*-\s*([a-zA-Zа-яА-Я\s]+)\s*:/', $message, $matches)) {
            $analysis['affiliate_name'] = trim($matches[1]);
            $analysis['broker_name'] = trim($matches[2]);
        }
        
        // Ищем гео после двоеточия
        if (preg_match('/:(.+)$/m', $message, $matches)) {
            $geoString = trim($matches[1]);
            $geos = array_map('trim', explode(',', $geoString));
            $analysis['geos'] = array_filter($geos, function($geo) {
                return strlen($geo) > 1 && strlen($geo) < 50;
            });
        }
        
        return $analysis;
    }
} 