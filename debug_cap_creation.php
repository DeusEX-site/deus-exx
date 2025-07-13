<?php

require_once 'vendor/autoload.php';
require_once 'DynamicCapTestEngine.php';

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🚀 Отладка создания кап - Динамические тесты\n";
echo "==========================================\n\n";

try {
    // Создаем тестовый движок с включенным логированием
    $engine = new DynamicCapTestEngine(true);
    
    echo "📋 Тестовый движок инициализирован\n\n";
    
    // Простой тест создания одной капы
    $testCapData = [
        'affiliate' => 'testaffiliate',
        'recipient' => 'testrecipient', 
        'geo' => 'ru',
        'cap' => '25',
        'schedule' => '10-18',
        'total' => '100'
    ];
    
    echo "🎯 Запуск теста создания одной капы\n";
    echo "📊 Тестовые данные: " . json_encode($testCapData) . "\n\n";
    
    $result = $engine->testSingleCapCreation($testCapData);
    
    echo "\n📈 РЕЗУЛЬТАТ ТЕСТА:\n";
    echo "==================\n";
    echo "Успешно: " . ($result['success'] ? 'ДА' : 'НЕТ') . "\n";
    
    if (!$result['success']) {
        echo "Ошибки: " . json_encode($result['errors']) . "\n";
    }
    
    echo "Сообщение:\n" . $result['message'] . "\n\n";
    echo "Результат анализа: " . json_encode($result['analysis_result']) . "\n\n";
    
    if (isset($result['caps_created_in_db'])) {
        echo "Кап создано в БД: " . $result['caps_created_in_db'] . "\n";
    }
    
    if (isset($result['caps_history_created'])) {
        echo "Записей истории создано: " . $result['caps_history_created'] . "\n";
    }
    
    // Проверяем что действительно создалось в БД
    echo "\n🔍 ПРОВЕРКА БАЗЫ ДАННЫХ:\n";
    echo "========================\n";
    
    $totalCaps = \App\Models\Cap::count();
    $totalMessages = \App\Models\Message::count();
    $totalHistory = \App\Models\CapHistory::count();
    
    echo "Всего кап в БД: {$totalCaps}\n";
    echo "Всего сообщений в БД: {$totalMessages}\n";
    echo "Всего записей истории: {$totalHistory}\n\n";
    
    // Показываем созданные капы
    $createdCaps = \App\Models\Cap::orderBy('id', 'desc')->limit(5)->get();
    echo "Последние 5 кап:\n";
    foreach ($createdCaps as $cap) {
        echo "- ID: {$cap->id}, {$cap->affiliate_name} -> {$cap->recipient_name} ({$cap->geos[0]}, {$cap->cap_amounts[0]})\n";
    }
    
    echo "\n✅ Отладка завершена\n";
    
} catch (Exception $e) {
    echo "❌ ОШИБКА: " . $e->getMessage() . "\n";
    echo "📍 Стек ошибки:\n" . $e->getTraceAsString() . "\n";
} 