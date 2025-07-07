<?php

require_once 'vendor/autoload.php';

use App\Services\CapAnalysisService;

$service = new CapAnalysisService();

echo "=== ОТЛАДКА ПАРСИНГА SCHEDULE ===\n\n";

// Создаем рефлексию для доступа к приватным методам
$reflection = new ReflectionClass($service);
$parseScheduleValues = $reflection->getMethod('parseScheduleValues');
$parseScheduleValues->setAccessible(true);

$parseScheduleTime = $reflection->getMethod('parseScheduleTime');
$parseScheduleTime->setAccessible(true);

$testSchedule = "18:00/01:00 18:00/02:00 GMT+03:00";

echo "ТЕСТИРУЕМ: '$testSchedule'\n\n";

echo "1. parseScheduleValues() результат:\n";
$schedules = $parseScheduleValues->invoke($service, $testSchedule);
var_dump($schedules);

echo "\n2. parseScheduleTime() для каждого элемента:\n";
foreach ($schedules as $index => $schedule) {
    echo "\nЭлемент $index: '$schedule'\n";
    $result = $parseScheduleTime->invoke($service, $schedule);
    echo "start_time: " . ($result['start_time'] ?? 'NULL') . "\n";
    echo "end_time: " . ($result['end_time'] ?? 'NULL') . "\n";
    echo "timezone: " . ($result['timezone'] ?? 'NULL') . "\n";
    echo "schedule: " . $result['schedule'] . "\n";
}

echo "\n=== ТЕСТ ПОЛНОГО СООБЩЕНИЯ ===\n";
$fullMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE AT CH
Cap: 15 20 30
Schedule: 18:00/01:00 18:00/02:00 GMT+03:00";

$result = $service->analyzeCapMessage($fullMessage);
if ($result && isset($result['combinations'])) {
    foreach ($result['combinations'] as $index => $combo) {
        echo "\nЗапись $index:\n";
        echo "  schedule: " . $combo['schedule'] . "\n";
        echo "  start_time: " . ($combo['start_time'] ?? 'NULL') . "\n";
        echo "  end_time: " . ($combo['end_time'] ?? 'NULL') . "\n";
        echo "  timezone: " . ($combo['timezone'] ?? 'NULL') . "\n";
    }
} 