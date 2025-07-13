<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

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
        // Bootstrap Laravel
        $app = require_once __DIR__ . '/bootstrap/app.php';
        $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
        $kernel->bootstrap();
        
        $this->generator = new DynamicCapTestGenerator();
        $this->engine = new DynamicCapTestEngine($config['verbose'] ?? true);
        $this->combinations = new DynamicCapCombinationGenerator(
            $config['max_combination_size'] ?? 3,
            $config['max_permutations'] ?? 12
        );
        $this->reporter = new DynamicCapReportGenerator();
        
        $this->config = array_merge([
            'verbose' => true,
            'save_reports' => true,
            'cleanup_after_test' => true,
            'test_types' => 'all', // 'all', 'create_only', 'update_only', 'status_only'
            'max_tests_per_type' => 100,
            'test_timeout' => 300, // 5 minutes
            'parallel_execution' => false
        ], $config);
        
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
        $this->log("📊 Планируемое количество тестов:");
        foreach ($stats as $type => $count) {
            if ($type !== 'total') {
                $this->log("   • {$type}: {$count} тестов");
            }
        }
        $this->log("   • Всего: {$stats['total']} тестов");
        $this->log("");
        
        // Запускаем тесты по типам операций
        $operationTypes = $this->getOperationTypesToTest();
        $totalTests = 0;
        $completedTests = 0;
        
        foreach ($operationTypes as $operationType) {
            $totalTests += $stats[$operationType] ?? 0;
        }
        
        foreach ($operationTypes as $operationType) {
            $this->log("🔍 Тестирование операции: {$operationType}");
            
            $testResults = $this->runOperationTests($operationType, $completedTests, $totalTests);
            
            foreach ($testResults as $testName => $result) {
                $this->reporter->addTestResult($operationType, $testName, $result);
                $completedTests++;
            }
            
            $this->log("✅ Завершено тестирование операции: {$operationType}");
            $this->log("");
        }
        
        // Финализация и создание отчетов
        $this->reporter->finalize();
        $this->generateReports();
        
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
        
        foreach ($testSuite as $testIndex => $testCase) {
            $testName = $this->generateTestName($operationType, $testIndex, $testCase);
            
            $this->reporter->printProgress($currentTestIndex + 1, $totalTests, $testName);
            
            $result = $this->runSingleTest($operationType, $testCase);
            $results[$testName] = $result;
            
            $currentTestIndex++;
            
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
        $capData = $testCase['values'];
        $result = $this->engine->testSingleCapCreation($capData);
        
        // Сохраняем данные капы для последующих тестов обновления
        if ($result['success']) {
            $this->currentCapData[] = [
                'affiliate' => $capData['affiliate'],
                'recipient' => $capData['recipient'],
                'geo' => $capData['geo']
            ];
        }
        
        return $result;
    }

    /**
     * Запускает тест создания нескольких кап
     */
    private function runCreateMultiCapTest(array $testCase): array
    {
        $baseData = [
            'affiliate' => 'aff1',
            'recipient' => 'brok1'
        ];
        
        $capValues = $testCase['caps'];
        $geoFunnelValues = $testCase['geo_funnel']['values'];
        
        $result = $this->engine->testMultiCapCreation($baseData, $capValues, $geoFunnelValues);
        
        // Сохраняем данные кап для последующих тестов
        if ($result['success']) {
            foreach ($geoFunnelValues as $value) {
                $this->currentCapData[] = [
                    'affiliate' => $baseData['affiliate'],
                    'recipient' => $baseData['recipient'],
                    'geo' => $value
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
        $blocks = $testCase['blocks'];
        $result = $this->engine->testGroupCapCreation($blocks);
        
        // Сохраняем данные кап для последующих тестов
        if ($result['success']) {
            foreach ($blocks as $block) {
                $this->currentCapData[] = [
                    'affiliate' => $block['affiliate'],
                    'recipient' => $block['recipient'],
                    'geo' => $block['geo']
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
            
            if (!$createResult['success']) {
                return [
                    'success' => false,
                    'error' => 'Не удалось создать базовую капу для обновления',
                    'message' => 'N/A'
                ];
            }
            
            $this->currentCapData[] = [
                'affiliate' => $baseCapData['affiliate'],
                'recipient' => $baseCapData['recipient'],
                'geo' => $baseCapData['geo']
            ];
        }
        
        // Берем первую доступную капу для обновления
        $identifierFields = $this->currentCapData[0];
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
        return (microtime(true) - $this->reporter->getStatistics()['start_time'] ?? 0) > $this->config['test_timeout'];
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
        if ($this->config['verbose']) {
            echo $message . "\n";
        }
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
        
        if (!$createResult['success']) {
            $this->log("❌ Не удалось создать базовую капу для тестирования статуса");
            return;
        }
        
        $identifierFields = [
            'affiliate' => $baseCapData['affiliate'],
            'recipient' => $baseCapData['recipient'],
            'geo' => $baseCapData['geo']
        ];
        
        $commands = $this->generator->getStatusCommands();
        
        foreach ($commands as $command) {
            $this->log("🔍 Тестирование команды: {$command}");
            
            $result = $this->engine->testStatusCommand($identifierFields, $command);
            $testName = "status_command_{$command}";
            
            $this->reporter->addTestResult('status_commands', $testName, $result);
            
            if ($result['success']) {
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
        
        if (!$createResult['success']) {
            $this->log("❌ Не удалось создать капу для тестирования сброса");
            return;
        }
        
        $identifierFields = [
            'affiliate' => $fullCapData['affiliate'],
            'recipient' => $fullCapData['recipient'],
            'geo' => $fullCapData['geo']
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
}

// Проверяем, был ли файл запущен напрямую
if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    echo "🚀 Система динамических тестов кап\n";
    echo "=====================================\n\n";
    
    // Конфигурация из аргументов командной строки
    $config = [
        'verbose' => true,
        'save_reports' => true,
        'cleanup_after_test' => true,
        'test_types' => 'all',
        'max_tests_per_type' => 50, // Ограничиваем для демонстрации
        'max_combination_size' => 3,
        'max_permutations' => 12
    ];
    
    // Проверяем аргументы
    if (isset($argv[1])) {
        switch ($argv[1]) {
            case 'quick':
                $config['test_types'] = 'create_only';
                $config['max_tests_per_type'] = 10;
                $config['max_combination_size'] = 2;
                break;
            case 'full':
                $config['max_tests_per_type'] = 0; // Без ограничений
                break;
            case 'status':
                $config['test_types'] = 'status_only';
                break;
        }
    }
    
    $runner = new DynamicCapTestRunner($config);
    
    if (isset($argv[1]) && $argv[1] === 'quick') {
        $runner->runQuickTest();
    } elseif (isset($argv[1]) && $argv[1] === 'status') {
        $runner->runStatusCommandTests();
    } else {
        $runner->runFullTestSuite();
    }
} 