<?php

require_once 'bootstrap/app.php';

use App\Services\CapAnalysisService;
use App\Models\Message;
use App\Models\Chat;

echo "🧪 ТЕСТ: Проверка исправленной логики cap/geo и funnel\n";
echo "═══════════════════════════════════════════════════════\n\n";

$capService = new CapAnalysisService();

// Создаем тестовый чат и сообщения
$chat = Chat::firstOrCreate([
    'chat_id' => 999999999,
    'type' => 'group',
    'title' => 'Test Logic Chat',
    'is_active' => true
]);

$testCases = [
    [
        'name' => 'Равное количество cap/geo',
        'text' => "affiliate: TestAffiliate\nrecipient: TestBroker\ncap: 100 200 300\ngeo: RU UA DE",
        'expected' => 'success',
        'description' => 'cap: [100,200,300] + geo: [RU,UA,DE] = 1 запись с массивами'
    ],
    [
        'name' => 'Одна cap, несколько geo',
        'text' => "affiliate: TestAffiliate\nrecipient: TestBroker\ncap: 100\ngeo: RU UA DE",
        'expected' => 'success', 
        'description' => 'cap: [100] + geo: [RU,UA,DE] = 1 запись с размноженной cap'
    ],
    [
        'name' => 'Несколько cap, одно geo',
        'text' => "affiliate: TestAffiliate\nrecipient: TestBroker\ncap: 100 200 300\ngeo: RU",
        'expected' => 'success',
        'description' => 'cap: [100,200,300] + geo: [RU] = 1 запись с размноженным geo'
    ],
    [
        'name' => 'Несовпадающие количества (должно отклониться)',
        'text' => "affiliate: TestAffiliate\nrecipient: TestBroker\ncap: 100 200\ngeo: RU UA DE",
        'expected' => 'failure',
        'description' => 'cap: [100,200] + geo: [RU,UA,DE] = отклонено (разные количества, ни одно не равно 1)'
    ],
    [
        'name' => 'Проблемный тест из вашего примера',
        'text' => "affiliate: XYZ Company\nrecipient: BinaryBroker\ncap: 25 50 75 100\ngeo: FR IT ES",
        'expected' => 'failure',
        'description' => 'cap: [25,50,75,100] + geo: [FR,IT,ES] = должно отклониться'
    ],
    [
        'name' => 'Тест funnel как массив',
        'text' => "affiliate: TestAffiliate\nrecipient: TestBroker\ncap: 100\ngeo: RU\nfunnel: crypto, forex, binary",
        'expected' => 'success',
        'description' => 'Тест funnel как массива'
    ],
    [
        'name' => 'Правильный порядок полей',
        'text' => "affiliate: mbt internal\nrecipient: Global KZ\ncap: 50\ngeo: US UK CA",
        'expected' => 'success',
        'description' => 'Исправленный порядок полей'
    ]
];

$successCount = 0;
$failureCount = 0;

foreach ($testCases as $index => $test) {
    echo "📝 ТЕСТ #" . ($index + 1) . ": {$test['name']}\n";
    echo "   Сообщение: " . str_replace("\n", "\\n", $test['text']) . "\n";
    echo "   Ожидание: {$test['expected']}\n";
    echo "   Описание: {$test['description']}\n";
    
    // Создаем сообщение
    $message = Message::create([
        'message_id' => 1000 + $index,
        'chat_id' => $chat->id,
        'message' => $test['text'],
        'display_name' => 'TestUser',
        'timestamp' => now(),
        'is_outgoing' => false
    ]);
    
    // Анализируем сообщение
    $result = $capService->analyzeAndSaveCapMessage($message->id, $test['text']);
    
    $actualResult = ($result['cap_entries_count'] > 0) ? 'success' : 'failure';
    
    if ($actualResult === $test['expected']) {
        echo "   ✅ КОРРЕКТНО: получен {$actualResult}\n";
        $successCount++;
        
        // Для успешных случаев показываем детали
        if ($actualResult === 'success') {
            $caps = \App\Models\Cap::where('message_id', $message->id)->get();
            foreach ($caps as $cap) {
                echo "   📊 Создана cap: amounts=" . json_encode($cap->cap_amounts) . 
                     ", geos=" . json_encode($cap->geos) . 
                     ", funnels=" . json_encode($cap->funnels) . "\n";
            }
        }
    } else {
        echo "   ❌ НЕКОРРЕКТНО: ожидался {$test['expected']}, получен {$actualResult}\n";
        $failureCount++;
    }
    echo "\n";
}

echo "🎯 РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ:\n";
echo "✅ Корректных: {$successCount}\n";
echo "❌ Некорректных: {$failureCount}\n";
echo "📊 Точность: " . round(($successCount / count($testCases)) * 100, 1) . "%\n\n";

if ($failureCount === 0) {
    echo "🎉 ВСЕ ТЕСТЫ ПРОШЛИ! Логика работает корректно.\n";
} else {
    echo "⚠️ Есть проблемы в логике, требуется доработка.\n";
}

// Очистка тестовых данных
Message::where('chat_id', $chat->id)->delete();
\App\Models\Cap::whereIn('message_id', range(1000, 1000 + count($testCases)))->delete();
$chat->delete();

echo "\n🧹 Тестовые данные очищены.\n"; 