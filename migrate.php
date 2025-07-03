<?php

// Простой скрипт для выполнения миграции
// Запустите через веб-браузер: http://yourdomain.com/migrate.php

require_once 'vendor/autoload.php';

// Загружаем Laravel
$app = require_once 'bootstrap/app.php';

// Создаем консольное ядро
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    // Выполняем миграцию
    $exitCode = $kernel->call('migrate', [
        '--path' => 'database/migrations/2024_12_19_000003_add_is_outgoing_to_messages_table.php',
        '--force' => true
    ]);
    
    if ($exitCode === 0) {
        echo "✅ Миграция выполнена успешно!\n";
        echo "Поле is_outgoing добавлено в таблицу messages.\n";
    } else {
        echo "❌ Ошибка выполнения миграции.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Ошибка: " . $e->getMessage() . "\n";
}

// Удаляем этот файл после использования
if (file_exists(__FILE__)) {
    unlink(__FILE__);
    echo "🧹 Файл миграции удален.\n";
} 