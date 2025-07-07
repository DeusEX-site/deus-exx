<?php

// Простой тест для проверки команд RUN и RESTORE

require_once 'vendor/autoload.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Message;
use App\Models\Chat;
use App\Models\Cap;
use App\Services\CapAnalysisService;

echo "🧪 Тестирование команд RUN и RESTORE...\n";

$capAnalysisService = new CapAnalysisService();

// Создаем тестовый чат
$chat = Chat::firstOrCreate([
    'chat_id' => -1001234567890,
    'display_name' => 'Test Chat Debug',
    'display_order' => 1
]);

// Очищаем предыдущие тестовые данные
Cap::where('affiliate_name', 'TestAff')->delete();

echo "1. Создание тестовой капы...\n";

// Создаем капу
$createMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2001,
    'user_id' => 1,
    'display_name' => 'Test User',
    'message' => "Affiliate: TestAff\nRecipient: TestRec\nCap: 10\nGeo: US"
]);

$result = $capAnalysisService->analyzeAndSaveCapMessage($createMessage->id, $createMessage->message);
echo "✅ Создана капа: " . json_encode($result) . "\n";

// Получаем созданную капу
$cap = Cap::where('affiliate_name', 'TestAff')->first();
echo "✅ Капа найдена: ID {$cap->id}, статус {$cap->status}\n";

echo "2. Команда STOP...\n";

// Останавливаем капу
$stopMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2002,
    'reply_to_message_id' => $createMessage->id,
    'user_id' => 1,
    'display_name' => 'Test User',
    'message' => "STOP"
]);

$result = $capAnalysisService->analyzeAndSaveCapMessage($stopMessage->id, $stopMessage->message);
echo "Результат STOP: " . json_encode($result) . "\n";

$cap->refresh();
echo "Статус после STOP: {$cap->status}\n";

echo "3. Команда RUN...\n";

// Запускаем капу обратно
$runMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2003,
    'reply_to_message_id' => $stopMessage->id, // Отвечаем на STOP
    'user_id' => 1,
    'display_name' => 'Test User',
    'message' => "RUN"
]);

$result = $capAnalysisService->analyzeAndSaveCapMessage($runMessage->id, $runMessage->message);
echo "Результат RUN: " . json_encode($result) . "\n";

$cap->refresh();
echo "Статус после RUN: {$cap->status}\n";

echo "4. Команда DELETE...\n";

// Удаляем капу
$deleteMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2004,
    'reply_to_message_id' => $runMessage->id, // Отвечаем на RUN
    'user_id' => 1,
    'display_name' => 'Test User',
    'message' => "DELETE"
]);

$result = $capAnalysisService->analyzeAndSaveCapMessage($deleteMessage->id, $deleteMessage->message);
echo "Результат DELETE: " . json_encode($result) . "\n";

$cap->refresh();
echo "Статус после DELETE: {$cap->status}\n";

echo "5. Команда RESTORE...\n";

// Восстанавливаем капу
$restoreMessage = Message::create([
    'chat_id' => $chat->id,
    'message_id' => 2005,
    'reply_to_message_id' => $deleteMessage->id, // Отвечаем на DELETE
    'user_id' => 1,
    'display_name' => 'Test User',
    'message' => "RESTORE"
]);

$result = $capAnalysisService->analyzeAndSaveCapMessage($restoreMessage->id, $restoreMessage->message);
echo "Результат RESTORE: " . json_encode($result) . "\n";

$cap->refresh();
echo "Статус после RESTORE: {$cap->status}\n";

echo "🎉 Тест завершен!\n";

// Очистка
Cap::where('affiliate_name', 'TestAff')->delete();
Message::whereIn('message_id', [2001, 2002, 2003, 2004, 2005])->delete(); 