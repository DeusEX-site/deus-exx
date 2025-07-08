<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\CapAnalysisService;

$service = new CapAnalysisService();

echo "Testing Empty Fields with Full Parsing Logic...\n\n";

// Тестовые случаи с пустыми полями
$testCases = [
    [
        'name' => 'Message with empty fields (as shown in screenshot)',
        'input' => "Affiliate: mbt internal\nRecipient: Global KZ\nCAP: 20 30 30\nGeo: KZ1 KZ2 KZ3\nSchedule: 18:00/01:00 18:00/02:00 GMT+03:00\nDate: 24.02 25.02\n\nдополнительные поля, если надо :\nSchedule:\nDate:\nLanguage:\nFunnel:\nTotal:\nPending ACQ:",
        'expected_defaults' => [
            'language' => 'en',
            'funnel' => null,
            'total_amount' => -1
        ]
    ],
    [
        'name' => 'Message with only empty optional fields',
        'input' => "Affiliate: Test\nRecipient: Test\nCAP: 10\nGeo: DE\nSchedule:\nLanguage:\nFunnel:\nTotal:",
        'expected_defaults' => [
            'language' => 'en',
            'funnel' => null,
            'total_amount' => -1,
            'schedule' => '24/7'
        ]
    ],
    [
        'name' => 'Message with missing optional fields',
        'input' => "Affiliate: Test\nRecipient: Test\nCAP: 10\nGeo: DE",
        'expected_defaults' => [
            'language' => 'en',
            'funnel' => null,
            'total_amount' => -1,
            'schedule' => '24/7'
        ]
    ]
];

foreach ($testCases as $test) {
    echo str_repeat("=", 60) . "\n";
    echo "Test: " . $test['name'] . "\n";
    echo str_repeat("-", 60) . "\n";
    echo "Input:\n" . $test['input'] . "\n";
    echo str_repeat("-", 60) . "\n";
    
    // Используем reflection для доступа к приватному методу
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('parseStandardCapMessage');
    $method->setAccessible(true);
    
    $result = $method->invoke($service, $test['input']);
    
    if ($result && is_array($result)) {
        echo "Parsed " . count($result) . " caps\n\n";
        
        $success = true;
        foreach ($result as $i => $cap) {
            echo "Cap #" . ($i + 1) . ":\n";
            echo "  Cap: " . $cap['cap_amount'] . "\n";
            echo "  Geo: " . implode(', ', $cap['geos']) . "\n";
            echo "  Language: " . $cap['language'] . "\n";
            echo "  Funnel: " . ($cap['funnel'] ?? 'null') . "\n";
            echo "  Total: " . $cap['total_amount'] . "\n";
            echo "  Schedule: " . $cap['schedule'] . "\n";
            echo "  Is 24/7: " . ($cap['is_24_7'] ? 'true' : 'false') . "\n";
            
            // Проверяем значения по умолчанию
            foreach ($test['expected_defaults'] as $field => $expectedValue) {
                $actualValue = $cap[$field];
                if ($actualValue !== $expectedValue) {
                    echo "  ❌ ERROR: Expected $field = '" . ($expectedValue ?? 'null') . "', got '" . ($actualValue ?? 'null') . "'\n";
                    $success = false;
                } else {
                    echo "  ✓ $field matches expected default\n";
                }
            }
            echo "\n";
        }
        
        if ($success) {
            echo "✅ TEST PASSED\n";
        } else {
            echo "❌ TEST FAILED\n";
        }
    } else {
        echo "❌ Failed to parse message\n";
    }
    
    echo "\n";
}

echo "\nAll tests completed.\n"; 