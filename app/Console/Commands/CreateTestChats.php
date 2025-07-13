<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Chat;
use App\Models\Message;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Http\Controllers\TelegramWebhookController;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class CreateTestChats extends Command
{
    protected $signature = 'test:create-chats {count=100 : Количество чатов для создания} {--operations=all : Типы операций (all, create, update, status)} {--combinations=basic : Комбинации полей (basic, advanced, full)}';
    protected $description = 'Создает максимальное количество тестовых вариантов - 30К+ сообщений с разными вариантами написания полей';

    private $webhookController;

    // МАКСИМАЛЬНЫЕ ВАРИАНТЫ ПОЛЕЙ ДЛЯ ТЕСТИРОВАНИЯ
    private $fieldVariants = [
        'affiliate' => [
            'keys' => ['affiliate:', 'Affiliate:', 'AFFILIATE:', 'aFFiLiAtE:', 'affiliate :', ' affiliate:', 'affiliate: '],
            'values' => ['TestAffiliate1', 'TestAffiliate2', 'G06', 'mbt internal', 'XYZ Company', 'Super-Affiliate', 'Affiliate_Test', 'TEST01', 'Партнер1', 'افлیت۱']
        ],
        'recipient' => [
            'keys' => ['recipient:', 'Recipient:', 'RECIPIENT:', 'rEcIpIeNt:', 'recipient :', ' recipient:', 'recipient: '],
            'values' => ['TestBroker1', 'TestBroker2', 'TradingM', 'Global KZ', 'BinaryBroker', 'Crypto-Trader', 'Broker_Pro', 'Брокер1', 'بروکر۱', 'test broker']
        ],
        'cap' => [
            'keys' => ['cap:', 'Cap:', 'CAP:', 'cAp:', 'cap :', ' cap:', 'cap: '],
            'values' => ['10', '20 30', '100 200 300', '50', '25 50 75 100', '5', '999', '1 2 3 4 5', '0', '10000']
        ],
        'geo' => [
            'keys' => ['geo:', 'Geo:', 'GEO:', 'gEo:', 'geo :', ' geo:', 'geo: '],
            'values' => ['RU', 'RU UA', 'DE AT CH', 'US UK CA', 'FR IT ES', 'KZ', 'AU NZ', 'IE', 'PL CZ SK', 'BR AR MX']
        ],
        'schedule' => [
            'keys' => ['schedule:', 'Schedule:', 'SCHEDULE:', 'sChEdUlE:', 'schedule :', ' schedule:', 'schedule: '],
            'values' => ['24/7', '10-19', '09:00/18:00 GMT+01:00', '8.30 - 14.30 +3', '18:00/01:00 GMT+03:00', '24h', '10:00-19:00', '9-17', '12:00/20:00 GMT+02:00', '00-24', 'круглосуточно', '10-19 +2', 'always', 'non-stop']
        ],
        'date' => [
            'keys' => ['date:', 'Date:', 'DATE:', 'dAtE:', 'date :', ' date:', 'date: '],
            'values' => ['24.02', '01.01 02.02', '25.12.2024', '2024-01-01', '15/03/2024', '01.01-31.12', '24.02 25.02', 'today', 'завтра', '2024.01.01']
        ],
        'language' => [
            'keys' => ['language:', 'Language:', 'LANGUAGE:', 'lAnGuAgE:', 'language :', ' language:', 'language: '],
            'values' => ['en', 'ru', 'de fr', 'en ru de', 'es it', 'pl', 'zh', 'ar', 'tr', 'pt', 'ja', 'ko', 'uk', 'be', 'kz']
        ],
        'funnel' => [
            'keys' => ['funnel:', 'Funnel:', 'FUNNEL:', 'fUnNeL:', 'funnel :', ' funnel:', 'funnel: '],
            'values' => ['crypto', 'forex', 'binary', 'stocks', 'options', 'trading', 'investment', 'crypto,forex', 'deusexx', 'premium', 'vip', 'standard', 'test']
        ],
        'total' => [
            'keys' => ['total:', 'Total:', 'TOTAL:', 'tOtAl:', 'total :', ' total:', 'total: '],
            'values' => ['100', '500 1000', '200 400 600', '-', '999', '50 100 150', '1000', 'unlimited', '∞', '0', '1', '10000']
        ],
        'pending_acq' => [
            'keys' => ['pending acq:', 'Pending ACQ:', 'PENDING ACQ:', 'Pending acq:', 'pending Acq:', 'pending acq :', ' pending acq:', 'pending acq: '],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет', 'yes,no,yes', '1,0,1']
        ],
        'freeze_status_on_acq' => [
            'keys' => ['freeze status on acq:', 'Freeze status on ACQ:', 'FREEZE STATUS ON ACQ:', 'Freeze Status On Acq:', 'freeze Status on acq:', 'freeze status on acq :', ' freeze status on acq:', 'freeze status on acq: '],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет', 'freeze', 'unfreeze', 'yes,no,yes']
        ]
    ];

    // ПУСТЫЕ ЗНАЧЕНИЯ ДЛЯ ТЕСТИРОВАНИЯ СБРОСОВ
    private $emptyValues = ['', '-', '  ', 'null', 'none', 'empty', '---'];

    // СТАТУС КОМАНДЫ
    private $statusCommands = ['run', 'stop', 'delete', 'restore', 'RUN', 'STOP', 'DELETE', 'RESTORE', 'Run', 'Stop', 'Delete', 'Restore'];

    public function handle()
    {
        $chatCount = (int) $this->argument('count');
        $operations = $this->option('operations');
        $combinations = $this->option('combinations');
        
        $this->info("🚀 МАКСИМАЛЬНОЕ ТЕСТИРОВАНИЕ СИСТЕМЫ");
        $this->info("Создание {$chatCount} чатов с ОГРОМНЫМ количеством вариантов сообщений");
        $this->info("Цель: 30,000+ уникальных сообщений по 16 направлениям");
        $this->info("Типы операций: {$operations}");
        $this->info("Комбинации полей: {$combinations}");
        
        // Инициализация контроллера
        $this->webhookController = app(TelegramWebhookController::class);
        
        // Очищаем существующие данные
        $this->warn('Очистка существующих данных...');
        $this->clearDatabase();
        
        // МАКСИМАЛЬНАЯ ГЕНЕРАЦИЯ ВАРИАНТОВ
        $this->info('🔥 ГЕНЕРАЦИЯ МАКСИМАЛЬНОГО КОЛИЧЕСТВА ВАРИАНТОВ...');
        $this->generateMaximumVariants($chatCount, $operations, $combinations);
        
        // Показываем статистику
        $this->showStatistics();
    }
    
    private function generateMaximumVariants($chatCount, $operations, $combinations)
    {
        $successCount = 0;
        $errorCount = 0;
        $totalMessages = 0;
        $messageIndex = 1;
        
        $operationTypes = $this->getOperationTypesToTest($operations);
        
        for ($chatIndex = 1; $chatIndex <= $chatCount; $chatIndex++) {
            foreach ($operationTypes as $operationType) {
                // Для каждого типа операции генерируем МНОЖЕСТВО вариантов
                $variants = $this->generateAllVariantsForOperation($operationType, $combinations, $messageIndex);
                
                foreach ($variants as $variant) {
                    try {
                        $testMessage = $this->generateCapMessage($messageIndex, $operationType, $variant, $chatIndex);
                        
                        // Отправляем через webhook контроллер
                        $request = new Request($testMessage);
                        $response = $this->webhookController->handle($request);
                        
                        if ($response->getStatusCode() == 200) {
                            $successCount++;
                        } else {
                            $errorCount++;
                            if ($errorCount <= 10) { // Показываем только первые 10 ошибок
                                $this->error("Ошибка для сообщения {$messageIndex}: " . $response->getContent());
                            }
                        }
                        
                        $totalMessages++;
                        $messageIndex++;
                        
                        // Показываем прогресс каждые 100 сообщений
                        if ($totalMessages % 100 == 0) {
                            $this->info("Обработано сообщений: {$totalMessages}, Успешно: {$successCount}, Ошибок: {$errorCount}");
                        }
                        
                    } catch (\Exception $e) {
                        $errorCount++;
                        if ($errorCount <= 10) {
                            $this->error("Исключение для сообщения {$messageIndex}: " . $e->getMessage());
                        }
                        $messageIndex++;
                    }
                }
            }
        }
        
        $this->info("🎉 ГЕНЕРАЦИЯ ЗАВЕРШЕНА!");
        $this->info("Всего сообщений: {$totalMessages}");
        $this->info("Успешно обработано: {$successCount}");
        $this->info("Ошибок: {$errorCount}");
    }
    
    private function generateAllVariantsForOperation($operationType, $combinations, $baseIndex)
    {
        $variants = [];
        
        switch (true) {
            case str_contains($operationType, 'create'):
                $variants = $this->generateCreateVariants($combinations, $baseIndex);
                break;
                
            case str_contains($operationType, 'update'):
                $variants = $this->generateUpdateVariants($combinations, $baseIndex);
                break;
                
            case str_contains($operationType, 'reply'):
                $variants = $this->generateReplyVariants($combinations, $baseIndex);
                break;
                
            case str_contains($operationType, 'quote'):
                $variants = $this->generateQuoteVariants($combinations, $baseIndex);
                break;
                
            case str_contains($operationType, 'status'):
                $variants = $this->generateStatusVariants($baseIndex);
                break;
                
            default:
                $variants = $this->generateCreateVariants($combinations, $baseIndex);
        }
        
        return array_slice($variants, 0, 50); // Ограничиваем до 50 вариантов на операцию
    }
    
    private function generateCreateVariants($combinations, $baseIndex)
    {
        $variants = [];
        
        // Обязательные поля с максимальными вариантами
        $affiliateVariants = $this->getFieldVariants('affiliate', $baseIndex);
        $recipientVariants = $this->getFieldVariants('recipient', $baseIndex);
        $capVariants = $this->getFieldVariants('cap', $baseIndex);
        $geoVariants = $this->getFieldVariants('geo', $baseIndex);
        
        // Опциональные поля
        $scheduleVariants = $this->getFieldVariants('schedule', $baseIndex);
        $languageVariants = $this->getFieldVariants('language', $baseIndex);
        $totalVariants = $this->getFieldVariants('total', $baseIndex);
        $dateVariants = $this->getFieldVariants('date', $baseIndex);
        $funnelVariants = $this->getFieldVariants('funnel', $baseIndex);
        $pendingVariants = $this->getFieldVariants('pending_acq', $baseIndex);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $baseIndex);
        
        $variantIndex = 0;
        
        // Генерируем комбинации
        for ($i = 0; $i < 5; $i++) { // 5 основных вариантов
            $variant = [];
            
            // Обязательные поля
            $variant['affiliate'] = $affiliateVariants[$i % count($affiliateVariants)];
            $variant['recipient'] = $recipientVariants[$i % count($recipientVariants)];
            $variant['cap'] = $capVariants[$i % count($capVariants)];
            $variant['geo'] = $geoVariants[$i % count($geoVariants)];
            
            // Опциональные поля в зависимости от комбинаций
            if ($combinations === 'full' || $combinations === 'advanced') {
                $variant['schedule'] = $scheduleVariants[$i % count($scheduleVariants)];
                $variant['language'] = $languageVariants[$i % count($languageVariants)];
                $variant['total'] = $totalVariants[$i % count($totalVariants)];
                
                if ($combinations === 'full') {
                    $variant['date'] = $dateVariants[$i % count($dateVariants)];
                    $variant['funnel'] = $funnelVariants[$i % count($funnelVariants)];
                    $variant['pending_acq'] = $pendingVariants[$i % count($pendingVariants)];
                    $variant['freeze_status_on_acq'] = $freezeVariants[$i % count($freezeVariants)];
                }
            }
            
            // Добавляем вариант с перемешанным порядком полей
            $variant['field_order'] = $this->getRandomFieldOrder($variantIndex);
            
            // Добавляем варианты с пустыми полями для тестирования сбросов
            if ($i % 3 == 0) {
                $variant['empty_fields'] = $this->getEmptyFieldsVariant($variantIndex);
            }
            
            $variants[] = $variant;
            $variantIndex++;
        }
        
        // Добавляем специальные варианты с экстремальными случаями
        $variants = array_merge($variants, $this->generateExtremeVariants($combinations));
        
        return $variants;
    }
    
    private function generateUpdateVariants($combinations, $baseIndex)
    {
        $variants = [];
        
        // Варианты обновлений
        for ($i = 0; $i < 10; $i++) {
            $variant = [
                'update_type' => 'field_update',
                'fields_to_update' => $this->getRandomUpdateFields($i, $combinations)
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateReplyVariants($combinations, $baseIndex)
    {
        $variants = [];
        
        // Варианты ответов
        for ($i = 0; $i < 8; $i++) {
            $variant = [
                'reply_type' => 'field_reply',
                'reply_fields' => $this->getRandomReplyFields($i, $combinations)
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateQuoteVariants($combinations, $baseIndex)
    {
        $variants = [];
        
        // Варианты цитат
        for ($i = 0; $i < 6; $i++) {
            $variant = [
                'quote_type' => 'field_quote',
                'quote_fields' => $this->getRandomQuoteFields($i, $combinations)
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateStatusVariants($baseIndex)
    {
        $variants = [];
        
        // Все варианты статус команд
        foreach ($this->statusCommands as $command) {
            $variant = [
                'status_command' => $command,
                'with_fields' => $baseIndex % 2 == 0 // Иногда с полями, иногда без
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateExtremeVariants($combinations)
    {
        $variants = [];
        
        // Вариант 1: Все поля пустые
        $variants[] = [
            'extreme_type' => 'all_empty',
            'affiliate' => ['affiliate:', ''],
            'recipient' => ['recipient:', ''],
            'cap' => ['cap:', '-'],
            'geo' => ['geo:', ''],
        ];
        
        // Вариант 2: Максимально длинные значения
        $variants[] = [
            'extreme_type' => 'max_length',
            'affiliate' => ['affiliate:', 'Very-Long-Affiliate-Name-With-Special-Characters-And-Numbers-123'],
            'recipient' => ['recipient:', 'Extremely-Long-Recipient-Name-For-Testing-Maximum-Field-Length'],
            'cap' => ['cap:', '1 2 3 4 5 6 7 8 9 10 11 12 13 14 15 16 17 18 19 20'],
            'geo' => ['geo:', 'RU UA KZ DE FR IT ES US UK CA AU NZ IE PL CZ'],
        ];
        
        // Вариант 3: Специальные символы
        $variants[] = [
            'extreme_type' => 'special_chars',
            'affiliate' => ['affiliate:', 'Test@Affiliate#123'],
            'recipient' => ['recipient:', 'Broker$Test&Co'],
            'cap' => ['cap:', '100'],
            'geo' => ['geo:', 'RU'],
        ];
        
        return $variants;
    }
    
    private function getFieldVariants($fieldName, $baseIndex)
    {
        if (!isset($this->fieldVariants[$fieldName])) {
            return [[$fieldName . ':', 'default_value']];
        }
        
        $field = $this->fieldVariants[$fieldName];
        $variants = [];
        
        foreach ($field['keys'] as $keyIndex => $key) {
            foreach ($field['values'] as $valueIndex => $value) {
                $variants[] = [$key, $value];
                
                // Ограничиваем количество вариантов
                if (count($variants) >= 10) {
                    break 2;
                }
            }
        }
        
        return $variants;
    }
    
    private function getRandomFieldOrder($index)
    {
        $orders = [
            ['affiliate', 'recipient', 'cap', 'geo', 'schedule', 'total', 'language'],
            ['geo', 'cap', 'affiliate', 'recipient', 'language', 'schedule', 'total'],
            ['recipient', 'affiliate', 'geo', 'cap', 'total', 'language', 'schedule'],
            ['cap', 'geo', 'recipient', 'affiliate', 'schedule', 'language', 'total'],
        ];
        
        return $orders[$index % count($orders)];
    }
    
    private function getEmptyFieldsVariant($index)
    {
        $emptyVariants = [
            ['schedule', 'total'],
            ['language', 'funnel'],
            ['date', 'pending_acq'],
            ['freeze_status_on_acq'],
            ['schedule', 'language', 'total'],
        ];
        
        return $emptyVariants[$index % count($emptyVariants)];
    }
    
    private function getRandomUpdateFields($index, $combinations)
    {
        $updateFields = [
            ['schedule' => '24/7', 'total' => '500'],
            ['language' => 'en', 'funnel' => 'crypto'],
            ['pending_acq' => 'yes', 'freeze_status_on_acq' => 'no'],
            ['schedule' => '10-19', 'language' => 'ru'],
            ['total' => '-', 'date' => ''],
        ];
        
        return $updateFields[$index % count($updateFields)];
    }
    
    private function getRandomReplyFields($index, $combinations)
    {
        $replyFields = [
            ['schedule' => '10-19', 'total' => '300'],
            ['geo' => 'RU UA KZ', 'schedule' => '24/7'],
            ['language' => 'en', 'funnel' => 'forex'],
            ['pending_acq' => 'yes', 'total' => '1000'],
        ];
        
        return $replyFields[$index % count($replyFields)];
    }
    
    private function getRandomQuoteFields($index, $combinations)
    {
        $quoteFields = [
            ['schedule' => '12-20', 'total' => '400'],
            ['geo' => 'DE FR IT', 'schedule' => '24/7'],
            ['language' => 'de', 'funnel' => 'binary'],
        ];
        
        return $quoteFields[$index % count($quoteFields)];
    }

    private function getOperationTypesToTest($operations)
    {
        $operationTypes = [
            'message_create_single_one',
            'message_create_single_many', 
            'message_create_group_one',
            'message_create_group_many',
            'message_update_single_one',
            'message_update_single_many',
            'message_update_group_one', 
            'message_update_group_many',
            'reply_update_single_one',
            'reply_update_single_many',
            'reply_update_group_one',
            'reply_update_group_many',
            'quote_update_single_one',
            'quote_update_single_many',
            'quote_update_group_one',
            'quote_update_group_many',
            'status_run',
            'status_stop',
            'status_delete',
            'status_restore'
        ];

        if ($operations !== 'all') {
            $operationTypes = array_filter($operationTypes, function($type) use ($operations) {
                return str_contains($type, $operations);
            });
        }

        return $operationTypes;
    }

    private function clearDatabase()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        CapHistory::truncate();
        Cap::truncate();
        Message::truncate();
        Chat::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        
        $this->info('База данных очищена');
    }

    private function generateCapMessage($index, $operationType, $variant, $chatIndex = null)
    {
        $chatId = 1000 + ($chatIndex ?? $index);
        $messageId = 10000 + $index;
        $userId = 2000 + $index;
        
        // Генерируем сообщение используя максимальные варианты
        $messageText = $this->generateMessageByVariant($operationType, $variant);
        
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
                    'type' => 'group'
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

    private function generateMessageByVariant($operationType, $variant)
    {
        switch (true) {
            case str_contains($operationType, 'create'):
                return $this->generateCreateMessage($variant);
                
            case str_contains($operationType, 'update'):
                return $this->generateUpdateMessage($variant);
                
            case str_contains($operationType, 'reply'):
                return $this->generateReplyMessage($variant);
                
            case str_contains($operationType, 'quote'):
                return $this->generateQuoteMessage($variant);
                
            case str_contains($operationType, 'status'):
                return $this->generateStatusMessage($variant);
                
            default:
                return $this->generateCreateMessage($variant);
        }
    }
    
    private function generateCreateMessage($variant)
    {
        $message = '';
        
        // Определяем порядок полей
        $fieldOrder = $variant['field_order'] ?? ['affiliate', 'recipient', 'cap', 'geo'];
        
        foreach ($fieldOrder as $field) {
            if (isset($variant[$field])) {
                $fieldData = $variant[$field];
                
                // Проверяем, нужно ли сделать поле пустым
                if (isset($variant['empty_fields']) && in_array($field, $variant['empty_fields'])) {
                    $emptyValue = $this->emptyValues[array_rand($this->emptyValues)];
                    $message .= $fieldData[0] . ' ' . $emptyValue . "\n";
                } else {
                    $message .= $fieldData[0] . ' ' . $fieldData[1] . "\n";
                }
            }
        }
        
        return rtrim($message);
    }
    
    private function generateUpdateMessage($variant)
    {
        $message = '';
        
        if (isset($variant['fields_to_update'])) {
            foreach ($variant['fields_to_update'] as $field => $value) {
                $keys = $this->fieldVariants[$field]['keys'] ?? [$field . ':'];
                $key = $keys[array_rand($keys)];
                $message .= $key . ' ' . $value . "\n";
            }
        }
        
        return rtrim($message);
    }
    
    private function generateReplyMessage($variant)
    {
        $message = '';
        
        if (isset($variant['reply_fields'])) {
            foreach ($variant['reply_fields'] as $field => $value) {
                $keys = $this->fieldVariants[$field]['keys'] ?? [$field . ':'];
                $key = $keys[array_rand($keys)];
                $message .= $key . ' ' . $value . "\n";
            }
        }
        
        return rtrim($message);
    }
    
    private function generateQuoteMessage($variant)
    {
        $message = '';
        
        if (isset($variant['quote_fields'])) {
            foreach ($variant['quote_fields'] as $field => $value) {
                $keys = $this->fieldVariants[$field]['keys'] ?? [$field . ':'];
                $key = $keys[array_rand($keys)];
                $message .= $key . ' ' . $value . "\n";
            }
        }
        
        return rtrim($message);
    }
    
    private function generateStatusMessage($variant)
    {
        $message = '';
        
        if (isset($variant['with_fields']) && $variant['with_fields']) {
            // Добавляем поля для идентификации
            $message .= "affiliate: TestAffiliate1\n";
            $message .= "recipient: TestBroker1\n";
            $message .= "cap: 50\n";
            $message .= "geo: RU\n";
        }
        
        $message .= $variant['status_command'];
        
        return rtrim($message);
    }

    private function generateOriginalMessage($index)
    {
        return "affiliate: TestAffiliate{$index}\nrecipient: TestBroker{$index}\ncap: 50\ngeo: RU\nschedule: 24/7";
    }

    private function generateQuotedText($index)
    {
        return "affiliate: TestAffiliate{$index}\nrecipient: TestBroker{$index}\ncap: 50\ngeo: RU\nschedule: 24/7";
    }

    private function showStatistics()
    {
        $this->info('📊 ФИНАЛЬНАЯ СТАТИСТИКА:');
        
        $chatCount = Chat::count();
        $messageCount = Message::count();
        $capCount = Cap::count();
        $capHistoryCount = CapHistory::count();
        
        $this->info("Чатов создано: {$chatCount}");
        $this->info("Сообщений обработано: {$messageCount}");
        $this->info("Кап найдено: {$capCount}");
        $this->info("Записей в истории: {$capHistoryCount}");
        
        if ($capCount > 0) {
            $capsByStatus = Cap::selectRaw('status, COUNT(*) as count')
                              ->groupBy('status')
                              ->get();
            
            $this->info('Статистика по статусам кап:');
            foreach ($capsByStatus as $stat) {
                $this->info("  {$stat->status}: {$stat->count}");
            }
            
            $capsByGeo = Cap::selectRaw('JSON_EXTRACT(geos, "$[0]") as geo, COUNT(*) as count')
                           ->groupBy('geo')
                           ->orderBy('count', 'desc')
                           ->limit(10)
                           ->get();
            
            $this->info('Топ-10 гео по количеству кап:');
            foreach ($capsByGeo as $stat) {
                $geo = trim($stat->geo, '"');
                $this->info("  {$geo}: {$stat->count}");
            }
        }
        
        $recognitionRate = $messageCount > 0 ? round(($capCount / $messageCount) * 100, 2) : 0;
        $this->info("🎯 Процент распознавания кап: {$recognitionRate}%");
        
        if ($recognitionRate < 80) {
            $this->warn("⚠️  Низкий процент распознавания! Возможно, есть проблемы с парсингом.");
        } else {
            $this->info("✅ Отличный процент распознавания!");
        }
    }
} 