<?php

require_once 'vendor/autoload.php';

use App\Models\Cap;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Тест правильной логики original_message_id\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::updateOrCreate(
    ['chat_id' => -1003333333333],
    ['title' => 'Test Chat', 'display_order' => 1]
);

// Очищаем данные
Cap::where('affiliate_name', 'TEST_CORRECT')->delete();

// 1. Создаем новую капу
$createMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 4001,
    'user_id' => 123,
    'message' => "Affiliate: TEST_CORRECT\nRecipient: BrokerZ\nCap: 25\nGeo: DE",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($createMessage->id, $createMessage->message);
echo "Создано кап: " . $result1['cap_entries_count'] . "\n";

$cap = Cap::where('affiliate_name', 'TEST_CORRECT')->first();
if ($cap && $cap->original_message_id === null) {
    echo "✅ original_message_id = null для новой капы\n";
} else {
    echo "❌ original_message_id установлен неправильно\n";
}

// 2. Обновляем капу
$updateMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 4002,
    'user_id' => 123,
    'message' => "Cap: 30\nGeo: DE",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 4001
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage->id, $updateMessage->message);
echo "Обновлено кап: " . $result2['updated_entries_count'] . "\n";

$updatedCap = Cap::where('affiliate_name', 'TEST_CORRECT')->first();
if ($updatedCap && $updatedCap->original_message_id == $updateMessage->id) {
    echo "✅ original_message_id указывает на обновляющее сообщение\n";
} else {
    echo "❌ original_message_id не указывает на обновляющее сообщение\n";
}

echo "\n�� Тест завершен!\n"; 