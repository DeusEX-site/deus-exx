<?php

require_once 'bootstrap/app.php';

use App\Models\Chat;

echo "🧪 Creating test chats for position testing...\n";

// Проверяем сколько чатов уже есть
$existingChats = Chat::count();
echo "📊 Current chats: {$existingChats}\n";

$chatsToCreate = max(0, 12 - $existingChats); // Создаем чаты чтобы было всего 12
echo "🎯 Need to create: {$chatsToCreate} chats\n\n";

if ($chatsToCreate === 0) {
    echo "✅ Already have enough chats for testing!\n";
    exit(0);
}

for ($i = 1; $i <= $chatsToCreate; $i++) {
    $chat = Chat::create([
        'chat_id' => rand(1000000, 9999999), // Случайный ID
        'type' => 'group',
        'title' => "Test Chat {$i}",
        'username' => null,
        'description' => "Test chat {$i} for position testing",
        'is_active' => true,
        'last_message_at' => now()->subHours(rand(1, 24)), // От 1 до 24 часов назад
        'message_count' => rand(1, 100),
        'display_order' => 0 // ВНЕ топ-10 изначально
    ]);
    
    echo "✅ Created: {$chat->title} (ID: {$chat->id}, Telegram: {$chat->chat_id})\n";
}

echo "\n🎉 Test chats created successfully!\n";
echo "📊 Total chats now: " . Chat::count() . "\n";
echo "💡 You can now run: php artisan chats:test-positions\n"; 