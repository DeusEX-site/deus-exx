<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Models\CapHistory;

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Тестирование системы обновления через Reply ===\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate(
    ['chat_id' => 'test_chat_reply'],
    ['display_name' => 'Test Reply Chat']
);

// Очищаем старые тестовые данные
Message::where('chat_id', $chat->id)->delete();

echo "1. Создание исходной капы с несколькими гео:\n";
$originalMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1001,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: TEST01\nRecipient: Broker1\nCap: 100 200 300\nGeo: DE AT CH\nTotal: 500 1000 -\nLanguage: de de fr\nSchedule: 10:00/19:00 GMT+01:00, 24/7, 12:00/20:00 GMT+02:00",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($originalMessage->id, $originalMessage->message);

echo "Создано кап: " . $result1['cap_entries_count'] . "\n\n";

// Проверяем созданные капы
$caps = Cap::where('affiliate_name', 'TEST01')->where('recipient_name', 'Broker1')->get();
echo "Всего кап в базе: " . $caps->count() . "\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Кап: {$cap->cap_amounts[0]}, Total: {$cap->total_amount}, Language: {$cap->language}, Schedule: {$cap->schedule}\n";
}
echo "\n";

echo "2. Обновление через reply с указанием всех гео:\n";
$updateMessage1 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1002,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Geo: DE AT CH\nTotal: 600 1200 2000\nPending ACQ: yes, no, yes",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 1001
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage1->id, $updateMessage1->message);

echo "Обновлено кап: " . $result2['updated_entries_count'] . "\n";
if (isset($result2['message'])) {
    echo "Сообщение: " . $result2['message'] . "\n";
}
echo "\n";

// Проверяем обновленные капы
$caps = Cap::where('affiliate_name', 'TEST01')->where('recipient_name', 'Broker1')->get();
echo "Проверка после обновления:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Total: {$cap->total_amount}, Pending ACQ: " . ($cap->pending_acq ? 'yes' : 'no') . "\n";
}
echo "\n";

echo "3. Обновление через reply с частичным указанием гео:\n";
$updateMessage2 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1003,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Geo: DE CH\nCap: 150 350\nSchedule: 24/7",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 1001
]);

$result3 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage2->id, $updateMessage2->message);

echo "Обновлено кап: " . $result3['updated_entries_count'] . "\n";
if (isset($result3['message'])) {
    echo "Сообщение: " . $result3['message'] . "\n";
}
echo "\n";

// Проверяем обновленные капы
$caps = Cap::where('affiliate_name', 'TEST01')->where('recipient_name', 'Broker1')->get();
echo "Проверка после частичного обновления:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Кап: {$cap->cap_amounts[0]}, Schedule: {$cap->schedule}\n";
}
echo "\n";

echo "4. Создание капы с одним гео:\n";
$singleGeoMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1004,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: TEST02\nRecipient: Broker2\nCap: 50\nGeo: FR\nTotal: 100\nLanguage: fr",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result4 = $capAnalysisService->analyzeAndSaveCapMessage($singleGeoMessage->id, $singleGeoMessage->message);

echo "Создано кап: " . $result4['cap_entries_count'] . "\n";

$frCap = Cap::where('affiliate_name', 'TEST02')->where('recipient_name', 'Broker2')->first();
echo "Создана капа: Гео: {$frCap->geos[0]}, Кап: {$frCap->cap_amounts[0]}, Total: {$frCap->total_amount}\n\n";

echo "5. Обновление капы с одним гео БЕЗ указания Geo:\n";
$updateMessage3 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1005,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Cap: 75\nTotal: 150\nSchedule: 09:00/18:00 GMT+01:00",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 1004
]);

$result5 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage3->id, $updateMessage3->message);

echo "Обновлено кап: " . $result5['updated_entries_count'] . "\n";
if (isset($result5['message'])) {
    echo "Сообщение: " . $result5['message'] . "\n";
}
if (isset($result5['error'])) {
    echo "Ошибка: " . $result5['error'] . "\n";
}
echo "\n";

// Проверяем обновленную капу
$frCap = Cap::where('affiliate_name', 'TEST02')->where('recipient_name', 'Broker2')->first();
echo "Проверка после обновления без Geo:\n";
echo "- Гео: {$frCap->geos[0]}, Кап: {$frCap->cap_amounts[0]}, Total: {$frCap->total_amount}, Schedule: {$frCap->schedule}\n\n";

echo "6. Попытка обновить капу с несколькими гео БЕЗ указания Geo (должна быть ошибка):\n";
$updateMessage4 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 1006,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Total: 999\nLanguage: en",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 1001
]);

$result6 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage4->id, $updateMessage4->message);

if (isset($result6['error'])) {
    echo "✓ Ожидаемая ошибка: " . $result6['error'] . "\n";
} else {
    echo "✗ Ошибка не получена, но должна быть!\n";
}
echo "\n";

// Проверяем историю изменений
echo "7. Проверка истории изменений:\n";
$historyCount = CapHistory::count();
echo "Всего записей в истории: {$historyCount}\n";

echo "\n=== Тест завершен ===\n"; 