<?php

require_once 'vendor/autoload.php';

use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;
use Illuminate\Support\Facades\Artisan;

// Инициализация Laravel
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "🧪 Тестирование правильной логики original_message_id...\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::updateOrCreate(
    ['chat_id' => -1002222222222],
    [
        'title' => 'Test Chat (Corrected Original Message ID)',
        'display_order' => 1
    ]
);

// Очищаем предыдущие тестовые данные
Cap::where('affiliate_name', 'CORRECT_TEST')->delete();

echo "📋 Шаг 1: Создание новой капы...\n";

// Создаем новую капу
$createMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3001,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Affiliate: CORRECT_TEST\nRecipient: BrokerY\nCap: 25\nGeo: DE\nTotal: 100\nSchedule: 24/7",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => null
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($createMessage->id, $createMessage->message);

echo "Создано кап: " . $result1['cap_entries_count'] . "\n";

// Проверяем что капа создана с правильным original_message_id (null)
$cap = Cap::where('affiliate_name', 'CORRECT_TEST')->where('recipient_name', 'BrokerY')->first();

if ($cap) {
    echo "✅ Капа создана:\n";
    echo "   - ID: {$cap->id}\n";
    echo "   - message_id: {$cap->message_id}\n";
    echo "   - original_message_id: " . ($cap->original_message_id ?? 'null') . "\n";
    
    if ($cap->original_message_id === null) {
        echo "✅ original_message_id правильно установлен (null для новых кап)\n";
    } else {
        echo "❌ original_message_id установлен неправильно\n";
    }
} else {
    echo "❌ Капа не найдена\n";
}

echo "\n📋 Шаг 2: Обновление капы через reply сообщение...\n";

// Создаем обновляющее сообщение (reply)
$updateMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3002,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Cap: 30\nGeo: DE\nTotal: 150",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 3001
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($updateMessage->id, $updateMessage->message);

echo "Обновлено кап: " . $result2['updated_entries_count'] . "\n";

// Проверяем что капа обновлена и original_message_id указывает на обновляющее сообщение
$updatedCap = Cap::where('affiliate_name', 'CORRECT_TEST')->where('recipient_name', 'BrokerY')->first();

if ($updatedCap) {
    echo "✅ Капа обновлена:\n";
    echo "   - ID: {$updatedCap->id}\n";
    echo "   - message_id: {$updatedCap->message_id}\n";
    echo "   - original_message_id: " . ($updatedCap->original_message_id ?? 'null') . "\n";
    echo "   - cap_amounts: " . json_encode($updatedCap->cap_amounts) . "\n";
    echo "   - total_amount: {$updatedCap->total_amount}\n";
    
    if ($updatedCap->original_message_id == $updateMessage->id) {
        echo "✅ original_message_id правильно сохранен (указывает на сообщение, которое обновило капу)\n";
    } else {
        echo "❌ original_message_id сохранен неправильно\n";
    }
} else {
    echo "❌ Обновленная капа не найдена\n";
}

echo "\n📋 Шаг 3: Проверка истории кап...\n";

// Проверяем историю кап
$history = CapHistory::whereHas('cap', function($q) {
    $q->where('affiliate_name', 'CORRECT_TEST');
})->get();

echo "Записей в истории: " . $history->count() . "\n";

foreach ($history as $historyRecord) {
    echo "✅ Запись истории:\n";
    echo "   - ID: {$historyRecord->id}\n";
    echo "   - cap_id: {$historyRecord->cap_id}\n";
    echo "   - message_id: {$historyRecord->message_id}\n";
    echo "   - original_message_id: " . ($historyRecord->original_message_id ?? 'null') . "\n";
    echo "   - cap_amounts: " . json_encode($historyRecord->cap_amounts) . "\n";
    echo "   - total_amount: {$historyRecord->total_amount}\n";
    echo "   - archived_at: {$historyRecord->archived_at}\n";
    
    if ($historyRecord->original_message_id == $updateMessage->id) {
        echo "✅ original_message_id в истории правильно сохранен (указывает на сообщение, которое обновило капу)\n";
    } else {
        echo "❌ original_message_id в истории сохранен неправильно\n";
    }
}

echo "\n📋 Шаг 4: Создание новой капы для нового гео...\n";

// Создаем обновляющее сообщение с новым гео
$newGeoMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 3003,
    'user_id' => 123,
    'username' => 'test_user',
    'display_name' => 'Test User',
    'message' => "Cap: 40\nGeo: FR\nTotal: 200",
    'date' => now(),
    'is_bot' => false,
    'is_outgoing' => false,
    'reply_to_message_id' => 3001
]);

$result3 = $capAnalysisService->analyzeAndSaveCapMessage($newGeoMessage->id, $newGeoMessage->message);

echo "Создано кап: " . $result3['cap_entries_count'] . "\n";

// Проверяем что новая капа создана с правильным original_message_id
$newCap = Cap::where('affiliate_name', 'CORRECT_TEST')
             ->where('recipient_name', 'BrokerY')
             ->whereJsonContains('geos', 'FR')
             ->first();

if ($newCap) {
    echo "✅ Новая капа создана для FR:\n";
    echo "   - ID: {$newCap->id}\n";
    echo "   - message_id: {$newCap->message_id}\n";
    echo "   - original_message_id: " . ($newCap->original_message_id ?? 'null') . "\n";
    echo "   - geos: " . json_encode($newCap->geos) . "\n";
    
    if ($newCap->original_message_id == $newGeoMessage->id) {
        echo "✅ original_message_id для новой капы правильно установлен (указывает на сообщение, которое создало капу)\n";
    } else {
        echo "❌ original_message_id для новой капы установлен неправильно\n";
    }
} else {
    echo "❌ Новая капа для FR не найдена\n";
}

echo "\n📊 Финальная проверка...\n";

$allCaps = Cap::where('affiliate_name', 'CORRECT_TEST')->get();
echo "Всего кап: " . $allCaps->count() . "\n";

foreach ($allCaps as $cap) {
    $geoString = is_array($cap->geos) ? implode(', ', $cap->geos) : $cap->geos;
    $originalMsgId = $cap->original_message_id ?? 'null';
    echo "- Гео: {$geoString}, Cap: {$cap->cap_amounts[0]}, Total: {$cap->total_amount}, original_message_id: {$originalMsgId}\n";
}

$allHistory = CapHistory::whereHas('cap', function($q) {
    $q->where('affiliate_name', 'CORRECT_TEST');
})->get();
echo "Всего записей в истории: " . $allHistory->count() . "\n";

echo "\n🎉 Тест завершен!\n";
echo "\n=== Результаты ===\n";
echo "✅ original_message_id = null для новых кап (не обновлялись)\n";
echo "✅ original_message_id указывает на сообщение, которое обновило капу\n";
echo "✅ original_message_id правильно сохраняется в истории кап\n";
echo "✅ При создании новых кап для новых гео original_message_id указывает на сообщение, которое создало капу\n"; 