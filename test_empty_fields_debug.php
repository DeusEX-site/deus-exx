<?php

// Точный текст из сообщения пользователя
$messageText = 'Affiliate: test resipent
Recipient: CitadelCPA
CAP: 30
Geo: GE
Schedule: 10-19 +3

дополнительные поля, если надо :
Schedule:
Date:
Language:
Funnel:
Total:
Pending ACQ:
Freeze status on ACQ:';

echo "=== TESTING EMPTY FIELDS PARSING ===\n\n";
echo "Original message:\n";
echo "---\n$messageText\n---\n\n";

// Тестируем каждое поле отдельно
$fields = [
    'Language' => '/^Language:\s*([^\n\r]*)$/m',
    'Funnel' => '/^Funnel:\s*([^\n\r]*)$/m', 
    'Total' => '/^Total:\s*([^\n\r]*)$/m',
    'Date' => '/^Date:\s*([^\n\r]*)$/m',
    'Schedule' => '/^Schedule:\s*([^\n\r]*)$/m',
    'Pending ACQ' => '/^Pending ACQ:\s*([^\n\r]*)$/m',
    'Freeze status on ACQ' => '/^Freeze status on ACQ:\s*([^\n\r]*)$/m'
];

foreach ($fields as $fieldName => $pattern) {
    echo "\n--- Testing $fieldName ---\n";
    
    if (preg_match_all($pattern, $messageText, $matches, PREG_SET_ORDER)) {
        echo "Found " . count($matches) . " matches:\n";
        
        foreach ($matches as $i => $match) {
            echo "Match " . ($i + 1) . ":\n";
            echo "  Full match: '" . $match[0] . "'\n";
            echo "  Captured value: '" . $match[1] . "'\n";
            echo "  Value length: " . strlen($match[1]) . "\n";
            echo "  Is empty: " . (trim($match[1]) === '' ? 'YES' : 'NO') . "\n";
            
            // Проверка на наличие переносов строк
            if (strpos($match[1], "\n") !== false) {
                echo "  WARNING: Contains newline!\n";
            }
            if (strpos($match[1], "\r") !== false) {
                echo "  WARNING: Contains carriage return!\n";
            }
            
            // Hex dump для отладки
            echo "  Hex: ";
            for ($j = 0; $j < strlen($match[1]); $j++) {
                echo sprintf("%02X ", ord($match[1][$j]));
            }
            echo "\n";
        }
    } else {
        echo "No matches found\n";
    }
}

// Теперь тестируем isEmpty функцию
echo "\n\n=== Testing isEmpty function ===\n";

function testIsEmpty($value) {
    // Копия isEmpty из CapAnalysisService
    return $value === null || 
           $value === '' || 
           (is_string($value) && trim($value) === '') ||
           (is_string($value) && trim($value) === '-');
}

$testValues = [
    '',
    ' ',
    '  ',
    "\n",
    "\r\n",
    "Funnel:",
    "Total:",
    "Language:",
    "-",
    " - ",
    "0",
    "test"
];

foreach ($testValues as $value) {
    $isEmpty = testIsEmpty($value);
    echo "Value: '" . str_replace(["\n", "\r"], ['\n', '\r'], $value) . "' -> isEmpty: " . ($isEmpty ? 'YES' : 'NO') . "\n";
}

// Проверяем, что происходит с языком
echo "\n\n=== Checking Language parsing issue ===\n";

// Симулируем парсинг Language
if (preg_match_all('/^Language:\s*([^\n\r]*)$/m', $messageText, $matches)) {
    $languageValue = null;
    foreach ($matches[1] as $match) {
        $trimmed = trim($match);
        echo "Raw match: '" . str_replace(["\n", "\r"], ['\n', '\r'], $match) . "'\n";
        echo "Trimmed: '" . $trimmed . "'\n";
        echo "Is empty by our logic: " . (testIsEmpty($trimmed) ? 'YES' : 'NO') . "\n";
        
        if (!testIsEmpty($trimmed)) {
            $languageValue = $trimmed;
            echo "SELECTED THIS VALUE!\n";
            break;
        }
    }
    
    echo "\nFinal Language value: " . ($languageValue ? "'$languageValue'" : 'NULL (will use default)') . "\n";
} 