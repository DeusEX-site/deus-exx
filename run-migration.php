<?php

// Скрипт для запуска миграции
require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

// Запускаем миграцию
$exitCode = $kernel->call('migrate', [
    '--force' => true, // Для продакшена, чтобы не спрашивать подтверждение
]);

if ($exitCode === 0) {
    echo "Миграция успешно выполнена!\n";
} else {
    echo "Ошибка выполнения миграции!\n";
} 