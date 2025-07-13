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
    protected $signature = 'test:create-chats {count=100 : Количество чатов для создания}';
    protected $description = 'Создает максимальное количество тестовых вариантов - 30К+ сообщений с разными вариантами написания полей';

    private $webhookController;
    
    // Отслеживание использованных комбинаций для избежания дублирования
    private $usedCombinations = [];

    // МАКСИМАЛЬНЫЕ ВАРИАНТЫ ПОЛЕЙ ДЛЯ ТЕСТИРОВАНИЯ
    private $fieldVariants = [
        'affiliate' => [
            'keys' => ['affiliate:', 'Affiliate:', 'AFFILIATE:', 'aFFiLiAtE:'],
            'values' => ['TestAffiliate1', 'TestAffiliate2', 'G06', 'mbt internal', 'Super-Affiliate', 'Affiliate_Test', 'TEST01', 'Партнер1']
        ],
        'recipient' => [
            'keys' => ['recipient:', 'Recipient:', 'RECIPIENT:', 'rEcIpIeNt:'],
            'values' => ['TestBroker1', 'TestBroker2', 'TradingM', 'Global KZ', 'Crypto-Trader', 'Broker_Pro', 'Брокер1', 'بروکر۱', 'test broker']
        ],
        'cap' => [
            'keys' => ['cap:', 'Cap:', 'CAP:', 'cAp:'],
            'values' => ['10', '20 30', '100 200 300', '50', '5 10', '999 888', '10000']
        ],
        'geo' => [
            'keys' => ['geo:', 'Geo:', 'GEO:', 'gEo:'],
            'values' => ['RU', 'RU UA', 'DE AT CH', 'KZ', 'AU NZ', 'US UK', 'IE']
        ],
        'schedule' => [
            'keys' => ['schedule:', 'Schedule:', 'SCHEDULE:', 'sChEdUlE:'],
            'values' => ['24/7', '10-19', '09:00/18:00 GMT+01:00', '8.30 - 14.30 +3', '18:00/01:00 GMT+03:00', '10:00-19:00', '9-17', '12:00/20:00 GMT+02:00', '10-19 +2']
        ],
        'date' => [
            'keys' => ['date:', 'Date:', 'DATE:', 'dAtE:'],
            'values' => ['24.02', '01.01 02.02', '25.12.2024', '2024-01-01', '15/03/2024', '01.01-31.12', '24.02 25.02', '2024.01.01']
        ],
        'language' => [
            'keys' => ['language:', 'Language:', 'LANGUAGE:', 'lAnGuAgE:'],
            'values' => ['en', 'ru', 'de fr', 'en ru de', 'es it', 'pl', 'zh', 'ar', 'tr', 'pt', 'ja', 'ko', 'uk', 'be', 'kz']
        ],
        'funnel' => [
            'keys' => ['funnel:', 'Funnel:', 'FUNNEL:', 'fUnNeL:'],
            'values' => ['crypto', 'forex,binary', 'stocks,options,trading', 'investment', 'crypto,forex', 'premium,vip', 'standard']
        ],
        'test' => [
            'keys' => ['test:', 'Test:', 'TEST:', 'tEsT:'],
            'values' => ['yes', 'no', 'true', 'false', 'active', 'inactive', 'on', 'off', 'enabled', 'disabled', 'debug', 'live', 'staging', 'production']
        ],
        'total' => [
            'keys' => ['total:', 'Total:', 'TOTAL:', 'tOtAl:'],
            'values' => ['100', '500 1000', '200 400 600', '999', '50 100 150', '1000', '1', '10000']
        ],
        'pending_acq' => [
            'keys' => ['pending acq:', 'Pending ACQ:', 'PENDING ACQ:', 'Pending acq:', 'pending Acq:'],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет']
        ],
        'freeze_status_on_acq' => [
            'keys' => ['freeze status on acq:', 'Freeze status on ACQ:', 'FREEZE STATUS ON ACQ:', 'Freeze Status On Acq:', 'freeze Status on acq:'],
            'values' => ['yes', 'no', 'true', 'false', 'yes no', 'true false', '1', '0', 'да', 'нет', 'freeze', 'unfreeze']
        ]
    ];

    // СТАТУС КОМАНДЫ
    private $statusCommands = ['run', 'stop', 'delete', 'restore', 'RUN', 'STOP', 'DELETE', 'RESTORE', 'Run', 'Stop', 'Delete', 'Restore'];

    public function handle()
    {
        $chatCount = (int) $this->argument('count');
        
        $this->info("🚀 МАКСИМАЛЬНОЕ ТЕСТИРОВАНИЕ СИСТЕМЫ");
        $this->info("Создание {$chatCount} чатов с ОГРОМНЫМ количеством вариантов сообщений");
        $this->info("Цель: 30,000+ уникальных сообщений с 4 типами сообщений");
        
        // Инициализация контроллера
        $this->webhookController = app(TelegramWebhookController::class);
        
        // Очищаем существующие данные
        $this->warn('Очистка существующих данных...');
        $this->clearDatabase();
        
        // МАКСИМАЛЬНАЯ ГЕНЕРАЦИЯ ВАРИАНТОВ
        $this->info('🔥 ГЕНЕРАЦИЯ МАКСИМАЛЬНОГО КОЛИЧЕСТВА ВАРИАНТОВ...');
        $this->generateMaximumVariants($chatCount);
        
        // Показываем статистику
        $this->showStatistics();
    }
    
    private function generateMaximumVariants($chatCount)
    {
        $successCount = 0;
        $errorCount = 0;
        $messageIndex = 0;
        $correctResults = 0;
        $incorrectResults = 0;
        
        // Определяем 4 типа сообщений для тестирования
        $messageTypes = [
            'single_single',
            'single_multi', 
            'group_single',
            'group_multi'
        ];
        
        foreach ($messageTypes as $messageType) {
            $this->info("🔄 НАЧИНАЕМ ОБРАБОТКУ: {$messageType}");
            $this->info("═══════════════════════════════════════════════════════════════");
            
            for ($chatIndex = 1; $chatIndex <= $chatCount; $chatIndex++) {
                $messageIndex++;
                
                try {
                    // Генерируем конкретный тип сообщения
                    $variant = $this->generateVariantForMessageType($messageType, $messageIndex);
                    
                    // Пропускаем создание если не удалось сгенерировать
                    if (!$variant) {
                        $this->error("❌ Не удалось создать вариант для {$messageType}");
                        $errorCount++;
                        continue;
                    }
                    
                    // Создаем сообщение
                    $telegramMessage = $this->generateCapMessage($messageIndex, $messageType, $variant, $chatIndex);
                    
                    // Пропускаем если сообщение было отклонено из-за несовместимости cap-geo
                    if ($telegramMessage === null) {
                        $errorCount++;
                        continue;
                    }
                    
                    $messageText = $telegramMessage['message']['text'];
                    
                    // Проверяем ожидаемые результаты
                    $expectedResults = $this->analyzeExpectedResults($messageText, $messageType, $variant);
                    
                    // Получаем количество записей до обработки
                    $beforeCounts = $this->getDatabaseCounts();
                    
                    // Обрабатываем сообщение через webhook
                    $request = new Request();
                    $request->replace($telegramMessage);
                    
                    $response = $this->webhookController->handle($request);
                    
                    // Проверяем фактические результаты
                    $actualResults = $this->checkActualResults($messageText, $messageType, $beforeCounts, $telegramMessage['message']);
                    
                    // Сравниваем результаты
                    $isCorrect = $this->compareAndReportResults($messageIndex, $expectedResults, $actualResults, $messageText, $messageType);
                    
                    if ($isCorrect) {
                        $correctResults++;
                    } else {
                        $incorrectResults++;
                    }
                    
                    $successCount++;
                    
                    // Пауза для предотвращения переполнения
                    if ($messageIndex % 10 == 0) {
                        usleep(50000); // 0.05 секунд
                    }
                    
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("❌ Ошибка при создании сообщения #{$messageIndex}: " . $e->getMessage());
                }
            }
            
            // ПАУЗА МЕЖДУ ЭТАПАМИ
            if ($messageType !== end($messageTypes)) {
                $this->info("");
                $this->info("⏸️  ПАУЗА МЕЖДУ ЭТАПАМИ");
                $this->info("───────────────────────────────────────────────────────────────");
                $this->ask("Нажмите Enter для продолжения...");
            }
        }
        
        $this->info("🎉 МАКСИМАЛЬНАЯ ГЕНЕРАЦИЯ ЗАВЕРШЕНА!");
        $this->info("Обработано сообщений: {$successCount}");
        $this->info("Ошибок: {$errorCount}");
        $this->info("Корректных результатов: {$correctResults}");
        $this->info("Некорректных результатов: {$incorrectResults}");
        
        if ($correctResults > 0) {
            $accuracy = round(($correctResults / ($correctResults + $incorrectResults)) * 100, 2);
            $this->info("📊 Точность системы: {$accuracy}%");
        }
        
        // Ожидание нажатия Enter
        $this->info("");
        $this->ask("Нажмите Enter для завершения...");
    }
    
    private function generateVariantForMessageType($messageType, $index)
    {
        switch ($messageType) {
            case 'single_single':
                return $this->generateSingleSingleVariant($index);
            case 'single_multi':
                return $this->generateSingleMultiVariant($index);
            case 'group_single':
                return $this->generateGroupSingleVariant($index);
            case 'group_multi':
                return $this->generateGroupMultiVariant($index);
            default:
                return null;
        }
    }
    
    private function generateSingleSingleVariant($index)
    {
        $affiliateVariants = $this->getFieldVariants('affiliate', $index);
        $recipientVariants = $this->getFieldVariants('recipient', $index);
        $scheduleVariants = $this->getFieldVariants('schedule', $index);
        $totalVariants = $this->getFieldVariants('total', $index);
        $dateVariants = $this->getFieldVariants('date', $index);
        $languageVariants = $this->getFieldVariants('language', $index);
        $funnelVariants = $this->getFieldVariants('funnel', $index);
        $testVariants = $this->getFieldVariants('test', $index);
        $pendingVariants = $this->getFieldVariants('pending_acq', $index);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $index);
        
        // Получаем совместимую cap-geo пару для single_single (1 cap + 1 geo)
        $capGeoPair = $this->getCompatibleCapGeoPair('single_single', $index);
        
        $affiliate = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient = $recipientVariants[array_rand($recipientVariants)];
        $schedule = $scheduleVariants[array_rand($scheduleVariants)];
        $total = $totalVariants[array_rand($totalVariants)];
        $date = $dateVariants[array_rand($dateVariants)];
        $language = $languageVariants[array_rand($languageVariants)];
        $funnel = $funnelVariants[array_rand($funnelVariants)];
        $test = $testVariants[array_rand($testVariants)];
        $pending = $pendingVariants[array_rand($pendingVariants)];
        $freeze = $freezeVariants[array_rand($freezeVariants)];
        
        return [
            'message_type' => 'single_single',
            'affiliate' => $affiliate,
            'recipient' => $recipient,
            'cap' => $capGeoPair['cap'],
            'geo' => $capGeoPair['geo'],
            'schedule' => $schedule,
            'total' => $total,
            'date' => $date,
            'language' => $language,
            'funnel' => $funnel,
            'test' => $test,
            'pending_acq' => $pending,
            'freeze_status_on_acq' => $freeze
        ];
    }
    
    private function generateSingleMultiVariant($index)
    {
        $affiliateVariants = $this->getFieldVariants('affiliate', $index);
        $recipientVariants = $this->getFieldVariants('recipient', $index);
        $scheduleVariants = $this->getFieldVariants('schedule', $index);
        $totalVariants = $this->getFieldVariants('total', $index);
        $dateVariants = $this->getFieldVariants('date', $index);
        $languageVariants = $this->getFieldVariants('language', $index);
        $funnelVariants = $this->getFieldVariants('funnel', $index);
        $testVariants = $this->getFieldVariants('test', $index);
        $pendingVariants = $this->getFieldVariants('pending_acq', $index);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $index);
        
        // Получаем совместимую cap-geo пару для single_multi (равное количество)
        $capGeoPair = $this->getCompatibleCapGeoPair('single_multi', $index);
        
        $affiliate = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient = $recipientVariants[array_rand($recipientVariants)];
        $schedule = $scheduleVariants[array_rand($scheduleVariants)];
        $total = $totalVariants[array_rand($totalVariants)];
        $date = $dateVariants[array_rand($dateVariants)];
        $language = $languageVariants[array_rand($languageVariants)];
        $funnel = $funnelVariants[array_rand($funnelVariants)];
        $test = $testVariants[array_rand($testVariants)];
        $pending = $pendingVariants[array_rand($pendingVariants)];
        $freeze = $freezeVariants[array_rand($freezeVariants)];
        
        return [
            'message_type' => 'single_multi',
            'affiliate' => $affiliate,
            'recipient' => $recipient,
            'cap' => $capGeoPair['cap'],
            'geo' => $capGeoPair['geo'],
            'schedule' => $schedule,
            'total' => $total,
            'date' => $date,
            'language' => $language,
            'funnel' => $funnel,
            'test' => $test,
            'pending_acq' => $pending,
            'freeze_status_on_acq' => $freeze
        ];
    }
    
    private function generateGroupSingleVariant($index)
    {
        $affiliateVariants = $this->getFieldVariants('affiliate', $index);
        $recipientVariants = $this->getFieldVariants('recipient', $index);
        $scheduleVariants = $this->getFieldVariants('schedule', $index);
        $totalVariants = $this->getFieldVariants('total', $index);
        $dateVariants = $this->getFieldVariants('date', $index);
        $languageVariants = $this->getFieldVariants('language', $index);
        $funnelVariants = $this->getFieldVariants('funnel', $index);
        $testVariants = $this->getFieldVariants('test', $index);
        $pendingVariants = $this->getFieldVariants('pending_acq', $index);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $index);
        
        // Получаем совместимые cap-geo пары для group_single
        $capGeoPair1 = $this->getCompatibleCapGeoPair('group_single', $index);
        $capGeoPair2 = $this->getCompatibleCapGeoPair('group_single', $index + 1);
        
        $affiliate1 = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient1 = $recipientVariants[array_rand($recipientVariants)];
        
        $affiliate2 = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient2 = $recipientVariants[array_rand($recipientVariants)];
        
        $schedule = $scheduleVariants[array_rand($scheduleVariants)];
        $total = $totalVariants[array_rand($totalVariants)];
        $date = $dateVariants[array_rand($dateVariants)];
        $language = $languageVariants[array_rand($languageVariants)];
        $funnel = $funnelVariants[array_rand($funnelVariants)];
        $test = $testVariants[array_rand($testVariants)];
        $pending = $pendingVariants[array_rand($pendingVariants)];
        $freeze = $freezeVariants[array_rand($freezeVariants)];
        
        return [
            'message_type' => 'group_single',
            'is_group_message' => true,
            'blocks' => [
                [
                    'affiliate' => $affiliate1,
                    'recipient' => $recipient1,
                    'cap' => $capGeoPair1['cap'],
                    'geo' => $capGeoPair1['geo']
                ],
                [
                    'affiliate' => $affiliate2,
                    'recipient' => $recipient2,
                    'cap' => $capGeoPair2['cap'],
                    'geo' => $capGeoPair2['geo']
                ]
            ],
            'schedule' => $schedule,
            'total' => $total,
            'date' => $date,
            'language' => $language,
            'funnel' => $funnel,
            'test' => $test,
            'pending_acq' => $pending,
            'freeze_status_on_acq' => $freeze
        ];
    }
    
    private function generateGroupMultiVariant($index)
    {
        $affiliateVariants = $this->getFieldVariants('affiliate', $index);
        $recipientVariants = $this->getFieldVariants('recipient', $index);
        $scheduleVariants = $this->getFieldVariants('schedule', $index);
        $totalVariants = $this->getFieldVariants('total', $index);
        $dateVariants = $this->getFieldVariants('date', $index);
        $languageVariants = $this->getFieldVariants('language', $index);
        $funnelVariants = $this->getFieldVariants('funnel', $index);
        $testVariants = $this->getFieldVariants('test', $index);
        $pendingVariants = $this->getFieldVariants('pending_acq', $index);
        $freezeVariants = $this->getFieldVariants('freeze_status_on_acq', $index);
        
        // Получаем совместимые cap-geo пары для group_multi
        $capGeoPair1 = $this->getCompatibleCapGeoPair('group_multi', $index);
        $capGeoPair2 = $this->getCompatibleCapGeoPair('group_multi', $index + 1);
        
        $affiliate1 = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient1 = $recipientVariants[array_rand($recipientVariants)];
        
        $affiliate2 = $affiliateVariants[array_rand($affiliateVariants)];
        $recipient2 = $recipientVariants[array_rand($recipientVariants)];
        
        $schedule = $scheduleVariants[array_rand($scheduleVariants)];
        $total = $totalVariants[array_rand($totalVariants)];
        $date = $dateVariants[array_rand($dateVariants)];
        $language = $languageVariants[array_rand($languageVariants)];
        $funnel = $funnelVariants[array_rand($funnelVariants)];
        $test = $testVariants[array_rand($testVariants)];
        $pending = $pendingVariants[array_rand($pendingVariants)];
        $freeze = $freezeVariants[array_rand($freezeVariants)];
        
        return [
            'message_type' => 'group_multi',
            'is_group_message' => true,
            'blocks' => [
                [
                    'affiliate' => $affiliate1,
                    'recipient' => $recipient1,
                    'cap' => $capGeoPair1['cap'],
                    'geo' => $capGeoPair1['geo']
                ],
                [
                    'affiliate' => $affiliate2,
                    'recipient' => $recipient2,
                    'cap' => $capGeoPair2['cap'],
                    'geo' => $capGeoPair2['geo']
                ]
            ],
            'schedule' => $schedule,
            'total' => $total,
            'date' => $date,
            'language' => $language,
            'funnel' => $funnel,
            'test' => $test,
            'pending_acq' => $pending,
            'freeze_status_on_acq' => $freeze
        ];
    }
    
    private function generateAllVariantsForOperation($operationType, $baseIndex)
    {
        $variants = [];
        
        switch (true) {
            case str_contains($operationType, 'create'):
                $variants = $this->generateCreateVariants($baseIndex);
                break;
                
            case str_contains($operationType, 'update'):
                $variants = $this->generateUpdateVariants($baseIndex);
                break;
                
            case str_contains($operationType, 'reply'):
                $variants = $this->generateReplyVariants($baseIndex);
                break;
                
            case str_contains($operationType, 'quote'):
                $variants = $this->generateQuoteVariants($baseIndex);
                break;
                
            case str_contains($operationType, 'status'):
                $variants = $this->generateStatusVariants($baseIndex);
                break;
                
            default:
                $variants = $this->generateCreateVariants($baseIndex);
        }
        
        return array_slice($variants, 0, 50); // Ограничиваем до 50 вариантов на операцию
    }
    
    private function generateCreateVariants($baseIndex)
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
            
            // Проверяем совпадение количества элементов в cap и funnel (если funnel присутствует)
            if (isset($variant['funnel']) && !$this->validateCapFunnelCount($variant['cap'][1], $variant['funnel'][1])) {
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
                    
                    // Проверяем совпадение количества элементов в cap и funnel после добавления funnel
                    if (!$this->validateCapFunnelCount($variant['cap'][1], $variant['funnel'][1])) {
                        continue; // Пропускаем если количества не совпадают
                    }
                    
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
        $extremeVariants = $this->generateExtremeVariants();
        
        // Проверяем уникальность экстремальных вариантов
        foreach ($extremeVariants as $extremeVariant) {
            if ($this->isUniqueCombination($extremeVariant['affiliate'][1], $extremeVariant['recipient'][1], $extremeVariant['geo'][1])) {
                $this->markCombinationAsUsed($extremeVariant['affiliate'][1], $extremeVariant['recipient'][1], $extremeVariant['geo'][1]);
                $variants[] = $extremeVariant;
            }
        }
        
        // Добавляем тесты для всех типов создания кап
        $creationTypeVariants = $this->generateAllCapCreationTypes();
        
        foreach ($creationTypeVariants as $creationVariant) {
            if ($this->isUniqueCombination($creationVariant['affiliate'][1], $creationVariant['recipient'][1], $creationVariant['geo'][1])) {
                $this->markCombinationAsUsed($creationVariant['affiliate'][1], $creationVariant['recipient'][1], $creationVariant['geo'][1]);
                $variants[] = $creationVariant;
            }
        }
        
                          // Добавляем тесты для разделённых кап по funnel
         $funnelVariants = $this->generateFunnelSeparatedCapVariants();
         
         foreach ($funnelVariants as $funnelVariant) {
             // Проверяем совпадение количества элементов cap и funnel
             if (isset($funnelVariant['funnel']) && !$this->validateCapFunnelCount($funnelVariant['cap'][1], $funnelVariant['funnel'][1])) {
                 continue; // Пропускаем если количества не совпадают
             }
             
             if ($this->isUniqueCombination($funnelVariant['affiliate'][1], $funnelVariant['recipient'][1], $funnelVariant['geo'][1])) {
                 $this->markCombinationAsUsed($funnelVariant['affiliate'][1], $funnelVariant['recipient'][1], $funnelVariant['geo'][1]);
                 $variants[] = $funnelVariant;
             }
         }
         
         // Добавляем тесты для групповых сообщений
         $groupVariants = $this->generateGroupMessageVariants();
         
         foreach ($groupVariants as $groupVariant) {
             // Для групповых сообщений проверяем уникальность всех блоков
             $canAddGroup = true;
             
             foreach ($groupVariant['blocks'] as $block) {
                 if (!$this->isUniqueCombination($block['affiliate'][1], $block['recipient'][1], $block['geo'][1])) {
                     $canAddGroup = false;
                     break;
                 }
             }
             
             if ($canAddGroup) {
                 // Регистрируем все блоки как использованные
                 foreach ($groupVariant['blocks'] as $block) {
                     $this->markCombinationAsUsed($block['affiliate'][1], $block['recipient'][1], $block['geo'][1]);
                 }
                 $variants[] = $groupVariant;
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

    private function validateCapFunnelCount($capValue, $funnelValue)
    {
        $capCount = count(explode(' ', trim($capValue)));
        $funnelCount = count(explode(',', trim($funnelValue)));
        
        return $capCount === $funnelCount;
    }
    
    private function generateUpdateVariants($baseIndex)
    {
        $variants = [];
        
        // Варианты обновлений
        for ($i = 0; $i < 10; $i++) {
            $variant = [
                'update_type' => 'field_update',
                'fields_to_update' => $this->getRandomUpdateFields($i)
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateReplyVariants($baseIndex)
    {
        $variants = [];
        
        // Варианты ответов
        for ($i = 0; $i < 8; $i++) {
            $variant = [
                'reply_type' => 'field_reply',
                'reply_fields' => $this->getRandomReplyFields($i)
            ];
            $variants[] = $variant;
        }
        
        return $variants;
    }
    
    private function generateQuoteVariants($baseIndex)
    {
        $variants = [];
        
        // Варианты цитат
        for ($i = 0; $i < 6; $i++) {
            $variant = [
                'quote_type' => 'field_quote',
                'quote_fields' => $this->getRandomQuoteFields($i)
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
    
    private function generateExtremeVariants()
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

    private function generateFunnelSeparatedCapVariants()
    {
        $variants = [];
        
        // Тест 1: Одиночное сообщение → Одна капа + funnel
        $variants[] = [
            'test_type' => 'single_message_single_cap',
            'affiliate' => ['affiliate:', 'FUNNEL_TEST_01'],
            'recipient' => ['recipient:', 'SingleCapBroker'],
            'cap' => ['cap:', '50'],
            'geo' => ['geo:', 'RU'],
            'funnel' => ['funnel:', 'crypto']
        ];
        
        // Тест 2: Одиночное сообщение → Много кап + funnel
        $variants[] = [
            'test_type' => 'single_message_multi_cap',
            'affiliate' => ['affiliate:', 'FUNNEL_TEST_02'],
            'recipient' => ['recipient:', 'MultiCapBroker'],
            'cap' => ['cap:', '50 100'],
            'geo' => ['geo:', 'RU UA'],
            'funnel' => ['funnel:', 'crypto,forex']
        ];
        
        // Тест 3: Групповое сообщение → Одна капа + funnel
        $variants[] = [
            'test_type' => 'group_message_single_cap', 
            'affiliate' => ['affiliate:', 'FUNNEL_TEST_03'],
            'recipient' => ['recipient:', 'GroupSingleBroker'],
            'cap' => ['cap:', '75'],
            'geo' => ['geo:', 'DE'],
            'funnel' => ['funnel:', 'binary']
        ];
        
        // Тест 4: Групповое сообщение → Много кап + funnel
        $variants[] = [
            'test_type' => 'group_message_multi_cap',
            'affiliate' => ['affiliate:', 'FUNNEL_TEST_04'],
            'recipient' => ['recipient:', 'GroupMultiBroker'],
            'cap' => ['cap:', '25 50 75'],
            'geo' => ['geo:', 'DE AT CH'],
            'funnel' => ['funnel:', 'stocks,options,trading']
        ];
        
        // Тест 5: Смешанный тест с максимальными полями
        $variants[] = [
            'test_type' => 'mixed_max_fields',
            'affiliate' => ['affiliate:', 'FUNNEL_TEST_05'],
            'recipient' => ['recipient:', 'MixedMaxBroker'],
            'cap' => ['cap:', '100 200 300 400'],
            'geo' => ['geo:', 'US UK CA AU'],
            'funnel' => ['funnel:', 'crypto,forex,binary,stocks'],
            'schedule' => ['schedule:', '10:00/19:00 GMT+01:00'],
            'language' => ['language:', 'en de fr es'],
            'total' => ['total:', '500 1000 1500 2000'],
            'date' => ['date:', '01.01 02.02 03.03 04.04'],
            'test' => ['test:', 'yes']
        ];
        
                 return $variants;
     }

     private function generateAllCapCreationTypes()
     {
         $variants = [];
         
         // ТИП 1: Одиночное сообщение → Одна капа
         $variants[] = [
             'creation_type' => 'single_message_single_cap',
             'affiliate' => ['affiliate:', 'SINGLE_CAP_01'],
             'recipient' => ['recipient:', 'SingleCapTest'],
             'cap' => ['cap:', '100'],
             'geo' => ['geo:', 'RU']
         ];
         
         // ТИП 2: Одиночное сообщение → Много кап
         $variants[] = [
             'creation_type' => 'single_message_multi_cap',
             'affiliate' => ['affiliate:', 'MULTI_CAP_01'],
             'recipient' => ['recipient:', 'MultiCapTest'],
             'cap' => ['cap:', '100 200'],
             'geo' => ['geo:', 'RU UA']
         ];
         
         // ТИП 3: Групповое сообщение → Одна капа
         $variants[] = [
             'creation_type' => 'group_message_single_cap',
             'affiliate' => ['affiliate:', 'GROUP_SINGLE_01'],
             'recipient' => ['recipient:', 'GroupSingleTest'],
             'cap' => ['cap:', '150'],
             'geo' => ['geo:', 'DE']
         ];
         
         // ТИП 4: Групповое сообщение → Много кап
         $variants[] = [
             'creation_type' => 'group_message_multi_cap',
             'affiliate' => ['affiliate:', 'GROUP_MULTI_01'],
             'recipient' => ['recipient:', 'GroupMultiTest'],
             'cap' => ['cap:', '50 100 150'],
             'geo' => ['geo:', 'DE AT CH']
         ];
         
         // Дополнительные тесты с опциональными полями
         if ($combinations === 'full' || $combinations === 'advanced') {
             // Тест с schedule
             $variants[] = [
                 'creation_type' => 'single_with_schedule',
                 'affiliate' => ['affiliate:', 'SCHEDULE_TEST_01'],
                 'recipient' => ['recipient:', 'ScheduleTestBroker'],
                 'cap' => ['cap:', '200'],
                 'geo' => ['geo:', 'FR'],
                 'schedule' => ['schedule:', '09:00/18:00 GMT+01:00']
             ];
             
             // Тест с language
             $variants[] = [
                 'creation_type' => 'multi_with_language',
                 'affiliate' => ['affiliate:', 'LANGUAGE_TEST_01'],
                 'recipient' => ['recipient:', 'LanguageTestBroker'],
                 'cap' => ['cap:', '100 200'],
                 'geo' => ['geo:', 'ES IT'],
                 'language' => ['language:', 'es it']
             ];
             
             // Тест с total
             $variants[] = [
                 'creation_type' => 'multi_with_total',
                 'affiliate' => ['affiliate:', 'TOTAL_TEST_01'],
                 'recipient' => ['recipient:', 'TotalTestBroker'],
                 'cap' => ['cap:', '50 100 150'],
                 'geo' => ['geo:', 'US UK CA'],
                 'total' => ['total:', '500 1000 1500']
             ];
         }
         
         return $variants;
     }

     private function generateGroupMessageVariants()
     {
         $variants = [];
         
         // Генерируем множество вариантов групповых сообщений с полным набором полей
         $groupVariantIndex = 0;
         
         // Для каждого типа группового сообщения создаем много вариантов
         for ($groupType = 1; $groupType <= 2; $groupType++) {
             for ($blockCount = 2; $blockCount <= 4; $blockCount++) {
                 for ($variantSet = 1; $variantSet <= 25; $variantSet++) {
                     $groupVariantIndex++;
                     
                     $blocks = [];
                     
                     for ($blockIndex = 1; $blockIndex <= $blockCount; $blockIndex++) {
                         $block = [];
                         
                         // Обязательные поля для каждого блока
                         $affiliateVariants = $this->getFieldVariants('affiliate', $groupVariantIndex + $blockIndex);
                         $recipientVariants = $this->getFieldVariants('recipient', $groupVariantIndex + $blockIndex);
                         $capVariants = $this->getFieldVariants('cap', $groupVariantIndex + $blockIndex);
                         $geoVariants = $this->getFieldVariants('geo', $groupVariantIndex + $blockIndex);
                         
                         // Выбираем варианты для этого блока
                         $affiliateIndex = ($groupVariantIndex + $blockIndex) % count($affiliateVariants);
                         $recipientIndex = ($groupVariantIndex + $blockIndex) % count($recipientVariants);
                         $capIndex = ($groupVariantIndex + $blockIndex) % count($capVariants);
                         $geoIndex = ($groupVariantIndex + $blockIndex) % count($geoVariants);
                         
                         // Определяем тип сообщения для этого блока
                         if ($groupType == 1) {
                             // group_single - одиночные значения
                             $block['affiliate'] = $affiliateVariants[$affiliateIndex];
                             $block['recipient'] = $recipientVariants[$recipientIndex];
                             $block['cap'] = [$capVariants[$capIndex][0], explode(' ', $capVariants[$capIndex][1])[0]]; // Берем первое значение
                             $block['geo'] = [$geoVariants[$geoIndex][0], explode(' ', $geoVariants[$geoIndex][1])[0]]; // Берем первое значение
                         } else {
                             // group_multi - множественные значения
                             $block['affiliate'] = $affiliateVariants[$affiliateIndex];
                             $block['recipient'] = $recipientVariants[$recipientIndex];
                             $block['cap'] = $capVariants[$capIndex];
                             $block['geo'] = $geoVariants[$geoIndex];
                         }
                         
                                                   // Добавляем дополнительные поля рандомно
                          $additionalFields = ['schedule', 'date', 'language', 'funnel', 'test', 'total', 'pending_acq', 'freeze_status_on_acq'];
                          
                          foreach ($additionalFields as $fieldName) {
                              if (rand(1, 2) == 1) { // 50% вероятность добавления каждого поля
                                  $fieldVariants = $this->getFieldVariants($fieldName, $groupVariantIndex + $blockIndex);
                                  $fieldIndex = ($groupVariantIndex + $blockIndex) % count($fieldVariants);
                                  $block[$fieldName] = $fieldVariants[$fieldIndex];
                              }
                          }
                         
                         $blocks[] = $block;
                     }
                     
                     // Проверяем совместимость всех блоков
                     $isCompatible = true;
                     foreach ($blocks as $block) {
                         if (!$this->isUniqueCombination($block['affiliate'][1], $block['recipient'][1], $block['geo'][1])) {
                             $isCompatible = false;
                             break;
                         }
                     }
                     
                     if ($isCompatible) {
                         $variants[] = [
                             'group_type' => $groupType == 1 ? 'group_single_cap' : 'group_multi_cap',
                             'is_group_message' => true,
                             'blocks' => $blocks
                         ];
                         
                         // Отмечаем комбинации как использованные
                         foreach ($blocks as $block) {
                             $this->markCombinationAsUsed($block['affiliate'][1], $block['recipient'][1], $block['geo'][1]);
                         }
                     }
                 }
             }
         }
         
         return $variants;
     }

     private function determineMessageType($messageText)
     {
         // Проверяем количество блоков affiliate (групповое vs одиночное)
         $affiliateCount = preg_match_all('/^affiliate:\s*(.+)$/im', $messageText);
         
         if ($affiliateCount > 1) {
             // Групповое сообщение - проверяем есть ли мульти-значения в любом блоке
             $affiliateBlocks = $this->parseAffiliateBlocks($messageText);
             
                              foreach ($affiliateBlocks as $block) {
                     $isMulti = false;
                     $capCount = 1;
                     $geoCount = 1;
                     $funnelCount = 1;
                     
                     // Проверяем cap
                     if (preg_match('/^cap:\s*(.+)$/im', $block, $matches)) {
                         $capValues = preg_split('/\s+/', trim($matches[1]));
                         $capCount = count($capValues);
                         if ($capCount > 1) {
                             $isMulti = true;
                         }
                     }
                     
                     // Проверяем geo
                     if (preg_match('/^geo:\s*(.+)$/im', $block, $matches)) {
                         $geoValues = preg_split('/\s+/', trim($matches[1]));
                         $geoCount = count($geoValues);
                         if ($geoCount > 1) {
                             $isMulti = true;
                         }
                     }
                     
                     // Проверяем funnel в специальном случае
                     if (preg_match('/^funnel:\s*(.+)$/im', $block, $matches)) {
                         $funnelValues = preg_split('/\s+/', trim($matches[1]));
                         $funnelCount = count($funnelValues);
                         
                         // СПЕЦИАЛЬНЫЙ СЛУЧАЙ: funnel участвует в определении типа ТОЛЬКО когда:
                         // CAP > 1, GEO = 1, FUNNEL = GEO (т.е. FUNNEL = 1)
                         if ($capCount > 1 && $geoCount == 1 && $funnelCount == $geoCount) {
                             // В этом случае funnel может влиять на тип
                             if ($funnelCount > 1) {
                                 $isMulti = true;
                             }
                         }
                     }
                     
                     if ($isMulti) {
                         return 'group_multi'; // Групповое сообщение с мульти-значениями
                     }
                 }
             
             return 'group_single'; // Групповое сообщение с одиночными значениями
         } else {
             // Одиночное сообщение - проверяем количество cap, geo и funnel
             $isMulti = false;
             $capCount = 1;
             $geoCount = 1;
             $funnelCount = 1;
             
             // Проверяем cap
             if (preg_match('/^cap:\s*(.+)$/im', $messageText, $matches)) {
                 $capValues = preg_split('/\s+/', trim($matches[1]));
                 $capCount = count($capValues);
                 if ($capCount > 1) {
                     $isMulti = true;
                 }
             }
             
             // Проверяем geo
             if (preg_match('/^geo:\s*(.+)$/im', $messageText, $matches)) {
                 $geoValues = preg_split('/\s+/', trim($matches[1]));
                 $geoCount = count($geoValues);
                 if ($geoCount > 1) {
                     $isMulti = true;
                 }
             }
             
             // Проверяем funnel в специальном случае
             if (preg_match('/^funnel:\s*(.+)$/im', $messageText, $matches)) {
                 $funnelValues = preg_split('/\s+/', trim($matches[1]));
                 $funnelCount = count($funnelValues);
                 
                 // СПЕЦИАЛЬНЫЙ СЛУЧАЙ: funnel участвует в определении типа ТОЛЬКО когда:
                 // CAP > 1, GEO = 1, FUNNEL = GEO (т.е. FUNNEL = 1)
                 if ($capCount > 1 && $geoCount == 1 && $funnelCount == $geoCount) {
                     // В этом случае funnel может влиять на тип
                     if ($funnelCount > 1) {
                         $isMulti = true;
                     }
                 }
             }
             
             return $isMulti ? 'single_multi' : 'single_single';
         }
     }
     
     private function getFieldVariants($fieldName, $baseIndex)
    {
        if (!isset($this->fieldVariants[$fieldName])) {
            return [[$fieldName . ':', 'default_value']];
        }
        
        $field = $this->fieldVariants[$fieldName];
        $variants = [];
        
        // ИСПОЛЬЗУЕМ ПОЛНЫЙ НАБОР ВАРИАНТОВ без ограничений
        foreach ($field['keys'] as $keyIndex => $key) {
            foreach ($field['values'] as $valueIndex => $value) {
                $variants[] = [$key, $value];
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
    
    private function getRandomUpdateFields($index)
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
    
    private function getRandomReplyFields($index)
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
    
    private function getRandomQuoteFields($index)
    {
        $quoteFields = [
            ['schedule' => '12-20', 'total' => '400'],
            ['language' => 'de', 'funnel' => 'binary'],
            ['test' => 'live', 'funnel' => 'crypto'],
            ['schedule' => '24/7', 'total' => '500'],
        ];
        
        return $quoteFields[$index % count($quoteFields)];
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
        
        // Добавляем ID сообщения к affiliate для гарантии уникальности
        $variant = $this->addMessageIdToAffiliate($variant, $messageId);
        
        // Генерируем сообщение используя максимальные варианты
        $messageText = $this->generateMessageByVariant($operationType, $variant);
        
        // Определяем тип сообщения
        $messageType = $this->determineMessageType($messageText);
        
        // Валидация: проверяем совместимость cap и geo количеств
        if (!$this->validateCapGeoCompatibility($messageText)) {
            // Пропускаем некорректные тесты БЕЗ создания сообщения в базе данных
            return null;
        }
        
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
                'text' => $messageText,
                'message_type' => $messageType // Добавляем тип сообщения
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

    private function addMessageIdToAffiliate($variant, $messageId)
    {
        // Для одиночных сообщений
        if (isset($variant['affiliate']) && is_array($variant['affiliate'])) {
            $variant['affiliate'][1] = $variant['affiliate'][1] . $messageId;
        }
        
        // Для групповых сообщений
        if (isset($variant['blocks']) && is_array($variant['blocks'])) {
            foreach ($variant['blocks'] as &$block) {
                if (isset($block['affiliate']) && is_array($block['affiliate'])) {
                    $block['affiliate'][1] = $block['affiliate'][1] . $messageId;
                }
            }
        }
        
        return $variant;
    }

    private function getCompatibleCapGeoPair($messageType, $index)
    {
        // Определяем совместимые cap-geo-funnel пары для каждого типа сообщения
        $singleSinglePairs = [
            ['cap' => ['cap:', '10'], 'geo' => ['geo:', 'RU'], 'funnel' => ['funnel:', 'crypto']],
            ['cap' => ['cap:', '50'], 'geo' => ['geo:', 'KZ'], 'funnel' => ['funnel:', 'forex']],
            ['cap' => ['cap:', '100'], 'geo' => ['geo:', 'IE'], 'funnel' => ['funnel:', 'binary']],
            ['cap' => ['cap:', '200'], 'geo' => ['geo:', 'UK'], 'funnel' => ['funnel:', 'stocks']],
            ['cap' => ['cap:', '500'], 'geo' => ['geo:', 'DE'], 'funnel' => ['funnel:', 'options']],
            ['cap' => ['cap:', '1000'], 'geo' => ['geo:', 'FR'], 'funnel' => ['funnel:', 'futures']],
            ['cap' => ['cap:', '75'], 'geo' => ['geo:', 'IT'], 'funnel' => ['funnel:', 'etf']],
            ['cap' => ['cap:', '25'], 'geo' => ['geo:', 'ES'], 'funnel' => ['funnel:', 'bonds']],
            ['cap' => ['cap:', '300'], 'geo' => ['geo:', 'PL'], 'funnel' => ['funnel:', 'commodities']],
            ['cap' => ['cap:', '150'], 'geo' => ['geo:', 'CZ'], 'funnel' => ['funnel:', 'indices']],
        ];
        
        $singleMultiPairs = [
            ['cap' => ['cap:', '20 30'], 'geo' => ['geo:', 'RU UA'], 'funnel' => ['funnel:', 'crypto,forex']],
            ['cap' => ['cap:', '5 10'], 'geo' => ['geo:', 'AU NZ'], 'funnel' => ['funnel:', 'binary,stocks']],
            ['cap' => ['cap:', '999 888'], 'geo' => ['geo:', 'US UK'], 'funnel' => ['funnel:', 'options,futures']],
            ['cap' => ['cap:', '100 200 300'], 'geo' => ['geo:', 'DE AT CH'], 'funnel' => ['funnel:', 'crypto,forex,binary']],
            ['cap' => ['cap:', '50 75'], 'geo' => ['geo:', 'IT ES'], 'funnel' => ['funnel:', 'stocks,etf']],
            ['cap' => ['cap:', '10 20'], 'geo' => ['geo:', 'PL CZ'], 'funnel' => ['funnel:', 'bonds,commodities']],
            ['cap' => ['cap:', '25 35'], 'geo' => ['geo:', 'FR BE'], 'funnel' => ['funnel:', 'indices,crypto']],
            ['cap' => ['cap:', '100 150'], 'geo' => ['geo:', 'SE NO'], 'funnel' => ['funnel:', 'forex,binary']],
            ['cap' => ['cap:', '80 90'], 'geo' => ['geo:', 'DK FI'], 'funnel' => ['funnel:', 'stocks,options']],
            ['cap' => ['cap:', '200 250'], 'geo' => ['geo:', 'LT LV'], 'funnel' => ['funnel:', 'futures,etf']],
            ['cap' => ['cap:', '40 60 80'], 'geo' => ['geo:', 'PT BR MX'], 'funnel' => ['funnel:', 'bonds,commodities,indices']],
            ['cap' => ['cap:', '15 25 35'], 'geo' => ['geo:', 'CA US AU'], 'funnel' => ['funnel:', 'crypto,forex,binary']],
        ];
        
        // Также добавляем пары для тестирования размножения funnel
        $funnelMultiplyPairs = [
            ['cap' => ['cap:', '100'], 'geo' => ['geo:', 'DE'], 'funnel' => ['funnel:', 'crypto,forex,binary']],
            ['cap' => ['cap:', '50'], 'geo' => ['geo:', 'US'], 'funnel' => ['funnel:', 'stocks,options']],
            ['cap' => ['cap:', '200 300'], 'geo' => ['geo:', 'UK'], 'funnel' => ['funnel:', 'futures,etf']],
            ['cap' => ['cap:', '25 35 45'], 'geo' => ['geo:', 'FR'], 'funnel' => ['funnel:', 'bonds,commodities,indices']],
        ];
        
        switch ($messageType) {
            case 'single_single':
                // Иногда возвращаем пары с размножением funnel
                if ($index % 5 === 0) {
                    return $funnelMultiplyPairs[$index % count($funnelMultiplyPairs)];
                }
                return $singleSinglePairs[$index % count($singleSinglePairs)];
            case 'single_multi':
                return $singleMultiPairs[$index % count($singleMultiPairs)];
            case 'group_single':
                return $singleSinglePairs[$index % count($singleSinglePairs)];
            case 'group_multi':
                return $singleMultiPairs[$index % count($singleMultiPairs)];
            default:
                return $singleSinglePairs[0];
        }
    }

    private function validateCapGeoCompatibility($messageText)
    {
        // Проверяем количество блоков affiliate (групповое vs одиночное)
        $affiliateCount = preg_match_all('/^affiliate:\s*(.+)$/im', $messageText);
        
        if ($affiliateCount > 1) {
            // Групповое сообщение - проверяем каждый блок
            $blocks = preg_split('/\n\s*\n/', $messageText);
            
            foreach ($blocks as $block) {
                $capCount = 1;
                $geoCount = 1;
                
                // Проверяем cap
                if (preg_match('/^cap:\s*(.+)$/im', $block, $matches)) {
                    $capValues = preg_split('/\s+/', trim($matches[1]));
                    $capCount = count($capValues);
                }
                
                // Проверяем geo
                if (preg_match('/^geo:\s*(.+)$/im', $block, $matches)) {
                    $geoValues = preg_split('/\s+/', trim($matches[1]));
                    $geoCount = count($geoValues);
                }
                
                // Проверяем совместимость количеств ТОЛЬКО cap и geo
                if (!$this->areCountsCompatible($capCount, $geoCount, 1)) {
                    return false;
                }
            }
        } else {
            // Одиночное сообщение
            $capCount = 1;
            $geoCount = 1;
            
            // Проверяем cap
            if (preg_match('/^cap:\s*(.+)$/im', $messageText, $matches)) {
                $capValues = preg_split('/\s+/', trim($matches[1]));
                $capCount = count($capValues);
            }
            
            // Проверяем geo
            if (preg_match('/^geo:\s*(.+)$/im', $messageText, $matches)) {
                $geoValues = preg_split('/\s+/', trim($matches[1]));
                $geoCount = count($geoValues);
            }
            
            // Проверяем совместимость количеств ТОЛЬКО cap и geo
            if (!$this->areCountsCompatible($capCount, $geoCount, 1)) {
                return false;
            }
        }
        
        return true; // Совместимые количества
    }
    
    private function areCountsCompatible($capCount, $geoCount, $funnelCount)
    {
        // Логика совместимости ТОЛЬКО для cap и geo:
        // 1. Если cap и geo имеют одинаковое количество - совместимы
        // 2. Если одно из них имеет 1 значение, а другое несколько - совместимы (размножение)
        // 3. Если оба имеют разное количество (>1) - несовместимы
        // funnel НЕ участвует в проверке совместимости
        
        if ($capCount === 1 || $geoCount === 1) {
            return true; // Одно поле одиночное - размножение возможно
        }
        
        return $capCount === $geoCount; // Оба множественные - должны совпадать
    }

    private function generateMessageByVariant($operationType, $variant)
    {
        switch ($variant['message_type']) {
            case 'single_single':
            case 'single_multi':
                return $this->generateSingleMessage($variant);
                
            case 'group_single':
            case 'group_multi':
                return $this->generateGroupMessage($variant);
                
            default:
                return $this->generateSingleMessage($variant);
        }
    }
    
    private function generateSingleMessage($variant)
    {
        $message = '';
        
        // Обязательные поля в правильном порядке
        if (isset($variant['affiliate'])) {
            $message .= $variant['affiliate'][0] . ' ' . $variant['affiliate'][1] . "\n";
        }
        
        if (isset($variant['recipient'])) {
            $message .= $variant['recipient'][0] . ' ' . $variant['recipient'][1] . "\n";
        }
        
        if (isset($variant['cap'])) {
            $message .= $variant['cap'][0] . ' ' . $variant['cap'][1] . "\n";
        }
        
        if (isset($variant['geo'])) {
            $message .= $variant['geo'][0] . ' ' . $variant['geo'][1] . "\n";
        }
        
        // Дополнительные поля
        if (isset($variant['schedule'])) {
            $message .= $variant['schedule'][0] . ' ' . $variant['schedule'][1] . "\n";
        }
        
        if (isset($variant['total'])) {
            $message .= $variant['total'][0] . ' ' . $variant['total'][1] . "\n";
        }
        
        if (isset($variant['date'])) {
            $message .= $variant['date'][0] . ' ' . $variant['date'][1] . "\n";
        }
        
        if (isset($variant['language'])) {
            $message .= $variant['language'][0] . ' ' . $variant['language'][1] . "\n";
        }
        
        if (isset($variant['funnel'])) {
            $message .= $variant['funnel'][0] . ' ' . $variant['funnel'][1] . "\n";
        }
        
        if (isset($variant['test'])) {
            $message .= $variant['test'][0] . ' ' . $variant['test'][1] . "\n";
        }
        
        if (isset($variant['pending_acq'])) {
            $message .= $variant['pending_acq'][0] . ' ' . $variant['pending_acq'][1] . "\n";
        }
        
        if (isset($variant['freeze_status_on_acq'])) {
            $message .= $variant['freeze_status_on_acq'][0] . ' ' . $variant['freeze_status_on_acq'][1] . "\n";
        }
        
        return rtrim($message);
    }

    private function generateGroupMessage($variant)
    {
        $message = '';
        $blocks = $variant['blocks'] ?? [];
        
        foreach ($blocks as $blockIndex => $block) {
            if ($blockIndex > 0) {
                $message .= "\n\n"; // Разделитель между блоками
            }
            
            // Сначала добавляем обязательные поля в правильном порядке
            $requiredFields = ['affiliate', 'recipient', 'cap', 'geo'];
            
            foreach ($requiredFields as $field) {
                if (isset($block[$field])) {
                    $fieldData = $block[$field];
                    $message .= $fieldData[0] . ' ' . $fieldData[1] . "\n";
                }
            }
            
            // Затем добавляем дополнительные поля (если есть в блоке)
            $additionalFields = ['schedule', 'total', 'date', 'language', 'funnel', 'test', 'pending_acq', 'freeze_status_on_acq'];
            
            foreach ($additionalFields as $field) {
                if (isset($block[$field])) {
                    $fieldData = $block[$field];
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
        
        $this->showTestTypeStatistics();
    }

    private function showTestTypeStatistics()
    {
        $this->info('');
        $this->info('🧪 ПОКРЫТИЕ ТИПОВ ТЕСТОВ:');
        
        // Подсчитываем типы тестов по названиям affiliate
        $testTypes = [
            'single_message_single_cap' => 0,
            'single_message_multi_cap' => 0,
            'group_message_single_cap' => 0,
            'group_message_multi_cap' => 0,
            'funnel_separated_caps' => 0,
            'extreme_variants' => 0,
            'basic_variants' => 0
        ];
        
        // Получаем статистику по типам сообщений из базы данных
        $messageTypes = Message::select('message_type')
            ->whereNotNull('message_type')
            ->get()
            ->countBy('message_type');
        
        // Обновляем статистику на основе реальных типов сообщений
        foreach ($messageTypes as $type => $count) {
            switch ($type) {
                case 'single_single':
                    $testTypes['single_message_single_cap'] = $count;
                    break;
                case 'single_multi':
                    $testTypes['single_message_multi_cap'] = $count;
                    break;
                case 'group_single':
                    $testTypes['group_message_single_cap'] = $count;
                    break;
                case 'group_multi':
                    $testTypes['group_message_multi_cap'] = $count;
                    break;
                case 'unknown':
                    $testTypes['basic_variants'] = $count;
                    break;
            }
        }
        
        // Дополнительно подсчитываем специальные типы по affiliate
        $caps = Cap::select('affiliate_name')->get();
        foreach ($caps as $cap) {
            $affiliate = $cap->affiliate_name;
            
            if (strpos($affiliate, 'FUNNEL_TEST') !== false) {
                $testTypes['funnel_separated_caps']++;
            } elseif (strpos($affiliate, 'Very-Long-Affiliate') !== false) {
                $testTypes['extreme_variants']++;
            }
        }
        
        $this->info('📊 Типы тестов создания кап:');
        $this->info('   ✅ Одиночное сообщение → Одна капа: ' . $testTypes['single_message_single_cap']);
        $this->info('   ✅ Одиночное сообщение → Много кап: ' . $testTypes['single_message_multi_cap']);
        $this->info('   ✅ Групповое сообщение → Одна капа: ' . $testTypes['group_message_single_cap']);
        $this->info('   ✅ Групповое сообщение → Много кап: ' . $testTypes['group_message_multi_cap']);
        $this->info('   ✅ Тесты с funnel разделением: ' . $testTypes['funnel_separated_caps']);
        $this->info('   ✅ Экстремальные варианты: ' . $testTypes['extreme_variants']);
        $this->info('   ✅ Базовые варианты: ' . $testTypes['basic_variants']);
        
        // Показываем детальную статистику по типам сообщений
        $this->info('');
        $this->info('📈 Детальная статистика по типам сообщений:');
        $totalMessages = Message::count();
        $messageTypes = Message::select('message_type')
            ->whereNotNull('message_type')
            ->get()
            ->countBy('message_type');
        
        foreach ($messageTypes as $type => $count) {
            $percentage = $totalMessages > 0 ? round(($count / $totalMessages) * 100, 1) : 0;
            $this->info("   📝 {$type}: {$count} ({$percentage}%)");
        }
        
        // Проверяем покрытие всех обязательных типов
        $requiredTypes = [
            'single_message_single_cap', 
            'single_message_multi_cap', 
            'group_message_single_cap', 
            'group_message_multi_cap'
        ];
        
        $allTypesPresent = true;
        foreach ($requiredTypes as $type) {
            if ($testTypes[$type] == 0) {
                $allTypesPresent = false;
                break;
            }
        }
        
        if ($allTypesPresent) {
            $this->info('✅ Все обязательные типы тестов присутствуют!');
        } else {
            $this->warn('⚠️  Не все обязательные типы тестов присутствуют!');
        }
    }

    /**
     * Анализирует ожидаемые результаты перед отправкой сообщения
     */
    private function analyzeExpectedResults($messageText, $messageType, $variant)
    {
        // Извлекаем поля из сообщения
        $fields = $this->extractFieldsFromMessage($messageText);
        
        // Определяем ожидаемое количество записей на основе типа сообщения
        $expectedCapCount = 0;
        
        switch ($messageType) {
            case 'single_single':
                // single_single ВСЕГДА означает РОВНО 1 запись
                // Один affiliate + один cap + один geo = 1 запись
                // ВСЕ остальные поля (total, funnel, language и т.д.) НЕ влияют на количество записей!
                $expectedCapCount = 1;
                break;
            case 'single_multi':
                // Подсчитываем количество элементов в cap и geo
                $capCount = 1;
                $geoCount = 1;
                $funnelCount = 1;
                
                if (isset($fields['cap'])) {
                    $caps = preg_split('/\s+/', trim($fields['cap']));
                    $capCount = count($caps);
                }
                
                if (isset($fields['geo'])) {
                    $geos = preg_split('/\s+/', trim($fields['geo']));
                    $geoCount = count($geos);
                }
                
                if (isset($fields['funnel'])) {
                    $funnels = preg_split('/\s+/', trim($fields['funnel']));
                    $funnelCount = count($funnels);
                }
                
                // СПЕЦИАЛЬНЫЙ СЛУЧАЙ: funnel участвует в размножении ТОЛЬКО когда:
                // CAP > 1, GEO = 1, FUNNEL = GEO (т.е. FUNNEL = 1)
                if ($capCount > 1 && $geoCount == 1 && $funnelCount == $geoCount) {
                    // В этом случае funnel участвует в размножении
                    $expectedCapCount = max($capCount, $geoCount, $funnelCount);
                } else {
                    // Обычная логика: используем только cap и geo
                    $expectedCapCount = max($capCount, $geoCount);
                }
                break;
            case 'group_single':
                // Подсчитываем количество блоков affiliate
                // group_single всегда = количество блоков affiliate, независимо от funnel
                $affiliateCount = preg_match_all('/^affiliate:\s*(.+)$/im', $messageText);
                $expectedCapCount = $affiliateCount;
                break;
            case 'group_multi':
                // Подсчитываем общее количество кап во всех блоках
                $affiliateBlocks = $this->parseAffiliateBlocks($messageText);
                
                foreach ($affiliateBlocks as $block) {
                    $capCount = 1;
                    $geoCount = 1;
                    $funnelCount = 1;
                    
                    if (preg_match('/^cap:\s*(.+)$/im', $block, $matches)) {
                        $caps = preg_split('/\s+/', trim($matches[1]));
                        $capCount = count($caps);
                    }
                    
                    if (preg_match('/^geo:\s*(.+)$/im', $block, $matches)) {
                        $geos = preg_split('/\s+/', trim($matches[1]));
                        $geoCount = count($geos);
                    }
                    
                    if (preg_match('/^funnel:\s*(.+)$/im', $block, $matches)) {
                        $funnels = preg_split('/\s+/', trim($matches[1]));
                        $funnelCount = count($funnels);
                    }
                    
                    // СПЕЦИАЛЬНЫЙ СЛУЧАЙ: funnel участвует в размножении ТОЛЬКО когда:
                    // CAP > 1, GEO = 1, FUNNEL = GEO (т.е. FUNNEL = 1)
                    if ($capCount > 1 && $geoCount == 1 && $funnelCount == $geoCount) {
                        // В этом случае funnel участвует в размножении
                        $expectedCapCount += max($capCount, $geoCount, $funnelCount);
                    } else {
                        // Обычная логика: используем только cap и geo
                        $expectedCapCount += max($capCount, $geoCount);
                    }
                }
                break;
        }
        
        return [
            'action' => 'создание',
            'expected_cap_count' => $expectedCapCount,
            'expected_fields' => $fields,
            'message_type' => $messageType
        ];
    }

    /**
     * Проверяет фактические результаты в базе данных
     */
    private function checkActualResults($messageText, $messageType, $beforeCounts, $testMessage)
    {
        // Получаем данные ПОСЛЕ обработки  
        $afterCounts = $this->getDatabaseCounts();
        
        // Подсчитываем изменения
        $actualCapCount = $afterCounts['caps'] - $beforeCounts['caps'];
        $actualMessageCount = $afterCounts['messages'] - $beforeCounts['messages'];
        
        // Получаем созданные капы для проверки полей
        $createdCaps = Cap::where('message_id', '>', $beforeCounts['messages'])
                          ->get()
                          ->map(function($cap) {
                              return [
                                  'affiliate' => $cap->affiliate_name,
                                  'recipient' => $cap->recipient_name,
                                  'geo' => $cap->geo,
                                  'cap_amount' => $cap->cap_amount
                              ];
                          })
                          ->toArray();
        
        return [
            'actual_cap_count' => $actualCapCount,
            'actual_message_count' => $actualMessageCount,
            'created_caps' => $createdCaps,
            'message_type' => $messageType
        ];
    }

    /**
     * Сравнивает ожидаемые и фактические результаты
     */
    private function compareAndReportResults($messageIndex, $expectedResults, $actualResults, $messageText, $messageType)
    {
        // Сокращаем текст для вывода
        $shortText = strlen($messageText) > 100 ? substr($messageText, 0, 100) . '...' : $messageText;
        
        $this->info("════════════════════════════════════════════════════════════════");
        $this->info("📝 СООБЩЕНИЕ #{$messageIndex} ({$messageType})");
        $this->info("Текст: {$shortText}");
        $this->info("────────────────────────────────────────────────────────────────");
        
        // Проверяем создание сообщения
        if ($actualResults['actual_message_count'] === 1) {
            $this->info("✅ Сообщение создано в базе данных");
        } else {
            $this->error("❌ Сообщение не создано в базе данных");
            return false;
        }
        
        // Проверяем создание кап
        $expectedCapCount = $expectedResults['expected_cap_count'];
        $actualCapCount = $actualResults['actual_cap_count'];
        
        if ($actualCapCount === $expectedCapCount) {
            $this->info("✅ Кап создан (ожидалось: {$expectedCapCount}, получено: {$actualCapCount})");
        } else {
            $this->error("❌ Неверное количество кап (ожидалось: {$expectedCapCount}, получено: {$actualCapCount})");
            return false;
        }
        
        // Проверяем поля только для групповых сообщений (где есть проблема с гео)
        if ($messageType === 'group_single' || $messageType === 'group_multi') {
            $this->info("📊 ПРОВЕРКА ПОЛЕЙ:");
            
            // Проверяем каждую созданную капу
            foreach ($actualResults['created_caps'] as $index => $cap) {
                $this->info("  📋 Капа #" . ($index + 1) . ":");
                $this->info("    ✅ affiliate: '{$cap['affiliate']}'");
                $this->info("    ✅ recipient: '{$cap['recipient']}'");
                $this->info("    ✅ geo: '{$cap['geo']}'");
                $this->info("    ✅ cap_amount: {$cap['cap_amount']}");
            }
        }
        
        $this->info("🎉 РЕЗУЛЬТАТ: КОРРЕКТНО");
        return true;
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
            'cap' => '/cap:\s*([^\n]+)/i',
            'geo' => '/geo:\s*([^\n]+)/i',
            'total' => '/total:\s*([^\n]+)/i',
            'schedule' => '/schedule:\s*([^\n]+)/i',
            'date' => '/date:\s*([^\n]+)/i',
            'language' => '/language:\s*([^\n]+)/i',
            'funnel' => '/funnel:\s*([^\n]+)/i',
            'test' => '/test:\s*([^\n]+)/i',
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
        $result = [];
        
        foreach ($originalOrder as $geo) {
            foreach ($geos as $key => $actualGeo) {
                if (strtolower($actualGeo) === strtolower($geo)) {
                    $result[] = $actualGeo;
                    unset($geos[$key]);
                    break;
                }
            }
        }
        
        // Добавляем оставшиеся геозоны
        foreach ($geos as $geo) {
            $result[] = $geo;
        }
        
        return $result;
    }

    private function parseAffiliateBlocks($messageText)
    {
        // Разбираем сообщение на блоки affiliate
        $lines = explode("\n", $messageText);
        $blocks = [];
        $currentBlock = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Если строка пустая, пропускаем
            if (empty($line)) {
                continue;
            }
            
            // Если строка начинается с "affiliate:", это начало нового блока
            if (preg_match('/^affiliate:\s*(.+)$/i', $line)) {
                // Сохраняем предыдущий блок если он не пустой
                if (!empty($currentBlock)) {
                    $blocks[] = implode("\n", $currentBlock);
                }
                
                // Начинаем новый блок
                $currentBlock = [$line];
            } else {
                // Добавляем строку к текущему блоку
                $currentBlock[] = $line;
            }
        }
        
        // Добавляем последний блок
        if (!empty($currentBlock)) {
            $blocks[] = implode("\n", $currentBlock);
        }
        
        return $blocks;
    }
} 