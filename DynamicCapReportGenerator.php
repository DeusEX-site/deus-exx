<?php

/**
 * Генератор отчетов для динамических тестов кап
 */
class DynamicCapReportGenerator
{
    private $results;
    private $errors;
    private $statistics;
    private $startTime;
    private $endTime;

    public function __construct()
    {
        $this->results = [];
        $this->errors = [];
        $this->statistics = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'error_tests' => 0,
            'by_operation_type' => []
        ];
        $this->startTime = microtime(true);
    }

    /**
     * Добавляет результат теста
     */
    public function addTestResult(string $operationType, string $testName, array $result): void
    {
        $this->results[$operationType][$testName] = $result;
        $this->updateStatistics($operationType, $result);
    }

    /**
     * Обновляет статистику
     */
    private function updateStatistics(string $operationType, array $result): void
    {
        $this->statistics['total_tests']++;
        
        if (!isset($this->statistics['by_operation_type'][$operationType])) {
            $this->statistics['by_operation_type'][$operationType] = [
                'total' => 0,
                'passed' => 0,
                'failed' => 0,
                'error' => 0
            ];
        }
        
        $this->statistics['by_operation_type'][$operationType]['total']++;
        
        if ($result['success']) {
            $this->statistics['passed_tests']++;
            $this->statistics['by_operation_type'][$operationType]['passed']++;
        } else {
            if (isset($result['error'])) {
                $this->statistics['error_tests']++;
                $this->statistics['by_operation_type'][$operationType]['error']++;
                $this->errors[] = [
                    'operation_type' => $operationType,
                    'test_name' => $testName ?? 'Unknown',
                    'error' => $result['error'],
                    'message' => $result['message'] ?? 'N/A',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            } else {
                $this->statistics['failed_tests']++;
                $this->statistics['by_operation_type'][$operationType]['failed']++;
                $this->errors[] = [
                    'operation_type' => $operationType,
                    'test_name' => $testName ?? 'Unknown',
                    'errors' => $result['errors'] ?? [],
                    'message' => $result['message'] ?? 'N/A',
                    'timestamp' => date('Y-m-d H:i:s')
                ];
            }
        }
    }

    /**
     * Завершает сбор данных для отчета
     */
    public function finalize(): void
    {
        $this->endTime = microtime(true);
    }

    /**
     * Генерирует краткий отчет
     */
    public function generateSummaryReport(): string
    {
        $executionTime = $this->endTime - $this->startTime;
        
        $report = "==========================================\n";
        $report .= "         ОТЧЕТ О ТЕСТИРОВАНИИ КАП\n";
        $report .= "==========================================\n\n";
        
        $report .= "📊 ОБЩАЯ СТАТИСТИКА:\n";
        $report .= "• Всего тестов: {$this->statistics['total_tests']}\n";
        $report .= "• Пройдено: {$this->statistics['passed_tests']}\n";
        $report .= "• Не пройдено: {$this->statistics['failed_tests']}\n";
        $report .= "• Ошибки: {$this->statistics['error_tests']}\n";
        $report .= "• Время выполнения: " . round($executionTime, 2) . " секунд\n\n";
        
        $successRate = $this->statistics['total_tests'] > 0 
            ? round(($this->statistics['passed_tests'] / $this->statistics['total_tests']) * 100, 2)
            : 0;
        
        $report .= "✅ УСПЕШНОСТЬ: {$successRate}%\n\n";
        
        $report .= "📈 ПО ТИПАМ ОПЕРАЦИЙ:\n";
        foreach ($this->statistics['by_operation_type'] as $operationType => $stats) {
            $typeSuccessRate = $stats['total'] > 0 
                ? round(($stats['passed'] / $stats['total']) * 100, 2)
                : 0;
            
            $report .= "• {$operationType}: {$stats['passed']}/{$stats['total']} ({$typeSuccessRate}%)\n";
        }
        
        $report .= "\n";
        
        if (!empty($this->errors)) {
            $report .= "❌ КОЛИЧЕСТВО ОШИБОК ПО ТИПАМ:\n";
            $errorsByType = [];
            foreach ($this->errors as $error) {
                $errorsByType[$error['operation_type']] = 
                    ($errorsByType[$error['operation_type']] ?? 0) + 1;
            }
            
            foreach ($errorsByType as $operationType => $count) {
                $report .= "• {$operationType}: {$count} ошибок\n";
            }
        }
        
        return $report;
    }

    /**
     * Генерирует детальный отчет по ошибкам
     */
    public function generateErrorReport(): string
    {
        if (empty($this->errors)) {
            return "✅ ОШИБОК НЕ ОБНАРУЖЕНО!\n";
        }
        
        $report = "==========================================\n";
        $report .= "       ДЕТАЛЬНЫЙ ОТЧЕТ ПО ОШИБКАМ\n";
        $report .= "==========================================\n\n";
        
        $errorNumber = 1;
        foreach ($this->errors as $error) {
            $report .= "🔴 ОШИБКА #{$errorNumber}\n";
            $report .= "Тип операции: {$error['operation_type']}\n";
            $report .= "Название теста: {$error['test_name']}\n";
            $report .= "Время: {$error['timestamp']}\n";
            $report .= "Сообщение:\n{$error['message']}\n\n";
            
            if (isset($error['error'])) {
                $report .= "Ошибка анализа:\n{$error['error']}\n";
            }
            
            if (isset($error['errors']) && !empty($error['errors'])) {
                $report .= "Детали ошибки:\n";
                foreach ($error['errors'] as $detail) {
                    $report .= "• {$detail}\n";
                }
            }
            
            $report .= "\n" . str_repeat("-", 50) . "\n\n";
            $errorNumber++;
        }
        
        return $report;
    }

    /**
     * Генерирует отчет по результатам тестирования
     */
    public function generateResultsReport(): string
    {
        $report = "==========================================\n";
        $report .= "      РЕЗУЛЬТАТЫ ТЕСТИРОВАНИЯ ПО ТИПАМ\n";
        $report .= "==========================================\n\n";
        
        foreach ($this->results as $operationType => $tests) {
            $report .= "📋 {$operationType}\n";
            $report .= str_repeat("-", 40) . "\n";
            
            $passedCount = 0;
            $totalCount = count($tests);
            
            foreach ($tests as $testName => $result) {
                $status = $result['success'] ? '✅' : '❌';
                $report .= "{$status} {$testName}\n";
                
                if ($result['success']) {
                    $passedCount++;
                }
            }
            
            $typeSuccessRate = $totalCount > 0 
                ? round(($passedCount / $totalCount) * 100, 2)
                : 0;
            
            $report .= "\nИтого: {$passedCount}/{$totalCount} ({$typeSuccessRate}%)\n\n";
        }
        
        return $report;
    }

    /**
     * Генерирует CSV отчет
     */
    public function generateCsvReport(): string
    {
        $csv = "Operation Type,Test Name,Status,Message,Errors\n";
        
        foreach ($this->results as $operationType => $tests) {
            foreach ($tests as $testName => $result) {
                $status = $result['success'] ? 'PASS' : 'FAIL';
                $message = str_replace(["\n", "\r", ","], [" ", " ", ";"], $result['message'] ?? '');
                $errors = '';
                
                if (isset($result['errors'])) {
                    $errors = str_replace(["\n", "\r", ","], [" ", " ", ";"], implode('; ', $result['errors']));
                }
                
                $csv .= "\"{$operationType}\",\"{$testName}\",\"{$status}\",\"{$message}\",\"{$errors}\"\n";
            }
        }
        
        return $csv;
    }

    /**
     * Генерирует JSON отчет
     */
    public function generateJsonReport(): string
    {
        $report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => round($this->endTime - $this->startTime, 2),
            'statistics' => $this->statistics,
            'results' => $this->results,
            'errors' => $this->errors
        ];
        
        return json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Сохраняет отчет в файл
     */
    public function saveReport(string $filename, string $content): bool
    {
        $success = file_put_contents($filename, $content);
        return $success !== false;
    }

    /**
     * Сохраняет все отчеты
     */
    public function saveAllReports(string $baseFilename = 'cap_test_report'): array
    {
        $timestamp = date('Y-m-d_H-i-s');
        $savedFiles = [];
        
        // Краткий отчет
        $summaryFile = "{$baseFilename}_summary_{$timestamp}.txt";
        if ($this->saveReport($summaryFile, $this->generateSummaryReport())) {
            $savedFiles[] = $summaryFile;
        }
        
        // Отчет по ошибкам
        $errorFile = "{$baseFilename}_errors_{$timestamp}.txt";
        if ($this->saveReport($errorFile, $this->generateErrorReport())) {
            $savedFiles[] = $errorFile;
        }
        
        // Результаты
        $resultsFile = "{$baseFilename}_results_{$timestamp}.txt";
        if ($this->saveReport($resultsFile, $this->generateResultsReport())) {
            $savedFiles[] = $resultsFile;
        }
        
        // CSV отчет
        $csvFile = "{$baseFilename}_{$timestamp}.csv";
        if ($this->saveReport($csvFile, $this->generateCsvReport())) {
            $savedFiles[] = $csvFile;
        }
        
        // JSON отчет
        $jsonFile = "{$baseFilename}_{$timestamp}.json";
        if ($this->saveReport($jsonFile, $this->generateJsonReport())) {
            $savedFiles[] = $jsonFile;
        }
        
        return $savedFiles;
    }

    /**
     * Выводит прогресс тестирования
     */
    public function printProgress(int $currentTest, int $totalTests, string $currentTestName): void
    {
        $percentage = round(($currentTest / $totalTests) * 100, 1);
        $progressBar = str_repeat('█', (int)($percentage / 5)) . str_repeat('░', 20 - (int)($percentage / 5));
        
        echo "\r[{$progressBar}] {$percentage}% ({$currentTest}/{$totalTests}) {$currentTestName}";
        
        if ($currentTest === $totalTests) {
            echo "\n";
        }
    }

    /**
     * Анализирует паттерны ошибок
     */
    public function analyzeErrorPatterns(): array
    {
        $patterns = [
            'by_operation_type' => [],
            'by_error_type' => [],
            'common_messages' => [],
            'recommendations' => []
        ];
        
        foreach ($this->errors as $error) {
            // Группировка по типу операции
            $operationType = $error['operation_type'];
            if (!isset($patterns['by_operation_type'][$operationType])) {
                $patterns['by_operation_type'][$operationType] = 0;
            }
            $patterns['by_operation_type'][$operationType]++;
            
            // Анализ типов ошибок
            if (isset($error['errors'])) {
                foreach ($error['errors'] as $errorDetail) {
                    // Извлекаем тип ошибки из сообщения
                    if (strpos($errorDetail, 'Не найдена капа') !== false) {
                        $patterns['by_error_type']['cap_not_found'] = 
                            ($patterns['by_error_type']['cap_not_found'] ?? 0) + 1;
                    } elseif (strpos($errorDetail, 'Ожидалось') !== false) {
                        $patterns['by_error_type']['count_mismatch'] = 
                            ($patterns['by_error_type']['count_mismatch'] ?? 0) + 1;
                    } elseif (strpos($errorDetail, 'Поле') !== false) {
                        $patterns['by_error_type']['field_validation'] = 
                            ($patterns['by_error_type']['field_validation'] ?? 0) + 1;
                    }
                }
            }
        }
        
        // Генерация рекомендаций
        if (($patterns['by_error_type']['cap_not_found'] ?? 0) > 0) {
            $patterns['recommendations'][] = 
                'Частые ошибки "Не найдена капа" - проверьте логику поиска кап в базе данных';
        }
        
        if (($patterns['by_error_type']['count_mismatch'] ?? 0) > 0) {
            $patterns['recommendations'][] = 
                'Ошибки подсчета - проверьте логику создания/обновления кап';
        }
        
        if (($patterns['by_error_type']['field_validation'] ?? 0) > 0) {
            $patterns['recommendations'][] = 
                'Ошибки валидации полей - проверьте правильность парсинга и сохранения значений';
        }
        
        return $patterns;
    }

    /**
     * Генерирует отчет по анализу паттернов ошибок
     */
    public function generateErrorAnalysisReport(): string
    {
        $patterns = $this->analyzeErrorPatterns();
        
        $report = "==========================================\n";
        $report .= "       АНАЛИЗ ПАТТЕРНОВ ОШИБОК\n";
        $report .= "==========================================\n\n";
        
        $report .= "📊 ОШИБКИ ПО ТИПАМ ОПЕРАЦИЙ:\n";
        foreach ($patterns['by_operation_type'] as $operationType => $count) {
            $report .= "• {$operationType}: {$count} ошибок\n";
        }
        $report .= "\n";
        
        $report .= "🔍 ОШИБКИ ПО ТИПАМ ПРОБЛЕМ:\n";
        foreach ($patterns['by_error_type'] as $errorType => $count) {
            $report .= "• {$errorType}: {$count} случаев\n";
        }
        $report .= "\n";
        
        if (!empty($patterns['recommendations'])) {
            $report .= "💡 РЕКОМЕНДАЦИИ:\n";
            foreach ($patterns['recommendations'] as $recommendation) {
                $report .= "• {$recommendation}\n";
            }
        }
        
        return $report;
    }

    /**
     * Получает статистику
     */
    public function getStatistics(): array
    {
        return $this->statistics;
    }

    /**
     * Получает ошибки
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Получает результаты
     */
    public function getResults(): array
    {
        return $this->results;
    }

    /**
     * Очищает все данные
     */
    public function clear(): void
    {
        $this->results = [];
        $this->errors = [];
        $this->statistics = [
            'total_tests' => 0,
            'passed_tests' => 0,
            'failed_tests' => 0,
            'error_tests' => 0,
            'by_operation_type' => []
        ];
        $this->startTime = microtime(true);
    }
} 