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

echo "=== Тестирование команд статуса для множественных кап ===\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate(
    ['chat_id' => 'test_chat_status'],
    ['display_name' => 'Test Status Chat']
);

// Очищаем старые тестовые данные
Message::where('chat_id', $chat->id)->delete();

echo "1. Создание сообщения с несколькими капами:\n";
$originalMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2001,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: STATUS_TEST\nRecipient: BrokerX\nCap: 100 200 300\nGeo: DE AT CH\nTotal: 500 1000 -\nLanguage: de de fr",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($originalMessage->id, $originalMessage->message);

echo "Создано кап: " . $result1['cap_entries_count'] . "\n";

// Проверяем созданные капы
$caps = Cap::where('affiliate_name', 'STATUS_TEST')->where('recipient_name', 'BrokerX')->get();
echo "Всего кап в базе: " . $caps->count() . "\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Статус: {$cap->status}\n";
}
echo "\n";

echo "2. Простая команда STOP (должна применяться ко всем капам):\n";
$stopMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2002,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "STOP",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 2001
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($stopMessage->id, $stopMessage->message);

echo "Обновлено кап: " . ($result2['updated_entries_count'] ?? 0) . "\n";
if (isset($result2['message'])) {
    echo "Сообщение: " . $result2['message'] . "\n";
}
if (isset($result2['error'])) {
    echo "Ошибка: " . $result2['error'] . "\n";
}

// Проверяем статусы
$caps = Cap::where('affiliate_name', 'STATUS_TEST')->where('recipient_name', 'BrokerX')->get();
echo "\nПроверка статусов после STOP:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Статус: {$cap->status}\n";
}
echo "\n";

echo "3. Команда RUN с указанием конкретного Geo:\n";
$runWithGeoMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2003,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Geo: DE\nRUN",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 2001
]);

$result3 = $capAnalysisService->analyzeAndSaveCapMessage($runWithGeoMessage->id, $runWithGeoMessage->message);

echo "Обновлено кап: " . ($result3['updated_entries_count'] ?? 0) . "\n";
if (isset($result3['message'])) {
    echo "Сообщение: " . $result3['message'] . "\n";
}

// Проверяем статусы
$caps = Cap::where('affiliate_name', 'STATUS_TEST')->where('recipient_name', 'BrokerX')->get();
echo "\nПроверка статусов после RUN для DE:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Статус: {$cap->status}\n";
}
echo "\n";

echo "4. Команда DELETE без указания Geo (должна удалить все активные капы):\n";
$deleteMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2004,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "DELETE",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 2001
]);

$result4 = $capAnalysisService->analyzeAndSaveCapMessage($deleteMessage->id, $deleteMessage->message);

echo "Обновлено кап: " . ($result4['updated_entries_count'] ?? 0) . "\n";
if (isset($result4['message'])) {
    echo "Сообщение: " . $result4['message'] . "\n";
}

// Проверяем статусы
$caps = Cap::where('affiliate_name', 'STATUS_TEST')->where('recipient_name', 'BrokerX')->get();
echo "\nПроверка статусов после DELETE:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Статус: {$cap->status}\n";
}
echo "\n";

echo "5. Команда RESTORE для конкретного Geo:\n";
$restoreMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2005,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Geo: AT\nRESTORE",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 2001
]);

$result5 = $capAnalysisService->analyzeAndSaveCapMessage($restoreMessage->id, $restoreMessage->message);

echo "Обновлено кап: " . ($result5['updated_entries_count'] ?? 0) . "\n";
if (isset($result5['message'])) {
    echo "Сообщение: " . $result5['message'] . "\n";
}

// Проверяем финальные статусы
$caps = Cap::where('affiliate_name', 'STATUS_TEST')->where('recipient_name', 'BrokerX')->get();
echo "\nФинальная проверка статусов:\n";
foreach ($caps as $cap) {
    echo "- Гео: {$cap->geos[0]}, Статус: {$cap->status}\n";
}

// Проверяем историю изменений
echo "\n6. Проверка истории изменений:\n";
$historyCount = CapHistory::whereHas('cap', function($q) {
    $q->where('affiliate_name', 'STATUS_TEST');
})->count();
echo "Всего записей в истории: {$historyCount}\n";

echo "\n=== Тест завершен ===\n"; 