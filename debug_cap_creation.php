<?php

require_once 'bootstrap/app.php';

use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate([
    'telegram_chat_id' => -999999,
    'chat_name' => 'Debug Test Chat',
    'display_order' => 999
]);

echo "🔍 Отладка создания капы...\n\n";

// Тест 1: Стандартное сообщение с капой
echo "📋 Тест 1: Стандартное сообщение с капой\n";
$testMessage1 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 999990,
    'user_id' => 1,
    'display_name' => 'Debug User',
    'message' => "Affiliate: DebugAffiliate\nRecipient: DebugRecipient\nCap: 25\nGeo: DE"
]);

echo "Текст сообщения:\n{$testMessage1->message}\n\n";

try {
    $result1 = $capAnalysisService->analyzeAndSaveCapMessage($testMessage1->id, $testMessage1->message);
    
    echo "Результат:\n";
    print_r($result1);
    
    if (isset($result1['cap_entries_count']) && $result1['cap_entries_count'] > 0) {
        echo "✅ Капа успешно создана!\n";
    } else {
        echo "❌ Капа НЕ создана!\n";
        if (isset($result1['error'])) {
            echo "Ошибка: {$result1['error']}\n";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Тест 2: Команда статуса
echo "📋 Тест 2: Команда статуса (простая)\n";
$testMessage2 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 999991,
    'reply_to_message_id' => $testMessage1->message_id,
    'user_id' => 1,
    'display_name' => 'Debug User',
    'message' => "STOP"
]);

echo "Текст сообщения: {$testMessage2->message}\n";
echo "Reply to: {$testMessage2->reply_to_message_id}\n\n";

try {
    $result2 = $capAnalysisService->analyzeAndSaveCapMessage($testMessage2->id, $testMessage2->message);
    
    echo "Результат:\n";
    print_r($result2);
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Тест 3: Обновление полей
echo "📋 Тест 3: Обновление полей через reply\n";
$testMessage3 = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 999992,
    'reply_to_message_id' => $testMessage1->message_id,
    'user_id' => 1,
    'display_name' => 'Debug User',
    'message' => "Geo: DE\nCap: 30\nSchedule: 10:00/18:00"
]);

echo "Текст сообщения:\n{$testMessage3->message}\n";
echo "Reply to: {$testMessage3->reply_to_message_id}\n\n";

try {
    $result3 = $capAnalysisService->analyzeAndSaveCapMessage($testMessage3->id, $testMessage3->message);
    
    echo "Результат:\n";
    print_r($result3);
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

echo "\n" . str_repeat("-", 50) . "\n\n";

// Проверяем что создалось в базе данных
echo "📊 Проверка базы данных:\n";
$caps = Cap::where('affiliate_name', 'DebugAffiliate')->get();
echo "Найдено кап: " . $caps->count() . "\n";
foreach ($caps as $cap) {
    echo "- {$cap->affiliate_name} → {$cap->recipient_name} ({$cap->geos[0]}, {$cap->cap_amounts[0]}) - {$cap->status}\n";
}

// Удаляем тестовые данные
echo "\n🧹 Удаление тестовых данных...\n";
Cap::where('affiliate_name', 'DebugAffiliate')->delete();
$testMessage1->delete();
$testMessage2->delete();
$testMessage3->delete();
$chat->delete();

echo "✅ Тестовые данные удалены.\n"; 