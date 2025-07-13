<?php

require_once 'DynamicCapTestGenerator.php';

/**
 * Генератор комбинаций полей для динамических тестов кап
 */
class DynamicCapCombinationGenerator
{
    private $generator;
    private $maxCombinationSize;
    private $maxPermutations;

    public function __construct(int $maxCombinationSize = 4, int $maxPermutations = 24)
    {
        $this->generator = new DynamicCapTestGenerator();
        $this->maxCombinationSize = $maxCombinationSize;
        $this->maxPermutations = $maxPermutations;
    }

    /**
     * Генерирует все возможные комбинации полей для создания кап
     */
    public function generateCreateCombinations(): array
    {
        $combinations = [];
        $allFields = $this->generator->getAllFields();
        
        // Убираем обязательные поля из списка для комбинирования
        $requiredFields = ['affiliate', 'recipient', 'cap', 'geo'];
        $optionalFields = array_diff($allFields, $requiredFields);
        
        // Генерируем комбинации от 1 до maxCombinationSize дополнительных полей
        for ($size = 0; $size <= min($this->maxCombinationSize, count($optionalFields)); $size++) {
            $fieldCombinations = $this->getCombinations($optionalFields, $size);
            
            foreach ($fieldCombinations as $fields) {
                $combinations[] = array_merge($requiredFields, $fields);
            }
        }
        
        return $combinations;
    }

    /**
     * Генерирует все возможные комбинации полей для обновления кап
     */
    public function generateUpdateCombinations(): array
    {
        $combinations = [];
        $updateableFields = [
            'cap', 'schedule', 'date', 'language', 'funnel', 'total', 
            'pending_acq', 'freeze_status_on_acq', 'test'
        ];
        
        // Генерируем комбинации от 1 до maxCombinationSize полей для обновления
        for ($size = 1; $size <= min($this->maxCombinationSize, count($updateableFields)); $size++) {
            $fieldCombinations = $this->getCombinations($updateableFields, $size);
            $combinations = array_merge($combinations, $fieldCombinations);
        }
        
        return $combinations;
    }

    /**
     * Генерирует все возможные комбинации значений для полей
     */
    public function generateValueCombinations(array $fields): array
    {
        $combinations = [];
        
        // Рекурсивно генерируем все комбинации значений
        $this->generateValueCombinationsRecursive($fields, [], 0, $combinations);
        
        return $combinations;
    }

    /**
     * Рекурсивная функция для генерации комбинаций значений
     */
    private function generateValueCombinationsRecursive(array $fields, array $current, int $index, array &$combinations): void
    {
        if ($index >= count($fields)) {
            $combinations[] = $current;
            return;
        }
        
        $field = $fields[$index];
        $values = $this->generator->getFieldValues($field);
        
        foreach ($values as $value) {
            $newCurrent = $current;
            $newCurrent[$field] = $value;
            $this->generateValueCombinationsRecursive($fields, $newCurrent, $index + 1, $combinations);
        }
    }

    /**
     * Генерирует все возможные перестановки порядка полей
     */
    public function generateFieldOrderPermutations(array $fields): array
    {
        $permutations = $this->getPermutations($fields);
        
        // Ограничиваем количество перестановок для производительности
        if (count($permutations) > $this->maxPermutations) {
            $permutations = array_slice($permutations, 0, $this->maxPermutations);
        }
        
        return $permutations;
    }

    /**
     * Генерирует комбинации geo и funnel для тестирования разделения
     */
    public function generateGeoFunnelCombinations(): array
    {
        $combinations = [];
        
        // Geo комбинации
        $geoValues = $this->generator->getFieldValues('geo');
        $geoCombinations = [
            [$geoValues[0]], // 1 гео
            [$geoValues[0], $geoValues[1]], // 2 гео
            [$geoValues[0], $geoValues[1], $geoValues[2]], // 3 гео
            [$geoValues[0], $geoValues[1], $geoValues[2], $geoValues[3]], // 4 гео
        ];
        
        foreach ($geoCombinations as $geos) {
            $combinations[] = [
                'type' => 'geo',
                'values' => $geos
            ];
        }
        
        // Funnel комбинации
        $funnelValues = $this->generator->getFieldValues('funnel');
        $funnelCombinations = [
            [$funnelValues[0]], // 1 воронка
            [$funnelValues[0], $funnelValues[1]], // 2 воронки
            [$funnelValues[0], $funnelValues[1], $funnelValues[2]], // 3 воронки
        ];
        
        foreach ($funnelCombinations as $funnels) {
            $combinations[] = [
                'type' => 'funnel',
                'values' => $funnels
            ];
        }
        
        return $combinations;
    }

    /**
     * Генерирует комбинации cap значений
     */
    public function generateCapCombinations(): array
    {
        $capValues = $this->generator->getFieldValues('cap');
        
        return [
            [$capValues[0]], // 1 капа
            [$capValues[0], $capValues[1]], // 2 капы
            [$capValues[0], $capValues[1], $capValues[2]], // 3 капы
            [$capValues[0], $capValues[1], $capValues[2], $capValues[3]], // 4 капы
        ];
    }

    /**
     * Генерирует все возможные комбинации для сброса полей
     */
    public function generateResetCombinations(): array
    {
        $combinations = [];
        $resettableFields = $this->generator->getResettableFields();
        
        // Генерируем комбинации от 1 до всех полей для сброса
        for ($size = 1; $size <= count($resettableFields); $size++) {
            $fieldCombinations = $this->getCombinations($resettableFields, $size);
            $combinations = array_merge($combinations, $fieldCombinations);
        }
        
        return $combinations;
    }

    /**
     * Генерирует тестовые случаи для групповых операций
     */
    public function generateGroupTestCases(): array
    {
        $testCases = [];
        
        // 2 блока
        $testCases[] = [
            'block_count' => 2,
            'blocks' => [
                ['affiliate' => 'aff1', 'recipient' => 'brok1', 'geo' => 'ru', 'cap' => '10'],
                ['affiliate' => 'aff2', 'recipient' => 'brok2', 'geo' => 'us', 'cap' => '20']
            ]
        ];
        
        // 3 блока
        $testCases[] = [
            'block_count' => 3,
            'blocks' => [
                ['affiliate' => 'aff1', 'recipient' => 'brok1', 'geo' => 'ru', 'cap' => '10'],
                ['affiliate' => 'aff2', 'recipient' => 'brok2', 'geo' => 'us', 'cap' => '20'],
                ['affiliate' => 'aff3', 'recipient' => 'brok1', 'geo' => 'de', 'cap' => '30']
            ]
        ];
        
        // 4 блока
        $testCases[] = [
            'block_count' => 4,
            'blocks' => [
                ['affiliate' => 'aff1', 'recipient' => 'brok1', 'geo' => 'ru', 'cap' => '10'],
                ['affiliate' => 'aff2', 'recipient' => 'brok2', 'geo' => 'us', 'cap' => '20'],
                ['affiliate' => 'aff3', 'recipient' => 'brok1', 'geo' => 'de', 'cap' => '30'],
                ['affiliate' => 'g06', 'recipient' => 'tmedia', 'geo' => 'fr', 'cap' => '50']
            ]
        ];
        
        return $testCases;
    }

    /**
     * Генерирует комбинации ошибочных случаев
     */
    public function generateErrorCases(): array
    {
        $errorCases = [];
        
        // Неправильное соответствие cap и geo
        $errorCases[] = [
            'type' => 'cap_geo_mismatch',
            'description' => 'Неправильное соответствие cap и geo',
            'caps' => ['10', '20', '30'],
            'geos' => ['ru', 'us'], // Меньше чем cap
            'expected_error' => true
        ];
        
        $errorCases[] = [
            'type' => 'cap_geo_mismatch',
            'description' => 'Неправильное соответствие cap и geo',
            'caps' => ['10', '20'],
            'geos' => ['ru', 'us', 'de'], // Больше чем cap
            'expected_error' => true
        ];
        
        // Неправильное соответствие cap и funnel
        $errorCases[] = [
            'type' => 'cap_funnel_mismatch',
            'description' => 'Неправильное соответствие cap и funnel',
            'caps' => ['10', '20', '30'],
            'funnels' => ['offer 1', 'offer 2'], // Меньше чем cap
            'expected_error' => true
        ];
        
        return $errorCases;
    }

    /**
     * Генерирует полный набор тестовых случаев для одного типа операции
     */
    public function generateFullTestSuite(string $operationType): array
    {
        $testSuite = [];
        
        switch ($operationType) {
            case 'message_create_single_one':
                $testSuite = $this->generateSingleCapCreateTests();
                break;
            case 'message_create_single_many':
                $testSuite = $this->generateMultiCapCreateTests();
                break;
            case 'message_create_group_one':
            case 'message_create_group_many':
                $testSuite = $this->generateGroupCapCreateTests();
                break;
            default:
                if (strpos($operationType, 'update') !== false) {
                    $testSuite = $this->generateUpdateTests();
                }
                break;
        }
        
        return $testSuite;
    }

    /**
     * Генерирует тесты для создания одной капы
     */
    private function generateSingleCapCreateTests(): array
    {
        $tests = [];
        $fieldCombinations = $this->generateCreateCombinations();
        
        foreach ($fieldCombinations as $fields) {
            $valueCombinations = $this->generateValueCombinations($fields);
            
            foreach ($valueCombinations as $values) {
                $fieldOrders = $this->generateFieldOrderPermutations($fields);
                
                foreach ($fieldOrders as $fieldOrder) {
                    $orderedValues = [];
                    foreach ($fieldOrder as $field) {
                        $orderedValues[$field] = $values[$field];
                    }
                    
                    $tests[] = [
                        'type' => 'single_cap_create',
                        'fields' => $fieldOrder,
                        'values' => $orderedValues
                    ];
                }
            }
        }
        
        return $tests;
    }

    /**
     * Генерирует тесты для создания нескольких кап
     */
    private function generateMultiCapCreateTests(): array
    {
        $tests = [];
        $capCombinations = $this->generateCapCombinations();
        $geoFunnelCombinations = $this->generateGeoFunnelCombinations();
        
        foreach ($capCombinations as $caps) {
            foreach ($geoFunnelCombinations as $geoFunnel) {
                // Проверяем валидность комбинации
                if (!$this->generator->validateCapGeoFunnelCombination($caps, $geoFunnel['values'])) {
                    continue;
                }
                
                $tests[] = [
                    'type' => 'multi_cap_create',
                    'caps' => $caps,
                    'geo_funnel' => $geoFunnel
                ];
            }
        }
        
        return $tests;
    }

    /**
     * Генерирует тесты для групповых операций
     */
    private function generateGroupCapCreateTests(): array
    {
        $tests = [];
        $groupCases = $this->generateGroupTestCases();
        
        foreach ($groupCases as $groupCase) {
            $tests[] = [
                'type' => 'group_cap_create',
                'block_count' => $groupCase['block_count'],
                'blocks' => $groupCase['blocks']
            ];
        }
        
        return $tests;
    }

    /**
     * Генерирует тесты для обновлений
     */
    private function generateUpdateTests(): array
    {
        $tests = [];
        $updateCombinations = $this->generateUpdateCombinations();
        
        foreach ($updateCombinations as $fields) {
            $valueCombinations = $this->generateValueCombinations($fields);
            
            foreach ($valueCombinations as $values) {
                $fieldOrders = $this->generateFieldOrderPermutations($fields);
                
                foreach ($fieldOrders as $fieldOrder) {
                    $orderedValues = [];
                    foreach ($fieldOrder as $field) {
                        $orderedValues[$field] = $values[$field];
                    }
                    
                    $tests[] = [
                        'type' => 'cap_update',
                        'fields' => $fieldOrder,
                        'values' => $orderedValues
                    ];
                }
            }
        }
        
        return $tests;
    }

    /**
     * Получает все возможные комбинации массива элементов по размеру
     */
    private function getCombinations(array $array, int $size): array
    {
        // Проверка на пустой массив
        if (empty($array)) {
            return $size === 0 ? [[]] : [];
        }
        
        if ($size == 0) {
            return [[]];
        }
        
        if ($size > count($array)) {
            return [];
        }
        
        if ($size == count($array)) {
            return [$array];
        }
        
        $combinations = [];
        
        for ($i = 0; $i <= count($array) - $size; $i++) {
            $current = $array[$i];
            $remaining = array_slice($array, $i + 1);
            $subCombinations = $this->getCombinations($remaining, $size - 1);
            
            foreach ($subCombinations as $subCombination) {
                if (is_array($subCombination)) {
                    $combinations[] = array_merge([$current], $subCombination);
                }
            }
        }
        
        return $combinations;
    }

    /**
     * Получает все возможные перестановки массива
     */
    private function getPermutations(array $array): array
    {
        if (count($array) <= 1) {
            return [$array];
        }
        
        $permutations = [];
        
        for ($i = 0; $i < count($array); $i++) {
            $current = $array[$i];
            $remaining = array_merge(
                array_slice($array, 0, $i),
                array_slice($array, $i + 1)
            );
            
            foreach ($this->getPermutations($remaining) as $permutation) {
                $permutations[] = array_merge([$current], $permutation);
            }
        }
        
        return $permutations;
    }

    /**
     * Получает статистику по количеству тестов
     */
    public function getTestStatistics(): array
    {
        $stats = [];
        
        $operationTypes = $this->generator->getOperationTypes();
        
        foreach ($operationTypes as $operationType) {
            $testSuite = $this->generateFullTestSuite($operationType);
            $stats[$operationType] = count($testSuite);
        }
        
        $stats['total'] = array_sum($stats);
        
        return $stats;
    }
} 