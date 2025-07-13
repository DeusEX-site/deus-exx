<?php

require_once 'vendor/autoload.php';
require_once 'DynamicCapTestGenerator.php';

use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

/**
 * Тестовый движок для динамических тестов системы кап
 */
class DynamicCapTestEngine
{
    private $generator;
    private $capAnalysisService;
    private $testResults;
    private $testErrors;
    private $testChat;
    private $messageCounter;
    private $verbose;

    public function __construct(bool $verbose = true)
    {
        $this->generator = new DynamicCapTestGenerator();
        $this->capAnalysisService = new CapAnalysisService();
        $this->testResults = [];
        $this->testErrors = [];
        $this->messageCounter = 1000;
        $this->verbose = $verbose;
        
        // Инициализация Laravel если еще не инициализирована
        if (!app()) {
            $app = require_once 'bootstrap/app.php';
            $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        }
        
        $this->setupTestChat();
    }

    /**
     * Настройка тестового чата
     */
    private function setupTestChat(): void
    {
        $this->log("📋 Настройка тестового чата...");
        
        $this->testChat = Chat::updateOrCreate(
            ['chat_id' => -99999999],
            [
                'type' => 'supergroup',
                'title' => 'Dynamic Cap Test Chat',
                'is_active' => true,
                'display_order' => 1,
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
        
        $this->log("✅ Тестовый чат создан/найден с ID: {$this->testChat->id} (chat_id: {$this->testChat->chat_id})");
        
        // Очищаем старые тестовые данные
        $this->log("🧹 Очистка старых тестовых данных...");
        
        $oldMessages = Message::where('chat_id', $this->testChat->id)->count();
        $oldCaps = Cap::whereIn('message_id', function($query) {
            $query->select('id')->from('messages')
                  ->where('chat_id', $this->testChat->id);
        })->count();
        
        $this->log("📊 Найдено для удаления: {$oldMessages} сообщений, {$oldCaps} кап");
        
        Cap::whereIn('message_id', function($query) {
            $query->select('id')->from('messages')
                  ->where('chat_id', $this->testChat->id);
        })->delete();
        
        Message::where('chat_id', $this->testChat->id)->delete();
        
        $this->log("✅ Тестовый чат настроен и очищен (ID: {$this->testChat->id})");
    }

    /**
     * Логирование
     */
    private function log(string $message): void
    {
        if ($this->verbose) {
            echo $message . "\n";
        }
    }

    /**
     * Создание тестового сообщения
     */
    private function createTestMessage(string $content, ?int $replyToMessageId = null, ?string $quotedText = null): Message
    {
        $this->log("💬 Создание тестового сообщения...");
        $this->log("📝 Содержимое: " . substr($content, 0, 200) . "...");
        
        $message = Message::create([
            'chat_id' => $this->testChat->id,
            'telegram_message_id' => $this->messageCounter++,
            'user' => 'DynamicTestUser',
            'display_name' => 'Dynamic Test User',
            'message' => $content,
            'reply_to_message_id' => $replyToMessageId,
            'quoted_text' => $quotedText,
            'telegram_user_id' => 999999,
            'telegram_username' => 'dynamictestuser',
            'telegram_first_name' => 'Dynamic',
            'telegram_last_name' => 'Test',
            'telegram_date' => now(),
            'message_type' => 'text',
            'is_outgoing' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $this->log("✅ Сообщение создано с ID: {$message->id}");
        
        return $message;
    }

    /**
     * Выполнение анализа сообщения
     */
    private function analyzeMessage(Message $message): array
    {
        try {
            $this->log("🔍 Анализ сообщения ID: {$message->id}");
            $this->log("📝 Текст сообщения: " . substr($message->message, 0, 100) . "...");
            
            // Подсчитываем записи до анализа
            $capsBefore = Cap::where('message_id', $message->id)->count();
            $this->log("📊 Кап до анализа: {$capsBefore}");
            
            $result = $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $message->message);
            
            // Подсчитываем записи после анализа
            $capsAfter = Cap::where('message_id', $message->id)->count();
            $capsHistoryAfter = CapHistory::whereIn('cap_id', function($query) use ($message) {
                $query->select('id')->from('caps')->where('message_id', $message->id);
            })->count();
            
            $this->log("📊 Кап после анализа: {$capsAfter}");
            $this->log("📊 История кап: {$capsHistoryAfter}");
            $this->log("✅ Результат анализа: " . json_encode($result));
            
            return [
                'success' => true,
                'result' => $result,
                'error' => null,
                'caps_created' => $capsAfter,
                'caps_history_created' => $capsHistoryAfter
            ];
        } catch (Exception $e) {
            $this->log("❌ Ошибка анализа: " . $e->getMessage());
            $this->log("📍 Стек: " . $e->getTraceAsString());
            return [
                'success' => false,
                'result' => null,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Проверка результатов создания кап
     */
    private function validateCapCreation(array $expectedCaps, array $analysisResult): array
    {
        $errors = [];
        
        $this->log("🔍 Валидация создания кап...");
        $this->log("📊 Ожидаемые капы: " . json_encode($expectedCaps));
        $this->log("📊 Результат анализа: " . json_encode($analysisResult));
        
        // Проверяем количество созданных кап
        $expectedCount = count($expectedCaps);
        $actualCount = $analysisResult['cap_entries_count'] ?? 0;
        
        $this->log("📈 Ожидалось кап: {$expectedCount}, создано: {$actualCount}");
        
        if ($expectedCount !== $actualCount) {
            $errors[] = "Ожидалось {$expectedCount} кап, создано {$actualCount}";
        }
        
        // Подсчитываем общее количество кап в БД
        $totalCapsInDb = Cap::count();
        $this->log("📈 Всего кап в БД: {$totalCapsInDb}");
        
        // Проверяем создание записей в БД
        foreach ($expectedCaps as $index => $expectedCap) {
            $this->log("🔍 Поиск капы #{$index}: {$expectedCap['affiliate']} -> {$expectedCap['recipient']} ({$expectedCap['geo']})");
            
            $foundCap = Cap::where('affiliate_name', strtolower($expectedCap['affiliate']))
                          ->where('recipient_name', strtolower($expectedCap['recipient']))
                          ->where('geo', strtolower($expectedCap['geo']))
                          ->first();
            
            if (!$foundCap) {
                $this->log("❌ Капа не найдена в БД");
                $errors[] = "Не найдена капа: {$expectedCap['affiliate']} -> {$expectedCap['recipient']} ({$expectedCap['geo']})";
                
                // Показываем какие капы есть в БД для отладки
                $allCaps = Cap::select('affiliate_name', 'recipient_name', 'geos', 'cap_amounts')->get();
                $this->log("📋 Все капы в БД: " . json_encode($allCaps->toArray()));
            } else {
                $this->log("✅ Капа найдена в БД с ID: {$foundCap->id}");
                
                // Проверяем значения полей
                foreach ($expectedCap as $field => $expectedValue) {
                    if ($field === 'geo') continue; // Уже проверено выше
                    
                    $actualValue = $foundCap->$field;
                    
                    if ($field === 'cap_amounts' && is_array($actualValue)) {
                        $actualValue = $actualValue[0] ?? null;
                    }
                    
                    $this->log("🔍 Проверка поля {$field}: ожидалось '{$expectedValue}', получено '{$actualValue}'");
                    
                    // Специальная обработка для числовых полей
                    if ($field === 'cap_amounts') {
                        if (intval($actualValue) !== intval($expectedValue)) {
                            $errors[] = "Поле {$field}: ожидалось '{$expectedValue}', получено '{$actualValue}'";
                        }
                    } else {
                        if (strtolower($actualValue) !== strtolower($expectedValue)) {
                            $errors[] = "Поле {$field}: ожидалось '{$expectedValue}', получено '{$actualValue}'";
                        }
                    }
                }
            }
        }
        
        $this->log($errors ? "❌ Найдены ошибки валидации: " . json_encode($errors) : "✅ Валидация прошла успешно");
        
        return $errors;
    }

    /**
     * Проверка результатов обновления кап
     */
    private function validateCapUpdate(array $expectedUpdates, array $analysisResult): array
    {
        $errors = [];
        
        // Проверяем количество обновленных кап
        $expectedCount = count($expectedUpdates);
        $actualCount = $analysisResult['updated_entries_count'] ?? 0;
        
        if ($expectedCount !== $actualCount) {
            $errors[] = "Ожидалось обновление {$expectedCount} кап, обновлено {$actualCount}";
        }
        
        // Проверяем обновления в БД
        foreach ($expectedUpdates as $expectedUpdate) {
            $foundCap = Cap::where('affiliate_name', strtolower($expectedUpdate['affiliate']))
                          ->where('recipient_name', strtolower($expectedUpdate['recipient']))
                          ->where('geo', strtolower($expectedUpdate['geo']))
                          ->first();
            
            if (!$foundCap) {
                $errors[] = "Не найдена капа для обновления: {$expectedUpdate['affiliate']} -> {$expectedUpdate['recipient']} ({$expectedUpdate['geo']})";
                continue;
            }
            
            // Проверяем обновленные поля
            foreach ($expectedUpdate['updates'] as $field => $expectedValue) {
                $actualValue = $foundCap->$field;
                
                if ($field === 'cap_amounts' && is_array($actualValue)) {
                    $actualValue = $actualValue[0] ?? null;
                }
                
                // Специальная обработка для числовых полей
                if ($field === 'cap_amounts') {
                    if (intval($actualValue) !== intval($expectedValue)) {
                        $errors[] = "Обновление поля {$field}: ожидалось '{$expectedValue}', получено '{$actualValue}'";
                    }
                } else {
                    if (strtolower($actualValue) !== strtolower($expectedValue)) {
                        $errors[] = "Обновление поля {$field}: ожидалось '{$expectedValue}', получено '{$actualValue}'";
                    }
                }
            }
        }
        
        return $errors;
    }

    /**
     * Проверка команд статуса
     */
    private function validateStatusCommand(array $expectedStatuses, array $analysisResult): array
    {
        $errors = [];
        
        foreach ($expectedStatuses as $expectedStatus) {
            $foundCap = Cap::where('affiliate_name', strtolower($expectedStatus['affiliate']))
                          ->where('recipient_name', strtolower($expectedStatus['recipient']))
                          ->where('geo', strtolower($expectedStatus['geo']))
                          ->first();
            
            if (!$foundCap) {
                $errors[] = "Не найдена капа для изменения статуса: {$expectedStatus['affiliate']} -> {$expectedStatus['recipient']} ({$expectedStatus['geo']})";
                continue;
            }
            
            $expectedStatusValue = strtoupper($expectedStatus['status']);
            $actualStatusValue = strtoupper($foundCap->status);
            
            if ($expectedStatusValue !== $actualStatusValue) {
                $errors[] = "Статус: ожидался '{$expectedStatusValue}', получен '{$actualStatusValue}'";
            }
        }
        
        return $errors;
    }

    /**
     * Тестирование создания одной капы
     */
    public function testSingleCapCreation(array $capData): array
    {
        $this->log("🔍 Тестирование создания одной капы...");
        $this->log("📊 Входные данные: " . json_encode($capData));
        
        $messageText = $this->generator->generateSingleCapMessage($capData);
        $this->log("📝 Сгенерированное сообщение:");
        $this->log($messageText);
        
        $message = $this->createTestMessage($messageText);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!is_array($analysisResult) || !($analysisResult['success'] ?? false)) {
            $this->log("❌ Анализ завершился неудачно");
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . ($analysisResult['error'] ?? 'Неизвестная ошибка'),
                'message' => $messageText
            ];
        }
        
        $expectedCaps = [
            [
                'affiliate' => $capData['affiliate'] ?? '',
                'recipient' => $capData['recipient'] ?? '',
                'geo' => $capData['geo'] ?? '',
                'cap_amounts' => intval($capData['cap'] ?? '10')
            ]
        ];
        
        $this->log("🎯 Ожидаемые капы для валидации: " . json_encode($expectedCaps));
        
        $errors = $this->validateCapCreation($expectedCaps, $analysisResult['result'] ?? []);
        
        $result = [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result'] ?? [],
            'caps_created_in_db' => $analysisResult['caps_created'] ?? 0,
            'caps_history_created' => $analysisResult['caps_history_created'] ?? 0
        ];
        
        $this->log($result['success'] ? "✅ Тест прошел успешно" : "❌ Тест завершился неудачно");
        
        return $result;
    }

    /**
     * Тестирование создания нескольких кап
     */
    public function testMultiCapCreation(array $baseData, array $capValues, array $geoValues): array
    {
        $this->log("🔍 Тестирование создания нескольких кап...");
        
        if (!$this->generator->validateCapGeoFunnelCombination($capValues, $geoValues)) {
            return [
                'success' => false,
                'error' => "Неверная комбинация cap и geo/funnel",
                'message' => 'N/A'
            ];
        }
        
        $messageText = $this->generator->generateMultiCapMessage($baseData, $capValues, $geoValues);
        $message = $this->createTestMessage($messageText);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!is_array($analysisResult) || !($analysisResult['success'] ?? false)) {
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . ($analysisResult['error'] ?? 'Неизвестная ошибка'),
                'message' => $messageText
            ];
        }
        
        // Формируем ожидаемые капы
        $expectedCaps = [];
        foreach ($geoValues as $index => $geo) {
            $expectedCaps[] = [
                'affiliate' => $baseData['affiliate'] ?? '',
                'recipient' => $baseData['recipient'] ?? '',
                'geo' => $geo ?? '',
                'cap_amounts' => intval(count($capValues) === 1 ? ($capValues[0] ?? '10') : ($capValues[$index] ?? '10'))
            ];
        }
        
        $errors = $this->validateCapCreation($expectedCaps, $analysisResult['result'] ?? []);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result'] ?? []
        ];
    }

    /**
     * Тестирование группового создания кап
     */
    public function testGroupCapCreation(array $blocks): array
    {
        $this->log("🔍 Тестирование группового создания кап...");
        
        $messageText = $this->generator->generateGroupMessage($blocks);
        $message = $this->createTestMessage($messageText);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!$analysisResult['success']) {
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . $analysisResult['error'],
                'message' => $messageText
            ];
        }
        
        // Формируем ожидаемые капы из всех блоков
        $expectedCaps = [];
        foreach ($blocks as $block) {
            $expectedCaps[] = [
                'affiliate' => $block['affiliate'],
                'recipient' => $block['recipient'],
                'geo' => $block['geo'],
                'cap_amounts' => intval($block['cap'] ?? '10')
            ];
        }
        
        $errors = $this->validateCapCreation($expectedCaps, $analysisResult['result']);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result']
        ];
    }

    /**
     * Тестирование обновления капы
     */
    public function testCapUpdate(array $identifierFields, array $updateFields, ?int $replyToMessageId = null): array
    {
        $this->log("🔍 Тестирование обновления капы...");
        
        $messageText = $this->generator->generateUpdateMessage($identifierFields, $updateFields);
        $message = $this->createTestMessage($messageText, $replyToMessageId);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!$analysisResult['success']) {
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . $analysisResult['error'],
                'message' => $messageText
            ];
        }
        
        $expectedUpdates = [
            [
                'affiliate' => $identifierFields['affiliate'],
                'recipient' => $identifierFields['recipient'],
                'geo' => $identifierFields['geo'],
                'updates' => $updateFields
            ]
        ];
        
        $errors = $this->validateCapUpdate($expectedUpdates, $analysisResult['result']);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result']
        ];
    }

    /**
     * Тестирование команды статуса
     */
    public function testStatusCommand(array $identifierFields, string $command): array
    {
        $this->log("🔍 Тестирование команды статуса: {$command}...");
        
        $messageText = $this->generator->generateStatusCommand($identifierFields, $command);
        $message = $this->createTestMessage($messageText);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!$analysisResult['success']) {
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . $analysisResult['error'],
                'message' => $messageText
            ];
        }
        
        $expectedStatuses = [
            [
                'affiliate' => $identifierFields['affiliate'],
                'recipient' => $identifierFields['recipient'],
                'geo' => $identifierFields['geo'],
                'status' => $command
            ]
        ];
        
        $errors = $this->validateStatusCommand($expectedStatuses, $analysisResult['result']);
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result']
        ];
    }

    /**
     * Тестирование сброса полей
     */
    public function testFieldReset(array $identifierFields, array $resetFields): array
    {
        $this->log("🔍 Тестирование сброса полей...");
        
        $messageText = $this->generator->generateResetMessage($identifierFields, $resetFields);
        $message = $this->createTestMessage($messageText);
        
        $analysisResult = $this->analyzeMessage($message);
        
        if (!$analysisResult['success']) {
            return [
                'success' => false,
                'error' => "Ошибка анализа: " . $analysisResult['error'],
                'message' => $messageText
            ];
        }
        
        // Проверяем, что поля сброшены до значений по умолчанию
        $foundCap = Cap::where('affiliate_name', strtolower($identifierFields['affiliate']))
                      ->where('recipient_name', strtolower($identifierFields['recipient']))
                      ->where('geo', strtolower($identifierFields['geo']))
                      ->first();
        
        $errors = [];
        if (!$foundCap) {
            $errors[] = "Не найдена капа для сброса полей";
        } else {
            $defaultValues = $this->generator->getDefaultValues();
            
            foreach ($resetFields as $field) {
                $expectedValue = $defaultValues[$field] ?? null;
                $actualValue = $foundCap->$field;
                
                if ($expectedValue !== $actualValue) {
                    $errors[] = "Поле {$field} не сброшено: ожидалось '{$expectedValue}', получено '{$actualValue}'";
                }
            }
        }
        
        return [
            'success' => empty($errors),
            'errors' => $errors,
            'message' => $messageText,
            'analysis_result' => $analysisResult['result']
        ];
    }

    /**
     * Получение результатов тестирования
     */
    public function getTestResults(): array
    {
        return $this->testResults;
    }

    /**
     * Получение ошибок тестирования
     */
    public function getTestErrors(): array
    {
        return $this->testErrors;
    }

    /**
     * Добавление результата теста
     */
    public function addTestResult(string $testName, array $result): void
    {
        $this->testResults[$testName] = $result;
        
        if (!$result['success']) {
            $this->testErrors[$testName] = $result;
        }
    }

    /**
     * Очистка результатов тестирования
     */
    public function clearResults(): void
    {
        $this->testResults = [];
        $this->testErrors = [];
    }

    /**
     * Очистка тестовых данных
     */
    public function cleanup(): void
    {
        // Удаляем историю кап, связанную с тестовыми капами
        CapHistory::whereIn('cap_id', function($query) {
            $query->select('id')->from('caps')
                  ->whereIn('message_id', function($subQuery) {
                      $subQuery->select('id')->from('messages')
                               ->where('chat_id', $this->testChat->id);
                  });
        })->delete();
        
        // Удаляем тестовые капы
        Cap::whereIn('message_id', function($query) {
            $query->select('id')->from('messages')
                  ->where('chat_id', $this->testChat->id);
        })->delete();
        
        // Удаляем тестовые сообщения
        Message::where('chat_id', $this->testChat->id)->delete();
        
        $this->log("🧹 Тестовые данные очищены");
    }
} 