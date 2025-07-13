<?php

require_once 'vendor/autoload.php';
require_once 'DynamicCapTestGenerator.php';
require_once 'DynamicCapTestEngine.php';
require_once 'DynamicCapCombinationGenerator.php';
require_once 'DynamicCapReportGenerator.php';

/**
 * Главный запускающий файл для системы динамических тестов кап
 */
class DynamicCapTestRunner
{
    private $generator;
    private $engine;
    private $combinations;
    private $reporter;
    private $config;
    private $currentCapData;

    public function __construct(array $config = [])
    {
        // Инициализация Laravel (только если не запущен из Artisan)
        if (!isset($config['skip_laravel_init']) || !$config['skip_laravel_init']) {
            try {
                $app = require_once 'bootstrap/app.php';
                $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
            } catch (Exception $e) {
                echo "❌ Ошибка инициализации Laravel: " . $e->getMessage() . "\n";
                echo "Убедитесь, что вы запускаете скрипт из корневой директории Laravel проекта.\n";
                exit(1);
            }
        }
        
        // Инициализируем конфигурацию перед использованием log()
        $this->config = array_merge([
            'verbose' => true,
            'save_reports' => false,
            'cleanup_after_test' => true,
            'test_types' => 'all', // 'all', 'create_only', 'update_only', 'status_only'
            'max_tests_per_type' => 0, // без ограничений
            'test_timeout' => 1800, // 30 minutes
            'parallel_execution' => false,
            'pause_on_error' => false,
            'real_time_output' => true
        ], $config);
        
        $this->generator = new DynamicCapTestGenerator();
        $this->engine = new DynamicCapTestEngine($config['verbose'] ?? true);
        $this->combinations = new DynamicCapCombinationGenerator(
            $config['max_combination_size'] ?? 3,
            $config['max_permutations'] ?? 12
        );
        $this->reporter = new DynamicCapReportGenerator();
        
        $this->log("🔧 Компоненты системы инициализированы");
        $this->log("📊 Конфигурация: " . json_encode(array_intersect_key($config, array_flip(['verbose', 'max_combination_size', 'max_permutations', 'test_types', 'max_tests_per_type']))));
        
        $this->currentCapData = [];
    }

    /**
     * Запускает полный набор тестов
     */
    public function runFullTestSuite(): void
    {
        $this->log("🚀 Запуск полного набора динамических тестов кап");
        $this->log("📋 Конфигурация:");
        $this->log("   • Максимальный размер комбинации: {$this->config['max_combination_size']}");
        $this->log("   • Максимальное количество перестановок: {$this->config['max_permutations']}");
        $this->log("   • Типы тестов: {$this->config['test_types']}");
        $this->log("");
        
        // Получаем статистику для планирования
        $stats = $this->combinations->getTestStatistics();
        
        if (!is_array($stats)) {
            $this->log("⚠️  Не удалось получить статистику тестов");
            $stats = ['total' => 0];
        }
        
        $this->log("📊 Планируемое количество тестов:");
        foreach ($stats as $type => $count) {
            if ($type !== 'total') {
                $this->log("   • {$type}: {$count} тестов");
            }
        }
        $this->log("   • Всего: " . ($stats['total'] ?? 0) . " тестов");
        $this->log("");
        
        // Запускаем тесты по типам операций
        $operationTypes = $this->getOperationTypesToTest();
        $totalTests = 0;
        $completedTests = 0;
        
        foreach ($operationTypes as $operationType) {
            $totalTests += $stats[$operationType] ?? 0;
        }
        
        foreach ($operationTypes as $operationType) {
            $this->log("\n" . str_repeat("=", 60));
            $this->log("🔍 ТЕСТИРОВАНИЕ ОПЕРАЦИИ: {$operationType}");
            $this->log(str_repeat("=", 60));
            
            $testResults = $this->runOperationTests($operationType, $completedTests, $totalTests);
            
            if (is_array($testResults)) {
                $successCount = 0;
                $failureCount = 0;
                
                foreach ($testResults as $testName => $result) {
                    $this->reporter->addTestResult($operationType, $testName, $result);
                    $completedTests++;
                    
                    if (is_array($result) && ($result['success'] ?? false)) {
                        $successCount++;
                        $this->log("✅ {$testName} - УСПЕХ");
                    } else {
                        $failureCount++;
                        $this->log("❌ {$testName} - НЕУДАЧА");
                        $this->pauseOnError($testName, $result);
                    }
                }
                
                $this->log("\n📊 Итоги операции {$operationType}:");
                $this->log("   ✅ Успешно: {$successCount}");
                $this->log("   ❌ Неудачно: {$failureCount}");
                $this->log("   📈 Всего: " . ($successCount + $failureCount));
                
            } else {
                $this->log("⚠️  Не удалось получить результаты тестов для операции: {$operationType}");
            }
            
            $this->log("✅ Завершено тестирование операции: {$operationType}");
            $this->log("");
        }
        
        // Финализация
        $this->reporter->finalize();
        
        // Генерируем отчеты только если включено
        if ($this->config['save_reports']) {
            $this->generateReports();
        }
        
        // Выводим итоговый отчет
        $this->displayFinalReport();
        
        if ($this->config['cleanup_after_test']) {
            $this->engine->cleanup();
        }
        
        $this->log("🎉 Полный набор тестов завершен!");
    }

    /**
     * Запускает тесты для конкретного типа операции
     */
    private function runOperationTests(string $operationType, int $startIndex, int $totalTests): array
    {
        $results = [];
        $testSuite = $this->combinations->generateFullTestSuite($operationType);
        
        // Ограничиваем количество тестов если задано
        if ($this->config['max_tests_per_type'] > 0 && count($testSuite) > $this->config['max_tests_per_type']) {
            $testSuite = array_slice($testSuite, 0, $this->config['max_tests_per_type']);
        }
        
        $currentTestIndex = $startIndex;
        $totalTestsInSuite = count($testSuite);
        
        $this->log("📋 Всего тестов в наборе: {$totalTestsInSuite}");
        
        foreach ($testSuite as $testIndex => $testCase) {
            $testName = $this->generateTestName($operationType, $testIndex, $testCase);
            
            $this->log("\n🔍 Тест " . ($testIndex + 1) . "/{$totalTestsInSuite}: {$testName}");
            
            // Показываем детали теста
            if (isset($testCase['values'])) {
                $this->log("   📝 Поля: " . implode(', ', array_keys($testCase['values'])));
            }
            
            $startTime = microtime(true);
            $result = $this->runSingleTest($operationType, $testCase);
            $endTime = microtime(true);
            $duration = round(($endTime - $startTime) * 1000, 2);
            
            $results[$testName] = $result;
            $currentTestIndex++;
            
            // Выводим результат сразу
            if (is_array($result) && ($result['success'] ?? false)) {
                $this->log("   ✅ УСПЕХ ({$duration}ms)");
            } else {
                $this->log("   ❌ НЕУДАЧА ({$duration}ms)");
                if (!empty($result['error'])) {
                    $this->log("   🔍 Ошибка: " . $result['error']);
                }
            }
            
            // Проверяем таймаут
            if ($this->isTimeoutExceeded()) {
                $this->log("\n⏰ Превышен таймаут выполнения, прерываем тестирование");
                break;
            }
        }
        
        return $results;
    }

    /**
     * Запускает один тест
     */
    private function runSingleTest(string $operationType, array $testCase): array
    {
        try {
            switch ($operationType) {
                case 'message_create_single_one':
                    return $this->runCreateSingleCapTest($testCase);
                    
                case 'message_create_single_many':
                    return $this->runCreateMultiCapTest($testCase);
                    
                case 'message_create_group_one':
                case 'message_create_group_many':
                    return $this->runCreateGroupCapTest($testCase);
                    
                default:
                    if (strpos($operationType, 'update') !== false) {
                        return $this->runUpdateCapTest($operationType, $testCase);
                    } else {
                        return [
                            'success' => false,
                            'error' => 'Неизвестный тип операции: ' . $operationType,
                            'message' => 'N/A'
                        ];
                    }
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Исключение: ' . $e->getMessage(),
                'message' => 'N/A'
            ];
        }
    }

    /**
     * Запускает тест создания одной капы
     */
    private function runCreateSingleCapTest(array $testCase): array
    {
        if (!isset($testCase['values'])) {
            return [
                'success' => false,
                'error' => 'Отсутствуют данные values в testCase',
                'message' => 'N/A'
            ];
        }
        
        $capData = $testCase['values'];
        $result = $this->engine->testSingleCapCreation($capData);
        
        // Сохраняем данные капы для последующих тестов обновления
        if (is_array($result) && ($result['success'] ?? false)) {
            $this->currentCapData[] = [
                'affiliate' => $capData['affiliate'] ?? '',
                'recipient' => $capData['recipient'] ?? '',
                'geo' => $capData['geo'] ?? ''
            ];
        }
        
        return $result;
    }

    /**
     * Запускает тест создания нескольких кап
     */
    private function runCreateMultiCapTest(array $testCase): array
    {
        if (!isset($testCase['caps']) || !isset($testCase['geo_funnel']['values'])) {
            return [
                'success' => false,
                'error' => 'Отсутствуют данные caps или geo_funnel в testCase',
                'message' => 'N/A'
            ];
        }
        
        $baseData = [
            'affiliate' => 'aff1',
            'recipient' => 'brok1'
        ];
        
        $capValues = $testCase['caps'];
        $geoFunnelValues = $testCase['geo_funnel']['values'];
        
        $result = $this->engine->testMultiCapCreation($baseData, $capValues, $geoFunnelValues);
        
        // Сохраняем данные кап для последующих тестов
        if (is_array($result) && ($result['success'] ?? false)) {
            foreach ($geoFunnelValues as $value) {
                $this->currentCapData[] = [
                    'affiliate' => $baseData['affiliate'] ?? '',
                    'recipient' => $baseData['recipient'] ?? '',
                    'geo' => $value ?? ''
                ];
            }
        }
        
        return $result;
    }

    /**
     * Запускает тест создания группы кап
     */
    private function runCreateGroupCapTest(array $testCase): array
    {
        if (!isset($testCase['blocks'])) {
            return [
                'success' => false,
                'error' => 'Отсутствуют данные blocks в testCase',
                'message' => 'N/A'
            ];
        }
        
        $blocks = $testCase['blocks'];
        $result = $this->engine->testGroupCapCreation($blocks);
        
        // Сохраняем данные кап для последующих тестов
        if (is_array($result) && ($result['success'] ?? false)) {
            foreach ($blocks as $block) {
                $this->currentCapData[] = [
                    'affiliate' => $block['affiliate'] ?? '',
                    'recipient' => $block['recipient'] ?? '',
                    'geo' => $block['geo'] ?? ''
                ];
            }
        }
        
        return $result;
    }

    /**
     * Запускает тест обновления капы
     */
    private function runUpdateCapTest(string $operationType, array $testCase): array
    {
        // Для тестов обновления нужна существующая капа
        if (empty($this->currentCapData)) {
            // Создаем базовую капу для обновления
            $baseCapData = $this->generator->generateBaseCapFields();
                    $createResult = $this->engine->testSingleCapCreation($baseCapData);
        
        if (!is_array($createResult) || !($createResult['success'] ?? false)) {
            return [
                'success' => false,
                'error' => 'Не удалось создать базовую капу для обновления',
                'message' => 'N/A'
            ];
        }
            
            $this->currentCapData[] = [
                'affiliate' => $baseCapData['affiliate'] ?? '',
                'recipient' => $baseCapData['recipient'] ?? '',
                'geo' => $baseCapData['geo'] ?? ''
            ];
        }
        
        // Берем первую доступную капу для обновления
        if (empty($this->currentCapData)) {
            return [
                'success' => false,
                'error' => 'Нет доступных кап для обновления',
                'message' => 'N/A'
            ];
        }
        
        $identifierFields = $this->currentCapData[0];
        
        if (!isset($testCase['values'])) {
            return [
                'success' => false,
                'error' => 'Отсутствуют данные values в testCase для обновления',
                'message' => 'N/A'
            ];
        }
        
        $updateFields = $testCase['values'];
        
        // Определяем тип обновления
        $replyToMessageId = null;
        if (strpos($operationType, 'reply') !== false) {
            // Для ответов нужен ID сообщения
            $replyToMessageId = 1; // Упрощенная реализация
        }
        
        return $this->engine->testCapUpdate($identifierFields, $updateFields, $replyToMessageId);
    }

    /**
     * Генерирует имя теста
     */
    private function generateTestName(string $operationType, int $testIndex, array $testCase): string
    {
        $baseName = "{$operationType}_{$testIndex}";
        
        if (isset($testCase['values'])) {
            $fields = array_keys($testCase['values']);
            $baseName .= "_" . implode('_', array_slice($fields, 0, 3));
        }
        
        return $baseName;
    }

    /**
     * Получает типы операций для тестирования
     */
    private function getOperationTypesToTest(): array
    {
        $allTypes = $this->generator->getOperationTypes();
        
        switch ($this->config['test_types']) {
            case 'create_only':
                return array_filter($allTypes, function($type) {
                    return strpos($type, 'create') !== false;
                });
                
            case 'update_only':
                return array_filter($allTypes, function($type) {
                    return strpos($type, 'update') !== false;
                });
                
            case 'status_only':
                return []; // Будет добавлено позже
                
            default:
                return $allTypes;
        }
    }

    /**
     * Проверяет превышение таймаута
     */
    private function isTimeoutExceeded(): bool
    {
        $statistics = $this->reporter->getStatistics();
        if (!is_array($statistics)) {
            return false; // Если статистика недоступна, считаем что таймаут не превышен
        }
        
        $startTime = $statistics['start_time'] ?? 0;
        return (microtime(true) - $startTime) > $this->config['test_timeout'];
    }

    /**
     * Генерирует отчеты
     */
    private function generateReports(): void
    {
        $this->log("📝 Генерация отчетов...");
        
        // Выводим краткий отчет в консоль
        echo "\n" . $this->reporter->generateSummaryReport() . "\n";
        
        if ($this->config['save_reports']) {
            $savedFiles = $this->reporter->saveAllReports('dynamic_cap_test_report');
            
            $this->log("💾 Сохранены файлы отчетов:");
            foreach ($savedFiles as $file) {
                $this->log("   • {$file}");
            }
            
            // Сохраняем анализ ошибок
            $analysisReport = $this->reporter->generateErrorAnalysisReport();
            $analysisFile = 'dynamic_cap_test_analysis_' . date('Y-m-d_H-i-s') . '.txt';
            
            if ($this->reporter->saveReport($analysisFile, $analysisReport)) {
                $this->log("   • {$analysisFile}");
            }
        }
    }

    /**
     * Логирование
     */
    private function log(string $message): void
    {
        if (isset($this->config) && is_array($this->config) && ($this->config['verbose'] ?? false)) {
            echo $message . "\n";
            if ($this->config['real_time_output'] ?? false) {
                flush();
            }
        }
    }
    
    /**
     * Пауза на ошибке
     */
    private function pauseOnError(string $testName, array $result): void
    {
        if (!($this->config['pause_on_error'] ?? false)) {
            return;
        }
        
        echo "\n" . str_repeat("=", 80) . "\n";
        echo "❌ ОШИБКА В ТЕСТЕ: {$testName}\n";
        echo str_repeat("=", 80) . "\n";
        
        if (!empty($result['errors'])) {
            echo "🔍 Детали ошибок:\n";
            foreach ($result['errors'] as $error) {
                echo "   • {$error}\n";
            }
        }
        
        if (!empty($result['error'])) {
            echo "🔍 Основная ошибка: {$result['error']}\n";
        }
        
        if (!empty($result['message'])) {
            echo "📝 Сообщение: {$result['message']}\n";
        }
        
        if (!empty($result['expected_caps'])) {
            echo "🎯 Ожидаемые капы:\n";
            foreach ($result['expected_caps'] as $cap) {
                echo "   • {$cap['affiliate']} -> {$cap['recipient']} ({$cap['geo']}) = {$cap['cap_amounts']}\n";
            }
        }
        
        echo str_repeat("=", 80) . "\n";
        echo "Нажмите Enter для продолжения...";
        fgets(STDIN);
        echo "\n";
    }

    /**
     * Запускает тестирование команд статуса
     */
    public function runStatusCommandTests(): void
    {
        $this->log("🔧 Тестирование команд статуса...");
        
        // Создаем базовую капу для тестирования статуса
        $baseCapData = $this->generator->generateBaseCapFields();
        $createResult = $this->engine->testSingleCapCreation($baseCapData);
        
        if (!is_array($createResult) || !($createResult['success'] ?? false)) {
            $this->log("❌ Не удалось создать базовую капу для тестирования статуса");
            return;
        }
        
        $identifierFields = [
            'affiliate' => $baseCapData['affiliate'] ?? '',
            'recipient' => $baseCapData['recipient'] ?? '',
            'geo' => $baseCapData['geo'] ?? ''
        ];
        
        $commands = $this->generator->getStatusCommands();
        
        foreach ($commands as $command) {
            $this->log("🔍 Тестирование команды: {$command}");
            
            $result = $this->engine->testStatusCommand($identifierFields, $command);
            $testName = "status_command_{$command}";
            
            $this->reporter->addTestResult('status_commands', $testName, $result);
            
            if (is_array($result) && ($result['success'] ?? false)) {
                $this->log("✅ Команда {$command} выполнена успешно");
            } else {
                $this->log("❌ Ошибка в команде {$command}");
            }
        }
    }

    /**
     * Запускает тестирование сброса полей
     */
    public function runFieldResetTests(): void
    {
        $this->log("🔄 Тестирование сброса полей...");
        
        // Создаем капу с заполненными полями
        $fullCapData = [
            'affiliate' => 'aff1',
            'recipient' => 'brok1',
            'geo' => 'ru',
            'cap' => '20',
            'schedule' => '10-19',
            'date' => '25.12',
            'language' => 'ru',
            'funnel' => 'test offer',
            'total' => '100',
            'pending_acq' => 'yes',
            'freeze_status_on_acq' => 'yes',
            'test' => 'yes'
        ];
        
        $createResult = $this->engine->testSingleCapCreation($fullCapData);
        
        if (!is_array($createResult) || !($createResult['success'] ?? false)) {
            $this->log("❌ Не удалось создать капу для тестирования сброса");
            return;
        }
        
        $identifierFields = [
            'affiliate' => $fullCapData['affiliate'] ?? '',
            'recipient' => $fullCapData['recipient'] ?? '',
            'geo' => $fullCapData['geo'] ?? ''
        ];
        
        $resetCombinations = $this->combinations->generateResetCombinations();
        
        foreach ($resetCombinations as $index => $resetFields) {
            $this->log("🔍 Тестирование сброса полей: " . implode(', ', $resetFields));
            
            $result = $this->engine->testFieldReset($identifierFields, $resetFields);
            $testName = "field_reset_" . $index;
            
            $this->reporter->addTestResult('field_resets', $testName, $result);
        }
    }

    /**
     * Запускает быстрое тестирование (ограниченный набор)
     */
    public function runQuickTest(): void
    {
        $this->log("⚡ Запуск быстрого тестирования...");
        
        // Ограничиваем количество тестов
        $this->config['max_tests_per_type'] = 10;
        $this->config['max_combination_size'] = 2;
        $this->config['max_permutations'] = 6;
        
        $this->runFullTestSuite();
    }

    /**
     * Получает статистику по тестам
     */
    public function getTestStatistics(): array
    {
        return $this->combinations->getTestStatistics();
    }
    
    /**
     * Показывает итоговый отчет
     */
    private function displayFinalReport(): void
    {
        $this->log("\n" . str_repeat("=", 80));
        $this->log("📊 ИТОГОВЫЙ ОТЧЕТ");
        $this->log(str_repeat("=", 80));
        
        $statistics = $this->reporter->getStatistics();
        
        if (is_array($statistics)) {
            $this->log("⏱️  Общее время выполнения: " . round($statistics['duration'] ?? 0, 2) . " сек");
            $this->log("📈 Всего тестов: " . ($statistics['total_tests'] ?? 0));
            $this->log("✅ Успешных: " . ($statistics['successful_tests'] ?? 0));
            $this->log("❌ Неудачных: " . ($statistics['failed_tests'] ?? 0));
            
            if (($statistics['total_tests'] ?? 0) > 0) {
                $successRate = round((($statistics['successful_tests'] ?? 0) / ($statistics['total_tests'] ?? 0)) * 100, 2);
                $this->log("🎯 Процент успеха: {$successRate}%");
            }
        }
        
        $this->log(str_repeat("=", 80));
    }
}

// Проверяем, был ли файл запущен напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 Система динамических тестов кап\n";
    echo "=====================================\n\n";
    
    // Простая конфигурация для полного тестирования
    $config = [
        'verbose' => true,
        'save_reports' => false,
        'cleanup_after_test' => true,
        'test_types' => 'all',
        'max_tests_per_type' => 0, // Без ограничений
        'max_combination_size' => 3,
        'max_permutations' => 12,
        'pause_on_error' => true,
        'real_time_output' => true
    ];
    
    $runner = new DynamicCapTestRunner($config);
    $runner->runFullTestSuite();
} 