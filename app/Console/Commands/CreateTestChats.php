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
    
    // Отслеживание использованных комбинаций для избежания дублирования
    private $usedCombinations = [];

    // МАКСИМАЛЬНЫЕ ВАРИАНТЫ ПОЛЕЙ ДЛЯ ТЕСТИРОВАНИЯ
    private $fieldVariants = [
        'affiliate' => [
            'keys' => ['affiliate:', 'Affiliate:', 'AFFILIATE:', 'aFFiLiAtE:', 'affiliate :', ' affiliate:', 'affiliate: '],
            'values' => ['TestAffiliate1', 'TestAffiliate2', 'G06', 'mbt internal', 'Super-Affiliate', 'Affiliate_Test', 'TEST01', 'Партнер1']
        ],
        'recipient' => [
            'keys' => ['recipient:', 'Recipient:', 'RECIPIENT:', 'rEcIpIeNt:', 'recipient :', ' recipient:', 'recipient: '],
            'values' => ['TestBroker1', 'TestBroker2', 'TradingM', 'Global KZ', 'Crypto-Trader', 'Broker_Pro', 'Брокер1', 'بروکر۱', 'test broker']
        ],
        'cap' => [
            'keys' => ['cap:', 'Cap:', 'CAP:', 'cAp:', 'cap :', ' cap:', 'cap: '],
            'values' => ['10', '20 30', '100 200 300', '50', '5 10', '999 888', '10000']
        ],
        'geo' => [
            'keys' => ['geo:', 'Geo:', 'GEO:', 'gEo:', 'geo :', ' geo:', 'geo: '],
            'values' => ['RU', 'RU UA', 'DE AT CH', 'KZ', 'AU NZ', 'US UK', 'IE']
        ],
        'schedule' => [
            'keys' => ['schedule:', 'Schedule:', 'SCHEDULE:', 'sChEdUlE:', 'schedule :', ' schedule:', 'schedule: '],
            'values' => ['24/7', '10-19', '09:00/18:00 GMT+01:00', '8.30 - 14.30 +3', '18:00/01:00 GMT+03:00', '10:00-19:00', '9-17', '12:00/20:00 GMT+02:00', '10-19 +2']
        ],
        'date' => [
            'keys' => ['date:', 'Date:', 'DATE:', 'dAtE:', 'date :', ' date:', 'date: '],
            'values' => ['24.02', '01.01 02.02', '25.12.2024', '2024-01-01', '15/03/2024', '01.01-31.12', '24.02 25.02', '2024.01.01']
        ],
        'language' => [
            'keys' => ['language:', 'Language:', 'LANGUAGE:', 'lAnGuAgE:', 'language :', ' language:', 'language: '],
            'values' => ['en', 'ru', 'de fr', 'en ru de', 'es it', 'pl', 'zh', 'ar', 'tr', 'pt', 'ja', 'ko', 'uk', 'be', 'kz']
        ],
        'funnel' => [
            'keys' => ['funnel:', 'Funnel:', 'FUNNEL:', 'fUnNeL:', 'funnel :', ' funnel:', 'funnel: '],
            'values' => ['crypto', 'forex,binary', 'stocks,options,trading', 'investment', 'crypto,forex', 'premium,vip', 'standard']
        ],
        'test' => [
            'keys' => ['test:', 'Test:', 'TEST:', 'tEsT:', 'test :', ' test:', 'test: '],
            'values' => ['yes', 'no', 'true', 'false', 'active', 'inactive', 'on', 'off', 'enabled', 'disabled', 'debug', 'live', 'staging', 'production']
        ],
        'total' => [
            'keys' => ['total:', 'Total:', 'TOTAL:', 'tOtAl:', 'total :', ' total:', 'total: '],
            'values' => ['100', '500 1000', '200 400 600', '999', '50 100 150', '1000', '1', '10000']
        ],
        'pending_acq' => [
            'keys' => ['pending acq:', 'Pending ACQ:', 'PENDING ACQ:', 'Pending acq:', 'pending Acq:', 'pending acq :', ' pending acq:', 'pending acq: '],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет']
        ],
        'freeze_status_on_acq' => [
            'keys' => ['freeze status on acq:', 'Freeze status on ACQ:', 'FREEZE STATUS ON ACQ:', 'Freeze Status On Acq:', 'freeze Status on acq:', 'freeze status on acq :', ' freeze status on acq:', 'freeze status on acq: '],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет', 'freeze', 'unfreeze']
        ]
    ];

    // ПУСТЫЕ ЗНАЧЕНИЯ ДЛЯ ТЕСТИРОВАНИЯ СБРОСОВ
    // Удален массив emptyValues - теперь пустые поля генерируются как "field:" без значения

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
        $correctResults = 0;
        $incorrectResults = 0;
        
        $operationTypes = $this->getOperationTypesToTest($operations);
        
        // ПОСЛЕДОВАТЕЛЬНАЯ ОБРАБОТКА ПО ТИПАМ ОПЕРАЦИЙ
        foreach ($operationTypes as $index => $operationType) {
            $this->info("🔄 НАЧИНАЕМ ОБРАБОТКУ: {$operationType}");
            $this->info("═══════════════════════════════════════════════════════════════");
            
            // Для каждого типа операции проходим ПО ВСЕМ ЧАТАМ
            for ($chatIndex = 1; $chatIndex <= $chatCount; $chatIndex++) {
                // Генерируем МНОЖЕСТВО вариантов для данного типа операции
                $variants = $this->generateAllVariantsForOperation($operationType, $combinations, $messageIndex);
                
                foreach ($variants as $variant) {
                    try {
                        $testMessage = $this->generateCapMessage($messageIndex, $operationType, $variant, $chatIndex);
                        $messageText = $testMessage['message']['text'];
                        
                        // 1. АНАЛИЗИРУЕМ ОЖИДАЕМЫЕ РЕЗУЛЬТАТЫ
                        $expectedResults = $this->analyzeExpectedResults($messageText, $operationType, $variant);
                        
                        // Получаем данные ДО отправки для корректного сравнения
                        $beforeCounts = $this->getDatabaseCounts();
                        
                        // 2. ОТПРАВЛЯЕМ СООБЩЕНИЕ
                        $request = new Request($testMessage);
                        $response = $this->webhookController->handle($request);
                        
                        if ($response->getStatusCode() == 200) {
                            $successCount++;
                            
                            // 3. ПРОВЕРЯЕМ ФАКТИЧЕСКИЕ РЕЗУЛЬТАТЫ
                            $actualResults = $this->checkActualResults($messageText, $operationType, $beforeCounts, $testMessage);
                            
                            // 4. СРАВНИВАЕМ И ВЫВОДИМ ОТЧЕТ
                            $isCorrect = $this->compareAndReportResults($messageIndex, $expectedResults, $actualResults, $messageText, $operationType);
                            
                            if ($isCorrect) {
                                $correctResults++;
                            } else {
                                $incorrectResults++;
                            }
                            
                        } else {
                            $errorCount++;
                            if ($errorCount <= 10) { // Показываем только первые 10 ошибок
                                $this->error("Ошибка для сообщения {$messageIndex}: " . $response->getContent());
                            }
                        }
                        
                        $totalMessages++;
                        $messageIndex++;
                        
                        // Показываем прогресс каждые 50 сообщений
                        if ($totalMessages % 50 == 0) {
                            $this->info("Обработано: {$totalMessages}, Успешно: {$successCount}, Ошибок: {$errorCount}, Корректно: {$correctResults}, Некорректно: {$incorrectResults}");
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
            
            $this->info("✅ ЗАВЕРШЕНО: {$operationType}");
            $this->info("═══════════════════════════════════════════════════════════════");
            
            // ПАУЗА МЕЖДУ ЭТАПАМИ (кроме последнего)
            if ($index < count($operationTypes) - 1) {
                $this->info("");
                $this->info("⏸️  ПАУЗА МЕЖДУ ЭТАПАМИ");
                $this->info("Нажмите ENTER для продолжения или Ctrl+C для выхода...");
                $this->info("");
                fgets(STDIN);
                $this->info("");
            }
        }
        
        $this->info("🎉 ГЕНЕРАЦИЯ ЗАВЕРШЕНА!");
        $this->info("Всего сообщений: {$totalMessages}");
        $this->info("Успешно обработано: {$successCount}");
        $this->info("Ошибок: {$errorCount}");
        $this->info("Корректных результатов: {$correctResults}");
        $this->info("Некорректных результатов: {$incorrectResults}");
        
        if ($correctResults > 0) {
            $accuracy = round(($correctResults / ($correctResults + $incorrectResults)) * 100, 2);
            $this->info("Точность системы: {$accuracy}%");
        }
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
        $testVariants = $this->getFieldVariants('test', $baseIndex);
        $pendingVariants = $this->getFieldVariants('pending_acq', $baseIndex);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $baseIndex);
        
        $variantIndex = 0;
        $maxAttempts = 100; // Предотвращаем бесконечный цикл
        
        // Генерируем уникальные комбинации
        for ($i = 0; $i < 5 && $variantIndex < $maxAttempts; $i++) {
            $variant = [];
            
            // Обязательные поля
            $affiliateIndex = ($baseIndex + $i) % count($affiliateVariants);
            $recipientIndex = ($baseIndex + $i) % count($recipientVariants);
            $capIndex = ($baseIndex + $i) % count($capVariants);
            $geoIndex = ($baseIndex + $i) % count($geoVariants);
            
            $variant['affiliate'] = $affiliateVariants[$affiliateIndex];
            $variant['recipient'] = $recipientVariants[$recipientIndex];
            $variant['cap'] = $capVariants[$capIndex];
            $variant['geo'] = $geoVariants[$geoIndex];
            
            // Проверяем совпадение количества элементов в cap и geo
            if (!$this->validateCapGeoCount($variant['cap'][1], $variant['geo'][1])) {
                continue; // Пропускаем если количества не совпадают
            }
            
            // Проверяем уникальность комбинации
            if (!$this->isUniqueCombination($variant['affiliate'][1], $variant['recipient'][1], $variant['geo'][1])) {
                // Пытаемся найти уникальную комбинацию
                $found = false;
                for ($j = 0; $j < 20 && !$found; $j++) {
                    $newAffiliateIndex = ($baseIndex + $i + $j) % count($affiliateVariants);
                    $newRecipientIndex = ($baseIndex + $i + $j * 2) % count($recipientVariants);
                    $newGeoIndex = ($baseIndex + $i + $j * 3) % count($geoVariants);
                    
                    $testAffiliate = $affiliateVariants[$newAffiliateIndex][1];
                    $testRecipient = $recipientVariants[$newRecipientIndex][1];
                    $testGeo = $geoVariants[$newGeoIndex][1];
                    
                    if ($this->isUniqueCombination($testAffiliate, $testRecipient, $testGeo)) {
                        $variant['affiliate'] = $affiliateVariants[$newAffiliateIndex];
                        $variant['recipient'] = $recipientVariants[$newRecipientIndex];
                        $variant['geo'] = $geoVariants[$newGeoIndex];
                        $found = true;
                    }
                }
                
                if (!$found) {
                    continue; // Пропускаем если не найдена уникальная комбинация
                }
            }
            
            // Регистрируем использование комбинации
            $this->markCombinationAsUsed($variant['affiliate'][1], $variant['recipient'][1], $variant['geo'][1]);
            
            // Опциональные поля в зависимости от комбинаций
            if ($combinations === 'full' || $combinations === 'advanced') {
                $variant['schedule'] = $scheduleVariants[$i % count($scheduleVariants)];
                $variant['language'] = $languageVariants[$i % count($languageVariants)];
                $variant['total'] = $totalVariants[$i % count($totalVariants)];
                
                if ($combinations === 'full') {
                    $variant['date'] = $dateVariants[$i % count($dateVariants)];
                    $variant['funnel'] = $funnelVariants[$i % count($funnelVariants)];
                    $variant['test'] = $testVariants[$i % count($testVariants)];
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
        $extremeVariants = $this->generateExtremeVariants($combinations);
        
        // Проверяем уникальность экстремальных вариантов
        foreach ($extremeVariants as $extremeVariant) {
            if ($this->isUniqueCombination($extremeVariant['affiliate'][1], $extremeVariant['recipient'][1], $extremeVariant['geo'][1])) {
                $this->markCombinationAsUsed($extremeVariant['affiliate'][1], $extremeVariant['recipient'][1], $extremeVariant['geo'][1]);
                $variants[] = $extremeVariant;
            }
        }
        
        return $variants;
    }
    
    private function isUniqueCombination($affiliate, $recipient, $geo)
    {
        $key = strtolower($affiliate) . '|' . strtolower($recipient) . '|' . strtolower($geo);
        return !isset($this->usedCombinations[$key]);
    }
    
    private function markCombinationAsUsed($affiliate, $recipient, $geo)
    {
        $key = strtolower($affiliate) . '|' . strtolower($recipient) . '|' . strtolower($geo);
        $this->usedCombinations[$key] = true;
    }

    private function validateCapGeoCount($capValue, $geoValue)
    {
        $capCount = count(explode(' ', trim($capValue)));
        $geoCount = count(explode(' ', trim($geoValue)));
        
        return $capCount === $geoCount;
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
        
        // Вариант 1: Максимально длинные значения с корректными пропорциями
        $variants[] = [
            'extreme_type' => 'max_length',
            'affiliate' => ['affiliate:', 'Very-Long-Affiliate-Name-With-Special-Characters-And-Numbers-123'],
            'recipient' => ['recipient:', 'Extremely-Long-Recipient-Name-For-Testing-Maximum-Field-Length'],
            'cap' => ['cap:', '100 200 300 400 500'],
            'geo' => ['geo:', 'RU UA KZ DE FR'],
        ];
        
        // Вариант 2: Специальные символы - УДАЛЕН по требованию пользователя
        // Вариант 3: Все поля пустые - УДАЛЕН по требованию пользователя
        
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
        // ЗАФИКСИРОВАННЫЙ СТАНДАРТ ПОРЯДКА ПОЛЕЙ:
        // 1. affiliate: (всегда первое)
        // 2. recipient: (всегда второе) 
        // 3. cap: (всегда третье)
        // 4. geo: (всегда четвертое)
        // 5. опциональные поля (в разном порядке для тестирования)
        $orders = [
            ['affiliate', 'recipient', 'cap', 'geo', 'schedule', 'total', 'language', 'funnel', 'test'],
            ['affiliate', 'recipient', 'cap', 'geo', 'language', 'funnel', 'schedule', 'total', 'test'],
            ['affiliate', 'recipient', 'cap', 'geo', 'total', 'language', 'funnel', 'test', 'schedule'],
            ['affiliate', 'recipient', 'cap', 'geo', 'funnel', 'test', 'schedule', 'language', 'total'],
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
            ['test'],
            ['schedule', 'language', 'total'],
            ['test', 'funnel'],
        ];
        
        return $emptyVariants[$index % count($emptyVariants)];
    }
    
    private function getRandomUpdateFields($index, $combinations)
    {
        $updateFields = [
            ['schedule' => '24/7', 'total' => '500'],
            ['language' => 'en', 'funnel' => 'crypto'],
            ['pending_acq' => 'yes', 'freeze_status_on_acq' => 'no'],
            ['test' => 'yes', 'funnel' => 'forex'],
            ['schedule' => '10-19', 'language' => 'ru'],
            ['total' => '1000', 'date' => '24.02'],
        ];
        
        return $updateFields[$index % count($updateFields)];
    }
    
    private function getRandomReplyFields($index, $combinations)
    {
        $replyFields = [
            ['schedule' => '10-19', 'total' => '300'],
            ['language' => 'en', 'funnel' => 'forex'],
            ['test' => 'debug', 'total' => '999'],
            ['pending_acq' => 'yes', 'total' => '1000'],
            ['schedule' => '24/7', 'funnel' => 'crypto'],
        ];
        
        return $replyFields[$index % count($replyFields)];
    }
    
    private function getRandomQuoteFields($index, $combinations)
    {
        $quoteFields = [
            ['schedule' => '12-20', 'total' => '400'],
            ['language' => 'de', 'funnel' => 'binary'],
            ['test' => 'live', 'funnel' => 'crypto'],
            ['schedule' => '24/7', 'total' => '500'],
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
        try {
            // Отключаем проверку внешних ключей
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Очищаем все тестовые данные в правильном порядке
            // Сначала удаляем зависимые таблицы
            CapHistory::truncate();
            Cap::truncate();
            Message::truncate();
            Chat::truncate();
            
            // Включаем проверку внешних ключей обратно
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            
        } catch (\Exception $e) {
            // Если TRUNCATE не работает, используем DELETE
            $this->warn('TRUNCATE не удался, используем DELETE...');
            
            // Удаляем в правильном порядке
            CapHistory::query()->delete();
            Cap::query()->delete();
            Message::query()->delete();
            Chat::query()->delete();
            
            // Сбрасываем AUTO_INCREMENT
            DB::statement('ALTER TABLE cap_history AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE caps AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE messages AUTO_INCREMENT = 1');
            DB::statement('ALTER TABLE chats AUTO_INCREMENT = 1');
        }
        
        // Сбрасываем отслеживание использованных комбинаций
        $this->usedCombinations = [];
        
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
                    // Для пустых полей добавляем только двоеточие без значения
                    $message .= $fieldData[0] . "\n";
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
            
            $capsByGeo = Cap::selectRaw('geo, COUNT(*) as count')
                           ->groupBy('geo')
                           ->orderBy('count', 'desc')
                           ->limit(10)
                           ->get();
            
            $this->info('Топ-10 гео по количеству кап:');
            foreach ($capsByGeo as $stat) {
                $this->info("  {$stat->geo}: {$stat->count}");
            }
        }
        
        $recognitionRate = $messageCount > 0 ? round(($capCount / $messageCount) * 100, 2) : 0;
        $this->info("🎯 Процент распознавания кап: {$recognitionRate}%");
        
        // Статистика уникальных комбинаций
        $uniqueCombinations = count($this->usedCombinations);
        $this->info("🔗 Уникальных комбинаций создано: {$uniqueCombinations}");
        
        if ($recognitionRate < 80) {
            $this->warn("⚠️  Низкий процент распознавания! Возможно, есть проблемы с парсингом.");
        } else {
            $this->info("✅ Отличный процент распознавания!");
        }
    }

    /**
     * Анализирует ожидаемые результаты перед отправкой сообщения
     */
    private function analyzeExpectedResults($messageText, $operationType, $variant)
    {
        $expected = [
            'should_create_cap' => false,
            'should_update_cap' => false,
            'should_create_message' => true,
            'should_update_status' => false,
            'expected_fields' => [],
            'expected_status' => null,
            'operation_type' => $operationType
        ];
        
        // Анализируем тип операции
        if (str_contains($operationType, 'status')) {
            $expected['should_update_status'] = true;
            $expected['expected_status'] = $this->extractStatusFromMessage($messageText);
        } elseif (str_contains($operationType, 'update') || str_contains($operationType, 'reply') || str_contains($operationType, 'quote')) {
            $expected['should_update_cap'] = true;
            $expected['expected_fields'] = $this->extractFieldsFromMessage($messageText);
        } elseif (str_contains($operationType, 'create')) {
            $expected['expected_fields'] = $this->extractFieldsFromMessage($messageText);
            
            // Проверяем, существует ли уже такая комбинация в базе данных
            $existingCap = null;
            if (isset($expected['expected_fields']['affiliate']) && 
                isset($expected['expected_fields']['recipient']) && 
                isset($expected['expected_fields']['geo'])) {
                
                $affiliate = strtolower($expected['expected_fields']['affiliate']);
                $recipient = strtolower($expected['expected_fields']['recipient']);
                $geoString = strtolower($expected['expected_fields']['geo']);
                
                // Разделяем geo на отдельные значения
                $geos = preg_split('/\s+/', trim($geoString));
                
                // Проверяем, существует ли хотя бы одна запись с любым из geo
                foreach ($geos as $geo) {
                    $geo = trim($geo);
                    if (!empty($geo)) {
                        $existingCap = Cap::where('affiliate_name', $affiliate)
                                          ->where('recipient_name', $recipient)
                                          ->where('geo', $geo)
                                          ->first();
                        if ($existingCap) {
                            break; // Нашли существующую запись
                        }
                    }
                }
            }
            
            if ($existingCap) {
                $expected['should_update_cap'] = true;
                $expected['should_create_cap'] = false;
            } else {
                $expected['should_create_cap'] = true;
                $expected['should_update_cap'] = false;
            }
        }
        
        return $expected;
    }

    /**
     * Проверяет фактические результаты в базе данных
     */
    private function checkActualResults($messageText, $operationType, $beforeCounts, $testMessage)
    {
        $afterCounts = $this->getDatabaseCounts();
        
        $actual = [
            'created_messages' => $afterCounts['messages'] - $beforeCounts['messages'],
            'created_caps' => $afterCounts['caps'] - $beforeCounts['caps'],
            'created_history' => $afterCounts['cap_history'] - $beforeCounts['cap_history'],
            'updated_caps' => 0,
            'actual_fields' => [],
            'actual_status' => null
        ];
        
        // Получаем созданное сообщение
        $telegramChatId = $testMessage['message']['chat']['id'];
        $telegramMessageId = $testMessage['message']['message_id'];
        
        // Сначала найдем чат по telegram chat_id
        $chat = Chat::where('chat_id', $telegramChatId)->first();
        
        if ($chat) {
            // Теперь найдем сообщение по chat_id (внешний ключ) и telegram_message_id
            $message = Message::where('chat_id', $chat->id)
                ->where('telegram_message_id', $telegramMessageId)
                ->first();
            
            if ($message) {
                // Получаем связанные капы
                $caps = Cap::where('message_id', $message->id)->get();
                
                if ($caps->count() > 0) {
                    $cap = $caps->first();
                    
                    // Получаем исходный порядок geo из сообщения
                    $originalGeoOrder = $this->getOriginalGeoOrder($messageText);
                    
                    // Собираем все geo из всех записей для этого сообщения
                    $allGeos = $caps->pluck('geo')->filter()->toArray();
                    
                    // Сортируем согласно исходному порядку из сообщения
                    $sortedGeos = $this->sortGeosByOriginalOrder($allGeos, $originalGeoOrder);
                    
                    $actual['actual_fields'] = [
                        'affiliate' => $cap->affiliate_name,
                        'recipient' => $cap->recipient_name,
                        'geo' => implode(' ', $sortedGeos),
                        'total' => $cap->total_amount,
                        'schedule' => $cap->schedule,
                        'date' => $cap->date,
                        'language' => $cap->language,
                        'funnel' => $cap->funnel,
                        'pending_acq' => $cap->pending_acq,
                        'freeze_status_on_acq' => $cap->freeze_status_on_acq,
                        'status' => $cap->status
                    ];
                    $actual['actual_status'] = $cap->status;
                }
                
                // Проверяем обновления через историю
                $historyRecords = CapHistory::where('message_id', $message->id)->get();
                $actual['updated_caps'] = $historyRecords->count();
            }
        }
        
        return $actual;
    }

    /**
     * Сравнивает ожидаемые и фактические результаты
     */
    private function compareAndReportResults($messageIndex, $expectedResults, $actualResults, $messageText, $operationType)
    {
        $isCorrect = true;
        
        // Сокращаем сообщение для вывода
        $shortMessage = mb_substr($messageText, 0, 100) . (mb_strlen($messageText) > 100 ? '...' : '');
        
        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("📝 СООБЩЕНИЕ #{$messageIndex} ({$operationType})");
        $this->info("Текст: {$shortMessage}");
        $this->info("────────────────────────────────────────────────────────────────");
        
        // Проверяем создание сообщения
        if ($expectedResults['should_create_message'] && $actualResults['created_messages'] == 0) {
            $this->error("❌ Сообщение не создано в базе данных!");
            $isCorrect = false;
        } elseif ($actualResults['created_messages'] > 0) {
            $this->info("✅ Сообщение создано в базе данных");
        }
        
        // Проверяем создание капы
        if ($expectedResults['should_create_cap']) {
            if ($actualResults['created_caps'] > 0) {
                $this->info("✅ Кап создан (ожидалось: создание)");
                $this->checkFieldsMatch($expectedResults['expected_fields'], $actualResults['actual_fields']);
            } else {
                $this->error("❌ Кап НЕ создан (ожидалось: создание)");
                $isCorrect = false;
            }
        }
        
        // Проверяем обновление капы
        if ($expectedResults['should_update_cap']) {
            if ($actualResults['updated_caps'] > 0) {
                $this->info("✅ Кап обновлен (ожидалось: обновление)");
                $this->checkFieldsMatch($expectedResults['expected_fields'], $actualResults['actual_fields']);
            } elseif ($actualResults['created_caps'] > 0) {
                $this->info("✅ Кап создан вместо обновления (комбинация была уникальной)");
                $this->checkFieldsMatch($expectedResults['expected_fields'], $actualResults['actual_fields']);
            } else {
                $this->error("❌ Кап НЕ обновлен (ожидалось: обновление)");
                $isCorrect = false;
            }
        }
        
        // Проверяем изменение статуса
        if ($expectedResults['should_update_status']) {
            if ($expectedResults['expected_status'] && $actualResults['actual_status'] == $expectedResults['expected_status']) {
                $this->info("✅ Статус изменен корректно: {$actualResults['actual_status']}");
            } else {
                $this->error("❌ Статус НЕ изменен или некорректен. Ожидалось: {$expectedResults['expected_status']}, Получено: {$actualResults['actual_status']}");
                $isCorrect = false;
            }
        }
        
        // Выводим итоговый результат
        if ($isCorrect) {
            $this->info("🎉 РЕЗУЛЬТАТ: КОРРЕКТНО");
        } else {
            $this->error("💥 РЕЗУЛЬТАТ: НЕКОРРЕКТНО");
        }
        
        return $isCorrect;
    }

    /**
     * Проверяет соответствие полей
     */
    private function checkFieldsMatch($expectedFields, $actualFields)
    {
        if (empty($expectedFields)) {
            return;
        }
        
        $this->info("📊 ПРОВЕРКА ПОЛЕЙ:");
        
        foreach ($expectedFields as $field => $expectedValue) {
            if (empty($expectedValue)) {
                continue;
            }
            
            $actualValue = $actualFields[$field] ?? null;
            
            if ($this->compareFieldValues($expectedValue, $actualValue)) {
                $expectedStr = is_array($expectedValue) ? implode(', ', $expectedValue) : $expectedValue;
                $actualStr = is_array($actualValue) ? implode(', ', $actualValue) : $actualValue;
                $this->info("  ✅ {$field}: '{$expectedStr}' = '{$actualStr}'");
            } else {
                $expectedStr = is_array($expectedValue) ? implode(', ', $expectedValue) : $expectedValue;
                $actualStr = is_array($actualValue) ? implode(', ', $actualValue) : $actualValue;
                $this->error("  ❌ {$field}: ожидалось '{$expectedStr}', получено '{$actualStr}'");
            }
        }
    }

    /**
     * Сравнивает значения полей с учетом массивов и строк
     */
    private function compareFieldValues($expected, $actual)
    {
        if (is_array($expected) && is_array($actual)) {
            return array_diff($expected, $actual) === array_diff($actual, $expected);
        }
        
        if (is_array($expected)) {
            $expected = implode(' ', $expected);
        }
        
        if (is_array($actual)) {
            $actual = implode(' ', $actual);
        }
        
        return strtolower(trim($expected)) === strtolower(trim($actual));
    }

    /**
     * Извлекает поля из сообщения
     */
    private function extractFieldsFromMessage($messageText)
    {
        $fields = [];
        
        // Конвертируем в нижний регистр для анализа
        $lowerText = strtolower($messageText);
        
        // Извлекаем все поля, которые система должна распознать
        $fieldPatterns = [
            'affiliate' => '/affiliate:\s*([^\n]+)/i',
            'recipient' => '/recipient:\s*([^\n]+)/i',
            'geo' => '/geo:\s*([^\n]+)/i',
            'total' => '/total:\s*([^\n]+)/i',
            'schedule' => '/schedule:\s*([^\n]+)/i',
            'date' => '/date:\s*([^\n]+)/i',
            'language' => '/language:\s*([^\n]+)/i',
            'funnel' => '/funnel:\s*([^\n]+)/i',
            'pending_acq' => '/pending acq:\s*([^\n]+)/i',
            'freeze_status_on_acq' => '/freeze status on acq:\s*([^\n]+)/i'
        ];
        
        foreach ($fieldPatterns as $field => $pattern) {
            if (preg_match($pattern, $lowerText, $matches)) {
                $fields[$field] = trim($matches[1]);
            }
        }
        
        return $fields;
    }

    /**
     * Извлекает статус из сообщения
     */
    private function extractStatusFromMessage($messageText)
    {
        $lowerText = strtolower(trim($messageText));
        
        if (in_array($lowerText, ['run', 'stop', 'delete', 'restore'])) {
            return $lowerText;
        }
        
        return null;
    }

    /**
     * Получает количество записей в базе данных
     */
    private function getDatabaseCounts()
    {
        return [
            'chats' => Chat::count(),
            'messages' => Message::count(),
            'caps' => Cap::count(),
            'cap_history' => CapHistory::count()
        ];
    }

    /**
     * Получает исходный порядок geo из сообщения
     */
    private function getOriginalGeoOrder($messageText)
    {
        $geoOrder = [];
        $lowerText = strtolower($messageText);

        // Ищем все поля geo в сообщении
        if (preg_match_all('/geo:\s*([^\n]+)/i', $lowerText, $matches)) {
            foreach ($matches[1] as $geoString) {
                $geos = preg_split('/\s+/', trim($geoString));
                foreach ($geos as $geo) {
                    $geo = trim($geo);
                    if (!empty($geo)) {
                        $geoOrder[] = $geo;
                    }
                }
            }
        }
        return $geoOrder;
    }

    /**
     * Сортирует массив geo значений согласно исходному порядку из сообщения
     */
    private function sortGeosByOriginalOrder($geos, $originalOrder)
    {
        $sortedGeos = [];
        foreach ($originalOrder as $geo) {
            if (in_array($geo, $geos)) {
                $sortedGeos[] = $geo;
            }
        }
        return $sortedGeos;
    }
} 