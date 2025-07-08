<?php

// Простой тест для проверки обработки пустых полей
echo "Testing Empty Fields Handling...\n\n";

// Пример текста с дублирующимися пустыми полями (как на скриншоте)
$messageText = "Affiliate: mbt internal
Recipient: Global KZ
CAP : 30
Geo: KZ
Schedule: 8.30 - 14.30 +3

дополнительные поля, если надо :
Schedule:
Date:
Language:
Funnel:
Total:
Pending ACQ:";

echo "Input message:\n";
echo str_repeat("-", 50) . "\n";
echo $messageText . "\n";
echo str_repeat("-", 50) . "\n\n";

// Тестируем обработку Language
echo "Testing Language field:\n";
if (preg_match_all('/^Language:\s*(.*)$/m', $messageText, $matches)) {
    echo "Found " . count($matches[1]) . " Language fields\n";
    $languageValue = null;
    foreach ($matches[1] as $i => $match) {
        $trimmed = trim($match);
        echo "  Field " . ($i + 1) . ": '" . $match . "' -> trimmed: '" . $trimmed . "' -> empty: " . (empty($trimmed) ? "YES" : "NO") . "\n";
        if (!empty($trimmed)) {
            $languageValue = $trimmed;
            break;
        }
    }
    
    if (!empty($languageValue)) {
        echo "Result: Using value '$languageValue'\n";
    } else {
        echo "Result: All fields empty, using default 'en'\n";
    }
} else {
    echo "No Language field found\n";
}

echo "\n";

// Тестируем обработку Total
echo "Testing Total field:\n";
if (preg_match_all('/^Total:\s*(.*)$/m', $messageText, $matches)) {
    echo "Found " . count($matches[1]) . " Total fields\n";
    $totalValue = null;
    foreach ($matches[1] as $i => $match) {
        $trimmed = trim($match);
        echo "  Field " . ($i + 1) . ": '" . $match . "' -> trimmed: '" . $trimmed . "' -> empty: " . (empty($trimmed) ? "YES" : "NO") . "\n";
        if (!empty($trimmed)) {
            $totalValue = $trimmed;
            break;
        }
    }
    
    if (!empty($totalValue)) {
        echo "Result: Using value '$totalValue'\n";
    } else {
        echo "Result: All fields empty, using default -1 (infinity)\n";
    }
} else {
    echo "No Total field found\n";
}

echo "\n";

// Тестируем обработку Schedule
echo "Testing Schedule field:\n";
if (preg_match_all('/^Schedule:\s*(.*)$/m', $messageText, $matches)) {
    echo "Found " . count($matches[1]) . " Schedule fields\n";
    $scheduleValue = null;
    foreach ($matches[1] as $i => $match) {
        $trimmed = trim($match);
        echo "  Field " . ($i + 1) . ": '" . $match . "' -> trimmed: '" . $trimmed . "' -> empty: " . (empty($trimmed) ? "YES" : "NO") . "\n";
        if (!empty($trimmed)) {
            $scheduleValue = $trimmed;
            break;
        }
    }
    
    if (!empty($scheduleValue)) {
        echo "Result: Using value '$scheduleValue'\n";
    } else {
        echo "Result: All fields empty, using default '24/7'\n";
    }
} else {
    echo "No Schedule field found\n";
}

echo "\n";

// Тестируем обработку Funnel
echo "Testing Funnel field:\n";
if (preg_match_all('/^Funnel:\s*(.*)$/m', $messageText, $matches)) {
    echo "Found " . count($matches[1]) . " Funnel fields\n";
    $funnelValue = null;
    foreach ($matches[1] as $i => $match) {
        $trimmed = trim($match);
        echo "  Field " . ($i + 1) . ": '" . $match . "' -> trimmed: '" . $trimmed . "' -> empty: " . (empty($trimmed) ? "YES" : "NO") . "\n";
        if (!empty($trimmed)) {
            $funnelValue = $trimmed;
            break;
        }
    }
    
    if (!empty($funnelValue)) {
        echo "Result: Using value '$funnelValue'\n";
    } else {
        echo "Result: All fields empty, using default null\n";
    }
} else {
    echo "No Funnel field found\n";
}

echo "\n";

// Тестируем обработку Date
echo "Testing Date field:\n";
if (preg_match_all('/^Date:\s*(.*)$/m', $messageText, $matches)) {
    echo "Found " . count($matches[1]) . " Date fields\n";
    $dateValue = null;
    foreach ($matches[1] as $i => $match) {
        $trimmed = trim($match);
        echo "  Field " . ($i + 1) . ": '" . $match . "' -> trimmed: '" . $trimmed . "' -> empty: " . (empty($trimmed) ? "YES" : "NO") . "\n";
        if (!empty($trimmed)) {
            $dateValue = $trimmed;
            break;
        }
    }
    
    if (!empty($dateValue)) {
        echo "Result: Using value '$dateValue'\n";
    } else {
        echo "Result: All fields empty, using default null (infinity)\n";
    }
} else {
    echo "No Date field found\n";
}

echo "\n\nTest completed.\n"; 