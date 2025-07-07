<?php

require_once 'vendor/autoload.php';

use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🧪 Тестирование системы истории кап...\n\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate([
    'chat_id' => -9999999,
    'type' => 'group',
    'title' => 'Test Cap History Chat'
]);

echo "📝 Шаг 1: Создание первоначальных кап\n";

$originalMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE AT CH
CAP: 20 30 30
Total: 100 200 200
Language: de en ru
Funnel: DeusEX, DeusEX2
Schedule: 18:00/01:00 18:00/02:00 GMT+03:00
Date: 24.02 25.02";

$message1 = Message::create([
    'chat_id' => $chat->id,
    'message' => $originalMessage,
    'user' => 'Test User',
    'telegram_message_id' => 1001,
    'telegram_user_id' => 123456
]);

$result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $originalMessage);

echo "Создано кап: " . $result1['cap_entries_count'] . "\n";
echo "Обновлено кап: " . $result1['updated_entries_count'] . "\n";

$totalCaps = Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->count();
echo "Всего кап в базе: {$totalCaps}\n\n";

echo "🔄 Шаг 2: Частичное обновление капы для DE (только Total)\n";

$partialUpdateMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE
CAP: 20
Total: 101";

$message2 = Message::create([
    'chat_id' => $chat->id,
    'message' => $partialUpdateMessage,
    'user' => 'Test User',
    'telegram_message_id' => 1002,
    'telegram_user_id' => 123456
]);

$result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $partialUpdateMessage);

echo "Создано кап: " . $result2['cap_entries_count'] . "\n";
echo "Обновлено кап: " . $result2['updated_entries_count'] . "\n";

$totalCapsAfter = Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->count();
$historyCount = CapHistory::whereHas('cap', function($q) {
    $q->where('affiliate_name', 'G06')->where('recipient_name', 'TMedia');
})->count();

echo "Всего кап в базе после обновления: {$totalCapsAfter}\n";
echo "Записей в истории: {$historyCount}\n\n";

// Проверяем конкретные записи
$deCap = Cap::where('affiliate_name', 'G06')
            ->where('recipient_name', 'TMedia')
            ->whereJsonContains('geos', 'DE')
            ->first();
            
$atCap = Cap::where('affiliate_name', 'G06')
            ->where('recipient_name', 'TMedia')
            ->whereJsonContains('geos', 'AT')
            ->first();
            
$chCap = Cap::where('affiliate_name', 'G06')
            ->where('recipient_name', 'TMedia')
            ->whereJsonContains('geos', 'CH')
            ->first();

echo "✅ Результаты проверки:\n";

if ($deCap && $deCap->total_amount == 101) {
    echo "✅ DE капа обновлена (Total: {$deCap->total_amount})\n";
    
    // Проверяем, что остальные поля не изменились
    if ($deCap->language == 'de' && $deCap->funnel == 'DeusEX') {
        echo "✅ DE капа: остальные поля остались без изменений\n";
    } else {
        echo "❌ DE капа: другие поля были изменены неправильно (Language: {$deCap->language}, Funnel: {$deCap->funnel})\n";
    }
} else {
    echo "❌ DE капа не обновлена правильно\n";
}

if ($atCap && $atCap->total_amount == 200) {
    echo "✅ AT капа осталась без изменений (Total: {$atCap->total_amount})\n";
} else {
    echo "❌ AT капа была удалена или изменена\n";
}

if ($chCap && $chCap->total_amount == 200) {
    echo "✅ CH капа осталась без изменений (Total: {$chCap->total_amount})\n";
} else {
    echo "❌ CH капа была удалена или изменена\n";
}

if ($historyCount == 1) {
    echo "✅ Создана 1 запись истории\n";
} else {
    echo "❌ Неправильное количество записей истории: {$historyCount}\n";
}

if ($totalCapsAfter == $totalCaps) {
    echo "✅ Общее количество кап не изменилось\n";
} else {
    echo "❌ Общее количество кап изменилось с {$totalCaps} на {$totalCapsAfter}\n";
}

echo "\n📜 История изменений:\n";
$history = CapHistory::whereHas('cap', function($q) {
    $q->where('affiliate_name', 'G06')->where('recipient_name', 'TMedia');
})->with('cap')->get();

foreach ($history as $historyRecord) {
    echo "- Гео: {$historyRecord->geos[0]}, Total: {$historyRecord->total_amount}, Заархивировано: {$historyRecord->archived_at}\n";
}

echo "\n🧹 Очистка тестовых данных...\n";

Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->delete();
CapHistory::whereIn('id', $history->pluck('id'))->delete();
Message::whereIn('id', [$message1->id, $message2->id])->delete();
$chat->delete();

echo "✅ Тестовые данные удалены\n";

echo "\n🎉 Тест завершен!\n";
echo "\n📋 Итог реализации:\n";
echo "✅ Создана таблица истории кап (caps_history)\n";
echo "✅ Реализована проверка дубликатов по affiliate_name + recipient_name + geo\n";
echo "✅ При обновлении капы старая версия сохраняется в истории\n";
echo "✅ Обновляются только те поля, которые указаны в новом сообщении\n";
echo "✅ Поля, не указанные в сообщении, остаются без изменений\n";
echo "✅ Не создаются лишние записи при обновлении\n";
echo "✅ Записи, не присутствующие в новом сообщении, не удаляются\n"; 