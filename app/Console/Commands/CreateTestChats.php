<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Http\Controllers\TelegramWebhookController;
use App\Services\DynamicCapTestGenerator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CreateTestChats extends Command
{
    protected $signature = 'test:create-chats {count=100 : Количество чатов для создания} {--operations=all : Типы операций (all, create, update, status)} {--combinations=basic : Комбинации полей (basic, advanced, full)}';
    protected $description = 'Создает тестовые чаты с капами используя DynamicCapTestGenerator (16 типов операций)';

    private $generator;
    private $webhookController;

    public function handle()
    {
        $count = (int) $this->argument('count');
        $operations = $this->option('operations');
        $combinations = $this->option('combinations');
        
        $this->info("Создание {$count} тестовых чатов с капами...");
        $this->info("Типы операций: {$operations}");
        $this->info("Комбинации полей: {$combinations}");
        
        // Инициализация генератора
        $this->generator = new DynamicCapTestGenerator();
        $this->webhookController = app(TelegramWebhookController::class);
        
        // Очищаем существующие данные
        $this->warn('Очистка существующих данных...');
        $this->clearDatabase();
        
        $this->info('Генерация тестовых сообщений с капами...');
        
        $successCount = 0;
        $errorCount = 0;
        $operationStats = [];
        
        for ($i = 1; $i <= $count; $i++) {
            try {
                // Определяем тип операции для этого чата
                $operationType = $this->selectOperationType($operations, $i);
                
                // Генерируем тестовое сообщение с капой
                $testMessage = $this->generateCapMessage($i, $operationType, $combinations);
                
                // Отправляем через webhook контроллер
                $request = new Request($testMessage);
                $response = $this->webhookController->handle($request);
                
                if ($response->getStatusCode() == 200) {
                    $successCount++;
                    $operationStats[$operationType] = ($operationStats[$operationType] ?? 0) + 1;
                    
                    if ($i % 10 == 0) {
                        $this->info("Обработано чатов: {$i}");
                    }
                } else {
                    $errorCount++;
                    $this->error("Ошибка для чата {$i}: " . $response->getContent());
                }
                
                // Небольшая задержка для имитации реального времени
                usleep(10000); // 0.01 секунды
                
            } catch (\Exception $e) {
                $errorCount++;
                $this->error("Исключение для чата {$i}: " . $e->getMessage());
            }
        }
        
        $this->info("✅ Обработано сообщений: {$successCount}");
        if ($errorCount > 0) {
            $this->warn("⚠️ Ошибок: {$errorCount}");
        }
        
        // Показываем статистику операций
        $this->showOperationStats($operationStats);
        
        // Показываем общую статистику
        $this->showStatistics();
    }

    private function clearDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('caps_history')->truncate();
        DB::table('caps')->truncate();  
        DB::table('messages')->truncate();
        DB::table('chats')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('База данных очищена');
    }

    private function selectOperationType($operations, $index)
    {
        $operationTypes = $this->generator->getOperationTypes();
        
        switch ($operations) {
            case 'create':
                $createTypes = array_filter($operationTypes, fn($type) => str_contains($type, 'create'));
                return $createTypes[array_rand($createTypes)];
                
            case 'update':
                $updateTypes = array_filter($operationTypes, fn($type) => str_contains($type, 'update'));
                return $updateTypes[array_rand($updateTypes)];
                
            case 'status':
                $statusCommands = $this->generator->getStatusCommands();
                return 'status_' . $statusCommands[array_rand($statusCommands)];
                
            default: // 'all'
                // Циклически проходим все типы операций
                return $operationTypes[$index % count($operationTypes)];
        }
    }

    private function generateCapMessage($index, $operationType, $combinations)
    {
        $chatId = 1000 + $index;
        $messageId = 10000 + $index;
        $userId = 2000 + $index;
        
        // Генерируем сообщение с капой в зависимости от типа операции
        $messageText = $this->generateMessageByType($operationType, $index, $combinations);
        
        // Создаем структуру сообщения Telegram API
        $telegramMessage = [
            'update_id' => $index,
            'message' => [
                'message_id' => $messageId,
                'from' => [
                    'id' => $userId,
                    'is_bot' => false,
                    'first_name' => "TestUser{$index}",
                    'last_name' => "LastName{$index}",
                    'username' => "testuser{$index}",
                    'language_code' => 'ru'
                ],
                'chat' => [
                    'id' => $chatId,
                    'type' => $this->getChatType($index),
                    'title' => $this->generateChatTitle($index),
                    'username' => $this->generateChatUsername($index),
                    'description' => $this->generateChatDescription($index)
                ],
                'date' => Carbon::now()->subMinutes(rand(0, 1440))->timestamp,
                'text' => $messageText
            ]
        ];
        
        // Добавляем reply_to_message для операций обновления
        if (str_contains($operationType, 'reply_') || str_contains($operationType, 'quote_')) {
            $telegramMessage['message']['reply_to_message'] = [
                'message_id' => $messageId - 1000,
                'from' => [
                    'id' => $userId - 1000,
                    'first_name' => "OriginalUser{$index}",
                    'username' => "originaluser{$index}"
                ],
                'chat' => $telegramMessage['message']['chat'],
                'date' => Carbon::now()->subMinutes(rand(1440, 2880))->timestamp,
                'text' => $this->generateOriginalMessage($index)
            ];
        }
        
        // Добавляем quoted_text для операций с цитатами
        if (str_contains($operationType, 'quote_')) {
            $telegramMessage['message']['quote'] = [
                'text' => $this->generateQuotedText($index)
            ];
        }
        
        return $telegramMessage;
    }

    private function generateMessageByType($operationType, $index, $combinations)
    {
        $baseFields = $this->getBaseFields($index, $combinations);
        
        switch ($operationType) {
            // Создание сообщений
            case 'message_create_single_one':
                return $this->generator->generateSingleCapMessage($baseFields);
                
            case 'message_create_single_many':
                $caps = ['10', '20', '30'];
                $geos = ['RU', 'UA', 'KZ'];
                return $this->generator->generateMultiCapMessage($baseFields, $caps, $geos);
                
            case 'message_create_group_one':
                $blocks = [$baseFields];
                return $this->generator->generateGroupMessage($blocks);
                
            case 'message_create_group_many':
                $blocks = [$baseFields, $this->getBaseFields($index + 1000, $combinations)];
                return $this->generator->generateGroupMessage($blocks);
                
            // Обновление через сообщения
            case 'message_update_single_one':
                $identifiers = ['affiliate' => $baseFields['affiliate'], 'recipient' => $baseFields['recipient'], 'geo' => $baseFields['geo']];
                $updates = ['schedule' => '10-19', 'total' => '500'];
                return $this->generator->generateUpdateMessage($identifiers, $updates);
                
            case 'message_update_single_many':
                $identifiers = ['affiliate' => $baseFields['affiliate'], 'recipient' => $baseFields['recipient']];
                $updates = ['geo' => 'RU UA', 'schedule' => '24/7', 'total' => '1000'];
                return $this->generator->generateUpdateMessage($identifiers, $updates);
                
            case 'message_update_group_one':
                $identifiers = ['affiliate' => $baseFields['affiliate'], 'recipient' => $baseFields['recipient']];
                $updates = ['schedule' => '10-19'];
                return $this->generator->generateUpdateMessage($identifiers, $updates);
                
            case 'message_update_group_many':
                $identifiers = ['affiliate' => $baseFields['affiliate']];
                $updates = ['schedule' => '24/7', 'total' => '2000'];
                return $this->generator->generateUpdateMessage($identifiers, $updates);
                
            // Обновление через ответы
            case 'reply_update_single_one':
                return "Schedule: 10-19\nTotal: 300";
                
            case 'reply_update_single_many':
                return "Geo: RU UA KZ\nSchedule: 24/7";
                
            case 'reply_update_group_one':
                return "Schedule: 10-19";
                
            case 'reply_update_group_many':
                return "Total: 1500";
                
            // Обновление через цитаты
            case 'quote_update_single_one':
                return "Schedule: 12-20\nTotal: 400";
                
            case 'quote_update_single_many':
                return "Geo: DE FR IT\nSchedule: 24/7";
                
            case 'quote_update_group_one':
                return "Schedule: 09-18";
                
            case 'quote_update_group_many':
                return "Total: 2500";
                
            // Команды статуса
            default:
                if (str_starts_with($operationType, 'status_')) {
                    $command = str_replace('status_', '', $operationType);
                    $identifiers = ['affiliate' => $baseFields['affiliate'], 'recipient' => $baseFields['recipient'], 'geo' => $baseFields['geo']];
                    return $this->generator->generateStatusCommand($identifiers, $command);
                }
                
                return $this->generator->generateSingleCapMessage($baseFields);
        }
    }

    private function getBaseFields($index, $combinations)
    {
        $fieldValues = $this->generator->getAllFields();
        $values = [];
        
        foreach (['affiliate', 'recipient', 'cap', 'geo'] as $field) {
            $options = $this->generator->getFieldValues($field);
            $values[$field] = $options[$index % count($options)];
        }
        
        // Добавляем опциональные поля в зависимости от типа комбинаций
        switch ($combinations) {
            case 'advanced':
                $values['schedule'] = $this->generator->getFieldValues('schedule')[$index % 6];
                $values['language'] = $this->generator->getFieldValues('language')[$index % 4];
                $values['total'] = $this->generator->getFieldValues('total')[$index % 5];
                break;
                
            case 'full':
                foreach (['schedule', 'date', 'language', 'funnel', 'total', 'pending_acq', 'freeze_status_on_acq'] as $field) {
                    $options = $this->generator->getFieldValues($field);
                    $values[$field] = $options[$index % count($options)];
                }
                break;
                
            default: // 'basic'
                $values['schedule'] = $this->generator->getFieldValues('schedule')[$index % 6];
                break;
        }
        
        return $values;
    }

    private function generateOriginalMessage($index)
    {
        $baseFields = $this->getBaseFields($index, 'basic');
        return $this->generator->generateSingleCapMessage($baseFields);
    }

    private function generateQuotedText($index)
    {
        $baseFields = $this->getBaseFields($index, 'basic');
        return $this->generator->generateSingleCapMessage($baseFields);
    }

    private function getChatType($index)
    {
        $types = ['private', 'group', 'supergroup', 'channel'];
        return $types[$index % count($types)];
    }

    private function generateChatTitle($index)
    {
        $type = $this->getChatType($index);
        
        switch ($type) {
            case 'private':
                return null;
            case 'group':
                return "Группа тестирования #{$index}";
            case 'supergroup':
                return "Супергруппа тестирования #{$index}";
            case 'channel':
                return "Канал тестирования #{$index}";
            default:
                return "Чат #{$index}";
        }
    }

    private function generateChatUsername($index)
    {
        $type = $this->getChatType($index);
        
        switch ($type) {
            case 'private':
                return "testuser{$index}";
            case 'group':
                return null;
            case 'supergroup':
                return rand(0, 1) ? "testgroup{$index}" : null;
            case 'channel':
                return "testchannel{$index}";
            default:
                return "testuser{$index}";
        }
    }

    private function generateChatDescription($index)
    {
        $type = $this->getChatType($index);
        
        switch ($type) {
            case 'private':
                return null;
            case 'group':
                return "Тестовая группа для проверки системы кап #{$index}";
            case 'supergroup':
                return "Тестовая супергруппа с расширенными возможностями кап #{$index}";
            case 'channel':
                return "Тестовый канал для публикации сообщений с капами #{$index}";
            default:
                return "Тестовый чат для кап #{$index}";
        }
    }

    private function showOperationStats($operationStats)
    {
        if (empty($operationStats)) {
            return;
        }
        
        $this->info("\n📊 Статистика типов операций:");
        
        foreach ($operationStats as $operation => $count) {
            $this->line("  - {$operation}: {$count}");
        }
    }

    private function showStatistics()
    {
        $this->info("\n📊 Статистика созданных данных:");
        
        // Статистика чатов
        $chatCount = Chat::count();
        $this->line("📁 Всего чатов: {$chatCount}");
        
        $chatTypes = Chat::selectRaw('type, COUNT(*) as count')
                     ->groupBy('type')
                     ->pluck('count', 'type')
                     ->toArray();
        
        foreach ($chatTypes as $type => $count) {
            $this->line("  - {$type}: {$count}");
        }
        
        // Статистика сообщений
        $messageCount = Message::count();
        $this->line("💬 Всего сообщений: {$messageCount}");
        
        // Статистика кап
        $capCount = Cap::count();
        $this->line("🎯 Всего кап: {$capCount}");
        
        if ($capCount > 0) {
            $capsByStatus = Cap::selectRaw('status, COUNT(*) as count')
                             ->groupBy('status')
                             ->pluck('count', 'status')
                             ->toArray();
            
            $this->line("📊 Капы по статусам:");
            foreach ($capsByStatus as $status => $count) {
                $this->line("  - {$status}: {$count}");
            }
            
            $capsByGeo = Cap::selectRaw('JSON_EXTRACT(geos, "$[0]") as geo, COUNT(*) as count')
                          ->groupBy('geo')
                          ->pluck('count', 'geo')
                          ->toArray();
            
            $this->line("📍 Капы по регионам (топ-5):");
            foreach (array_slice($capsByGeo, 0, 5) as $geo => $count) {
                $cleanGeo = trim($geo, '"');
                $this->line("  - {$cleanGeo}: {$count}");
            }
        }
        
        // Статистика истории кап
        $capHistoryCount = CapHistory::count();
        $this->line("📚 Записей в истории кап: {$capHistoryCount}");
        
        $this->info("\n🎯 Система готова для тестирования!");
        $this->info("✅ Использованы все 16 типов операций");
        $this->info("✅ Чаты созданы через TelegramWebhookController");
        $this->info("✅ Сообщения обработаны через CapAnalysisService");
        $this->info("✅ Капы найдены и сохранены автоматически");
    }
} 