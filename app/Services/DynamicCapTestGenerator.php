<?php

namespace App\Services;

/**
 * Динамический генератор тестов для системы кап
 * Покрывает все 16 типов операций + команды статуса
 */
class DynamicCapTestGenerator
{
    /**
     * Все возможные типы операций
     */
    private const OPERATION_TYPES = [
        // Сообщения - создание
        'message_create_single_one',     // Сообщение > Создание > Одиночное > Одна капа
        'message_create_single_many',    // Сообщение > Создание > Одиночное > Много кап
        'message_create_group_one',      // Сообщение > Создание > Групповое > Одна капа
        'message_create_group_many',     // Сообщение > Создание > Групповое > Много кап
        
        // Сообщения - обновление
        'message_update_single_one',     // Сообщение > Обновление > Одиночное > Одна капа
        'message_update_single_many',    // Сообщение > Обновление > Одиночное > Много кап
        'message_update_group_one',      // Сообщение > Обновление > Групповое > Одна капа
        'message_update_group_many',     // Сообщение > Обновление > Групповое > Много кап
        
        // Ответы - обновление
        'reply_update_single_one',       // Ответ > Обновление > Одиночное > Одна капа
        'reply_update_single_many',      // Ответ > Обновление > Одиночное > Много кап
        'reply_update_group_one',        // Ответ > Обновление > Групповое > Одна капа
        'reply_update_group_many',       // Ответ > Обновление > Групповое > Много кап
        
        // Цитаты - обновление
        'quote_update_single_one',       // Цитата > Обновление > Одиночное > Одна капа
        'quote_update_single_many',      // Цитата > Обновление > Одиночное > Много кап
        'quote_update_group_one',        // Цитата > Обновление > Групповое > Одна капа
        'quote_update_group_many',       // Цитата > Обновление > Групповое > Много кап
    ];

    /**
     * Команды статуса
     */
    private const STATUS_COMMANDS = ['run', 'stop', 'delete', 'restore', 'status'];

    /**
     * Минимальные обязательные поля
     */
    private const REQUIRED_FIELDS = ['affiliate', 'recipient', 'geo'];

    /**
     * Все поля системы кап
     */
    private const ALL_FIELDS = [
        'affiliate', 'recipient', 'cap', 'geo', 'schedule', 'date', 
        'language', 'funnel', 'total', 'pending_acq', 'freeze_status_on_acq', 'test'
    ];

    /**
     * Поля, которые можно сбрасывать до значений по умолчанию
     */
    private const RESETTABLE_FIELDS = [
        'date', 'schedule', 'total', 'funnel', 'language', 'test', 'pending_acq', 'freeze_status_on_acq'
    ];

    /**
     * Значения по умолчанию для полей
     */
    private const DEFAULT_VALUES = [
        'date' => null,
        'schedule' => '24/7',
        'total' => -1,
        'funnel' => null,
        'language' => 'en',
        'test' => 'no',
        'pending_acq' => 'no',
        'freeze_status_on_acq' => 'no'
    ];

    /**
     * Возможные значения для каждого поля
     */
    private const FIELD_VALUES = [
        'affiliate' => ['aff1', 'aff2', 'aff3', 'g06', 'webgate'],
        'recipient' => ['brok1', 'brok2', 'tmedia', 'tradingm', 'cryptotrader'],
        'cap' => ['10', '20', '30', '50', '100'],
        'geo' => ['ru', 'us', 'de', 'fr', 'ua', 'kz', 'at', 'au', 'ie', 'gt', 'pe', 'mx'],
        'schedule' => ['24/7', '24/5', '10-19', '10-19 gmt +3', '10:30/19:30', '10:30/19:30 gmt +3'],
        'date' => ['24.02', '23.03.2025', '01.01', '15.12.2024'],
        'language' => ['ru', 'en', 'de', 'fr'],
        'funnel' => ['test offer', 'offer 1', 'offer 2', 'deusexx', 'deusexx2'],
        'total' => ['100', '200', '500', '1000', '-1'],
        'pending_acq' => ['yes', 'no'],
        'freeze_status_on_acq' => ['yes', 'no'],
        'test' => ['yes', 'no', 'geo', 'any']
    ];

    /**
     * Генерирует сообщение для создания одной капы
     */
    public function generateSingleCapMessage(array $fields): string
    {
        $message = '';
        
        foreach ($fields as $field => $value) {
            $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
        }
        
        return trim($message);
    }

    /**
     * Генерирует сообщение для создания нескольких кап (разделение по geo/funnel)
     */
    public function generateMultiCapMessage(array $baseFields, array $capValues, array $geoOrFunnelValues): string
    {
        $message = '';
        
        // Базовые поля
        foreach ($baseFields as $field => $value) {
            if ($field === 'cap') {
                $message .= $this->formatFieldName($field) . ': ' . implode(' ', $capValues) . "\n";
            } elseif ($field === 'geo' || $field === 'funnel') {
                $message .= $this->formatFieldName($field) . ': ' . implode(' ', $geoOrFunnelValues) . "\n";
            } else {
                $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
            }
        }
        
        return trim($message);
    }

    /**
     * Генерирует групповое сообщение (несколько блоков)
     */
    public function generateGroupMessage(array $blocks): string
    {
        $message = '';
        
        foreach ($blocks as $index => $block) {
            if ($index > 0) {
                $message .= "\n\n";
            }
            
            foreach ($block as $field => $value) {
                $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
            }
        }
        
        return trim($message);
    }

    /**
     * Генерирует сообщение обновления (только измененные поля)
     */
    public function generateUpdateMessage(array $identifierFields, array $updateFields): string
    {
        $message = '';
        
        // Сначала идентификаторы для поиска капы
        foreach ($identifierFields as $field => $value) {
            $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
        }
        
        // Потом обновляемые поля
        foreach ($updateFields as $field => $value) {
            $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
        }
        
        return trim($message);
    }

    /**
     * Генерирует сообщение для сброса полей (пустые значения)
     */
    public function generateResetMessage(array $identifierFields, array $resetFields): string
    {
        $message = '';
        
        // Сначала идентификаторы для поиска капы
        foreach ($identifierFields as $field => $value) {
            $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
        }
        
        // Потом поля для сброса (пустые значения)
        foreach ($resetFields as $field) {
            $message .= $this->formatFieldName($field) . ': ' . "\n";
        }
        
        return trim($message);
    }

    /**
     * Генерирует команду статуса
     */
    public function generateStatusCommand(array $identifierFields, string $command): string
    {
        $message = '';
        
        // Сначала идентификаторы для поиска капы
        foreach ($identifierFields as $field => $value) {
            $message .= $this->formatFieldName($field) . ': ' . $value . "\n";
        }
        
        // Потом команда статуса
        $message .= 'Status: ' . strtoupper($command) . "\n";
        
        return trim($message);
    }

    /**
     * Генерирует все комбинации полей и их значений
     */
    public function generateFieldCombinations(array $fields): array
    {
        $combinations = [];
        
        // Одиночные поля
        foreach ($fields as $field) {
            foreach (self::FIELD_VALUES[$field] as $value) {
                $combinations[] = [$field => $value];
            }
        }
        
        // Парные комбинации
        for ($i = 0; $i < count($fields); $i++) {
            for ($j = $i + 1; $j < count($fields); $j++) {
                $field1 = $fields[$i];
                $field2 = $fields[$j];
                
                foreach (self::FIELD_VALUES[$field1] as $value1) {
                    foreach (self::FIELD_VALUES[$field2] as $value2) {
                        $combinations[] = [$field1 => $value1, $field2 => $value2];
                    }
                }
            }
        }
        
        // Тройные комбинации и более (ограничиваем для производительности)
        // Можно расширить при необходимости
        
        return $combinations;
    }

    /**
     * Генерирует все возможные перестановки полей
     */
    public function generateFieldPermutations(array $fields): array
    {
        if (count($fields) <= 1) {
            return [$fields];
        }
        
        $permutations = [];
        
        for ($i = 0; $i < count($fields); $i++) {
            $rest = array_slice($fields, 0, $i) + array_slice($fields, $i + 1);
            $subPermutations = $this->generateFieldPermutations($rest);
            
            foreach ($subPermutations as $perm) {
                $permutations[] = array_merge([$fields[$i]], $perm);
            }
        }
        
        return $permutations;
    }

    /**
     * Проверяет валидность комбинации cap/geo/funnel
     */
    public function validateCapGeoFunnelCombination(array $caps, array $geoOrFunnel): bool
    {
        // Количество cap и geo/funnel должно совпадать
        if (count($caps) != count($geoOrFunnel)) {
            return false;
        }
        
        // Все значения должны быть непустыми
        foreach ($caps as $cap) {
            if (empty($cap)) return false;
        }
        
        foreach ($geoOrFunnel as $item) {
            if (empty($item)) return false;
        }
        
        return true;
    }

    /**
     * Генерирует базовый набор полей для капы
     */
    public function generateBaseCapFields(): array
    {
        return [
            'affiliate' => self::FIELD_VALUES['affiliate'][0],
            'recipient' => self::FIELD_VALUES['recipient'][0],
            'cap' => self::FIELD_VALUES['cap'][0],
            'geo' => self::FIELD_VALUES['geo'][0],
            'schedule' => self::FIELD_VALUES['schedule'][0]
        ];
    }

    /**
     * Генерирует минимальный набор полей для идентификации
     */
    public function generateMinimalIdentifierFields(): array
    {
        return [
            'affiliate' => self::FIELD_VALUES['affiliate'][0],
            'recipient' => self::FIELD_VALUES['recipient'][0],
            'geo' => self::FIELD_VALUES['geo'][0]
        ];
    }

    /**
     * Получает все типы операций
     */
    public function getOperationTypes(): array
    {
        return self::OPERATION_TYPES;
    }

    /**
     * Получает все команды статуса
     */
    public function getStatusCommands(): array
    {
        return self::STATUS_COMMANDS;
    }

    /**
     * Получает поля, которые можно сбрасывать
     */
    public function getResettableFields(): array
    {
        return self::RESETTABLE_FIELDS;
    }

    /**
     * Получает значения по умолчанию
     */
    public function getDefaultValues(): array
    {
        return self::DEFAULT_VALUES;
    }

    /**
     * Получает все поля системы
     */
    public function getAllFields(): array
    {
        return self::ALL_FIELDS;
    }

    /**
     * Получает возможные значения для поля
     */
    public function getFieldValues(string $field): array
    {
        return self::FIELD_VALUES[$field] ?? [];
    }

    /**
     * Нормализует текст для сравнения
     */
    public function normalizeText(string $text): string
    {
        return mb_strtolower(trim($text));
    }

    /**
     * Форматирует имя поля для использования в сообщениях
     * Конвертирует подчеркивания в пробелы и применяет правильную капитализацию
     */
    private function formatFieldName(string $field): string
    {
        // Специальные случаи для полей с подчеркиваниями
        $specialCases = [
            'pending_acq' => 'Pending ACQ',
            'freeze_status_on_acq' => 'Freeze status on ACQ',
        ];
        
        // Если есть специальный случай, используем его
        if (isset($specialCases[$field])) {
            return $specialCases[$field];
        }
        
        // Для остальных полей: заменяем подчеркивания на пробелы и капитализируем
        return ucfirst(str_replace('_', ' ', $field));
    }
} 