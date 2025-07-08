<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Тестирование парсинга расписания ===\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate(
    ['chat_id' => 'test_schedule_chat'],
    ['display_name' => 'Test Schedule Chat']
);

// Очищаем старые тестовые данные
Message::where('chat_id', $chat->id)->delete();

// Тест 1: Формат с точками и пробелами
echo "1. Тест формата '8.30 - 14.30 +3':\n";
$message1 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3001,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: mbt internal\nRecipient: Global KZ\nCAP: 30\nGeo: KZ\nSchedule: 8.30 - 14.30 +3",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $message1->message);
echo "Создано кап: " . $result1['cap_entries_count'] . "\n";

$cap1 = Cap::where('affiliate_name', 'mbt internal')->where('recipient_name', 'Global KZ')->first();
if ($cap1) {
    echo "✓ Капа создана:\n";
    echo "  - Schedule: {$cap1->schedule}\n";
    echo "  - Start time: {$cap1->start_time}\n";
    echo "  - End time: {$cap1->end_time}\n";
    echo "  - Timezone: {$cap1->timezone}\n";
} else {
    echo "✗ Капа не создана\n";
}
echo "\n";

// Тест 2: Формат без минут
echo "2. Тест формата '10-19 +3':\n";
$message2 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3002,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: Test2\nRecipient: Broker2\nCap: 50\nGeo: DE\nSchedule: 10-19 +3",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $message2->message);
echo "Создано кап: " . $result2['cap_entries_count'] . "\n";

$cap2 = Cap::where('affiliate_name', 'Test2')->where('recipient_name', 'Broker2')->first();
if ($cap2) {
    echo "✓ Капа создана:\n";
    echo "  - Schedule: {$cap2->schedule}\n";
    echo "  - Start time: {$cap2->start_time}\n";
    echo "  - End time: {$cap2->end_time}\n";
    echo "  - Timezone: {$cap2->timezone}\n";
} else {
    echo "✗ Капа не создана\n";
}
echo "\n";

// Тест 3: Дублирующиеся поля Schedule
echo "3. Тест с дублирующимися полями Schedule:\n";
$message3 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3003,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: Test3\nRecipient: Broker3\nCap: 100\nGeo: AT\nSchedule: 8.30 - 14.30 +3\nдополнительные поля, если надо :\nSchedule:",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result3 = $capAnalysisService->analyzeAndSaveCapMessage($message3->id, $message3->message);
echo "Создано кап: " . $result3['cap_entries_count'] . "\n";

$cap3 = Cap::where('affiliate_name', 'Test3')->where('recipient_name', 'Broker3')->first();
if ($cap3) {
    echo "✓ Капа создана:\n";
    echo "  - Schedule: {$cap3->schedule}\n";
    echo "  - Start time: {$cap3->start_time}\n";
    echo "  - End time: {$cap3->end_time}\n";
    echo "  - Timezone: {$cap3->timezone}\n";
} else {
    echo "✗ Капа не создана\n";
}
echo "\n";

// Тест 4: Стандартный формат с GMT
echo "4. Тест стандартного формата '18:00/01:00 GMT+03:00':\n";
$message4 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3004,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: Test4\nRecipient: Broker4\nCap: 200\nGeo: CH\nSchedule: 18:00/01:00 GMT+03:00",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result4 = $capAnalysisService->analyzeAndSaveCapMessage($message4->id, $message4->message);
echo "Создано кап: " . $result4['cap_entries_count'] . "\n";

$cap4 = Cap::where('affiliate_name', 'Test4')->where('recipient_name', 'Broker4')->first();
if ($cap4) {
    echo "✓ Капа создана:\n";
    echo "  - Schedule: {$cap4->schedule}\n";
    echo "  - Start time: {$cap4->start_time}\n";
    echo "  - End time: {$cap4->end_time}\n";
    echo "  - Timezone: {$cap4->timezone}\n";
} else {
    echo "✗ Капа не создана\n";
}
echo "\n";

// Тест 5: Несколько расписаний
echo "5. Тест с несколькими расписаниями:\n";
$message5 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3005,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: Test5\nRecipient: Broker5\nCap: 150 250\nGeo: FR ES\nSchedule: 9:00/18:00 GMT+01:00, 10-20 +2",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result5 = $capAnalysisService->analyzeAndSaveCapMessage($message5->id, $message5->message);
echo "Создано кап: " . $result5['cap_entries_count'] . "\n";

$caps5 = Cap::where('affiliate_name', 'Test5')->where('recipient_name', 'Broker5')->get();
foreach ($caps5 as $cap) {
    echo "✓ Капа для гео {$cap->geos[0]}:\n";
    echo "  - Schedule: {$cap->schedule}\n";
    echo "  - Start time: {$cap->start_time}\n";
    echo "  - End time: {$cap->end_time}\n";
    echo "  - Timezone: {$cap->timezone}\n";
}

echo "\n=== Тест завершен ===\n"; 