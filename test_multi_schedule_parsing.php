<?php
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\CapAnalysisService;

$service = new CapAnalysisService();

echo "Testing Multi-Schedule Parsing...\n\n";

// Тестовые случаи для множественных расписаний
$testCases = [
    [
        'name' => 'Two schedules separated by space with common timezone',
        'input' => "Affiliate: mbt internal\nRecipient: Global KZ\nCap: 20 30 30\nGeo: KZ1 KZ2 KZ3\nSchedule: 18:00/01:00 18:00/02:00 GMT+03:00\nDate: 24.02 25.02",
        'expected_count' => 3,
        'expected_schedules' => ['18:00/01:00', '18:00/02:00', '18:00/01:00']
    ],
    [
        'name' => 'Multiple schedules without minutes, space separated',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20\nGeo: DE RU\nSchedule: 10-19 10-17",
        'expected_count' => 2,
        'expected_schedules' => ['10:00/19:00', '10:00/17:00']
    ],
    [
        'name' => 'Multiple schedules without minutes, comma separated',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20\nGeo: DE RU\nSchedule: 10-19, 10-17",
        'expected_count' => 2,
        'expected_schedules' => ['10:00/19:00', '10:00/17:00']
    ],
    [
        'name' => 'Schedules with dots, space separated with timezone',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20\nGeo: DE RU\nSchedule: 8.30 - 14.30 9.30 - 14.30 +3",
        'expected_count' => 2,
        'expected_schedules' => ['8:30/14:30', '9:30/14:30']
    ],
    [
        'name' => 'Schedules with dots, comma separated with timezone',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20\nGeo: DE RU\nSchedule: 8.30 - 14.30, 9.30 - 14.30 +3",
        'expected_count' => 2,
        'expected_schedules' => ['8:30/14:30', '9:30/14:30']
    ],
    [
        'name' => 'Single schedule for multiple caps',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20 30\nGeo: DE RU FR\nSchedule: 10:00 - 19:00 GMT+03:00",
        'expected_count' => 3,
        'expected_schedules' => ['10:00/19:00', '10:00/19:00', '10:00/19:00']
    ],
    [
        'name' => 'Three schedules for three caps',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20 30\nGeo: DE RU FR\nSchedule: 10-19, 10-17, 10-20",
        'expected_count' => 3,
        'expected_schedules' => ['10:00/19:00', '10:00/17:00', '10:00/20:00']
    ],
    [
        'name' => 'Two schedules for three caps (default for missing)',
        'input' => "Affiliate: Test\nRecipient: Test\nCap: 10 20 30\nGeo: DE RU FR\nSchedule: 10-19 10-17",
        'expected_count' => 3,
        'expected_schedules' => ['10:00/19:00', '10:00/17:00', '24/7']
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
        echo "Parsed " . count($result) . " caps (expected: " . $test['expected_count'] . ")\n\n";
        
        $success = true;
        foreach ($result as $i => $cap) {
            echo "Cap #" . ($i + 1) . ":\n";
            echo "  Cap: " . $cap['cap_amount'] . "\n";
            echo "  Geo: " . implode(', ', $cap['geos']) . "\n";
            echo "  Schedule: " . $cap['schedule'] . "\n";
            echo "  Start time: " . ($cap['start_time'] ?? 'null') . "\n";
            echo "  End time: " . ($cap['end_time'] ?? 'null') . "\n";
            echo "  Timezone: " . ($cap['timezone'] ?? 'null') . "\n";
            echo "  Is 24/7: " . ($cap['is_24_7'] ? 'true' : 'false') . "\n";
            
            if (isset($test['expected_schedules'][$i])) {
                $expected = $test['expected_schedules'][$i];
                if ($cap['schedule'] !== $expected) {
                    echo "  ❌ ERROR: Expected schedule '" . $expected . "', got '" . $cap['schedule'] . "'\n";
                    $success = false;
                } else {
                    echo "  ✓ Schedule matches expected\n";
                }
            }
            echo "\n";
        }
        
        if ($success && count($result) === $test['expected_count']) {
            echo "✅ TEST PASSED\n";
        } else {
            echo "❌ TEST FAILED\n";
        }
    } else {
        echo "❌ Failed to parse message\n";
    }
    
    echo "\n";
}

// Дополнительный тест parseScheduleValues напрямую
echo str_repeat("=", 60) . "\n";
echo "Direct parseScheduleValues tests:\n";
echo str_repeat("=", 60) . "\n";

$scheduleTests = [
    "18:00/01:00 18:00/02:00 GMT+03:00",
    "10-19 10-17",
    "10-19, 10-17",
    "8.30 - 14.30 9.30 - 14.30 +3",
    "8.30 - 14.30, 9.30 - 14.30 +3",
    "10:00 - 19:00 GMT+03:00",
    "10-19, 10-17, 10-20"
];

$parseScheduleValues = $reflection->getMethod('parseScheduleValues');
$parseScheduleValues->setAccessible(true);

foreach ($scheduleTests as $scheduleStr) {
    echo "\nTesting: '$scheduleStr'\n";
    $parsedSchedules = $parseScheduleValues->invoke($service, $scheduleStr);
    echo "Parsed into " . count($parsedSchedules) . " schedules:\n";
    foreach ($parsedSchedules as $i => $schedule) {
        echo "  " . ($i + 1) . ": '$schedule'\n";
    }
}

echo "\nAll tests completed.\n"; 