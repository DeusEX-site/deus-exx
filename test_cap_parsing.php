<?php

// Простой тест для проверки парсинга времени работы
function testTimeParsingLogic()
{
    echo "Тестирование логики парсинга времени работы...\n";
    
    $testMessage = "CAP 30\nAff - Rec\nRU KZ\ntotal 100\n10-19\nAff2 - Rec";
    
    echo "Тестовое сообщение:\n";
    echo $testMessage . "\n\n";
    
    // Разбиваем текст на строки
    $lines = explode("\n", $testMessage);
    $lines = array_filter($lines, function($line) {
        return trim($line) !== '';
    });
    
    echo "Строки:\n";
    foreach ($lines as $i => $line) {
        echo "  $i: '$line'\n";
    }
    echo "\n";
    
    $globalSchedule = null;
    $globalWorkHours = null;
    
    // Поиск времени работы
    foreach ($lines as $line) {
        $line = trim($line);
        
        echo "Проверяю строку: '$line'\n";
        
        // Пропускаем строки с CAP
        if (preg_match('/(?:cap|сар|сар|кап)/iu', $line)) {
            echo "  -> Пропускаю CAP\n";
            continue;
        }
        
        // СНАЧАЛА проверяем время работы (10-19, 09-18, etc.)
        if (preg_match('/^(\d{1,2})-(\d{1,2})$/', $line, $matches)) {
            $globalWorkHours = $matches[0];
            $globalSchedule = $matches[0];
            echo "  -> НАЙДЕНО ВРЕМЯ РАБОТЫ: " . $matches[0] . "\n";
            continue;
        }
        
        // Ищем 24/7
        if (preg_match('/24\/7|24-7/', $line)) {
            $globalSchedule = '24/7';
            echo "  -> Найдено 24/7\n";
            continue;
        }
        
        // Проверяем пары аффилейт-брокер
        if (preg_match('/([a-zA-Zа-яА-Я\d\s]+)\s*-\s*([a-zA-Zа-яА-Я\d\s]+)/', $line)) {
            // Дополнительная проверка времени работы
            $parts = explode('-', $line);
            if (count($parts) == 2) {
                $part1 = trim($parts[0]);
                $part2 = trim($parts[1]);
                // Если обе части - только цифры, это время работы
                if (preg_match('/^\d{1,2}$/', $part1) && preg_match('/^\d{1,2}$/', $part2)) {
                    $globalWorkHours = $line;
                    $globalSchedule = $line;
                    echo "  -> НАЙДЕНО ВРЕМЯ РАБОТЫ (в паре): " . $line . "\n";
                    continue;
                }
            }
            echo "  -> Найдена пара аффилейт-брокер: " . $line . "\n";
            continue;
        }
        
        echo "  -> Обычная строка\n";
    }
    
    echo "\nРезультаты:\n";
    echo "Время работы: " . ($globalWorkHours ?: 'не найдено') . "\n";
    echo "Расписание: " . ($globalSchedule ?: '24/7 (по умолчанию)') . "\n";
    
    if ($globalSchedule === '10-19') {
        echo "\n✅ УСПЕХ: Время работы 10-19 найдено!\n";
    } else {
        echo "\n❌ ОШИБКА: Время работы 10-19 не найдено!\n";
    }
}

testTimeParsingLogic();

?> 