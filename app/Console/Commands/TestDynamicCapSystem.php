<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class TestDynamicCapSystem extends Command
{
    protected $signature = 'test:dynamic-cap-system 
                           {type=full : Тип тестирования (full, quick, create, update, status, reset)}
                           {--max-tests=50 : Максимальное количество тестов на тип операции}
                           {--max-combinations=3 : Максимальный размер комбинаций полей}
                           {--max-permutations=12 : Максимальное количество перестановок}
                           {--no-reports : Не сохранять отчеты в файлы}
                           {--no-cleanup : Не очищать тестовые данные после завершения}
                           {--silent : Тихий режим (минимальный вывод)}
                           {--timeout=300 : Таймаут выполнения в секундах}';

    protected $description = 'Запускает динамические тесты системы кап с полным покрытием всех операций';

    public function handle()
    {
        $this->info('🚀 Запуск динамических тестов системы кап...');
        $this->info('');
        
        // Подключаем необходимые файлы
        $this->loadDynamicTestSystem();
        
        // Настраиваем конфигурацию
        $config = $this->buildConfig();
        
        // Выводим информацию о конфигурации
        $this->displayConfiguration($config);
        
        // Создаем экземпляр тестового раннера
        $runner = new \DynamicCapTestRunner($config);
        
        // Запускаем тестирование в зависимости от типа
        $type = $this->argument('type');
        $this->runTestsByType($runner, $type);
        
        $this->info('');
        $this->info('✅ Динамические тесты завершены!');
        
        return Command::SUCCESS;
    }

    /**
     * Подключает файлы системы динамических тестов
     */
    private function loadDynamicTestSystem(): void
    {
        $baseDir = base_path();
        $files = [
            'DynamicCapTestGenerator.php',
            'DynamicCapTestEngine.php',
            'DynamicCapCombinationGenerator.php',
            'DynamicCapReportGenerator.php',
            'dynamic_cap_test_runner.php'
        ];
        
        foreach ($files as $file) {
            $filepath = $baseDir . '/' . $file;
            if (!file_exists($filepath)) {
                $this->error("❌ Файл {$file} не найден в корне проекта");
                $this->error("Убедитесь, что все файлы системы динамических тестов находятся в корне проекта");
                exit(1);
            }
            require_once $filepath;
        }
    }

    /**
     * Создает конфигурацию для тестирования
     */
    private function buildConfig(): array
    {
        return [
            'skip_laravel_init' => true, // Пропускаем инициализацию Laravel, т.к. уже в Artisan
            'verbose' => !$this->option('silent'),
            'save_reports' => !$this->option('no-reports'),
            'cleanup_after_test' => !$this->option('no-cleanup'),
            'test_types' => $this->getTestTypes(),
            'max_tests_per_type' => (int)$this->option('max-tests'),
            'max_combination_size' => (int)$this->option('max-combinations'),
            'max_permutations' => (int)$this->option('max-permutations'),
            'test_timeout' => (int)$this->option('timeout')
        ];
    }

    /**
     * Определяет типы тестов для запуска
     */
    private function getTestTypes(): string
    {
        $type = $this->argument('type');
        
        switch ($type) {
            case 'create':
                return 'create_only';
            case 'update':
                return 'update_only';
            case 'status':
                return 'status_only';
            case 'quick':
            case 'full':
            default:
                return 'all';
        }
    }

    /**
     * Выводит информацию о конфигурации
     */
    private function displayConfiguration(array $config): void
    {
        $this->info('📋 Конфигурация тестирования:');
        $this->line("   • Тип тестов: {$config['test_types']}");
        $this->line("   • Максимум тестов на тип: {$config['max_tests_per_type']}");
        $this->line("   • Максимум комбинаций: {$config['max_combination_size']}");
        $this->line("   • Максимум перестановок: {$config['max_permutations']}");
        $this->line("   • Таймаут: {$config['test_timeout']} сек");
        $this->line("   • Сохранение отчетов: " . ($config['save_reports'] ? 'Да' : 'Нет'));
        $this->line("   • Очистка после тестов: " . ($config['cleanup_after_test'] ? 'Да' : 'Нет'));
        $this->line("   • Режим вывода: " . ($config['verbose'] ? 'Подробный' : 'Тихий'));
        $this->info('');
    }

    /**
     * Запускает тестирование в зависимости от типа
     */
    private function runTestsByType($runner, string $type): void
    {
        switch ($type) {
            case 'quick':
                $this->info('⚡ Запуск быстрого тестирования...');
                $runner->runQuickTest();
                break;
                
            case 'status':
                $this->info('🔧 Запуск тестирования команд статуса...');
                $runner->runStatusCommandTests();
                break;
                
            case 'reset':
                $this->info('🔄 Запуск тестирования сброса полей...');
                $runner->runFieldResetTests();
                break;
                
            case 'stats':
                $this->info('📊 Получение статистики тестов...');
                $this->displayTestStatistics($runner);
                break;
                
            case 'full':
            case 'create':
            case 'update':
            default:
                $this->info('🎯 Запуск полного набора тестов...');
                $runner->runFullTestSuite();
                break;
        }
    }

    /**
     * Выводит статистику тестов
     */
    private function displayTestStatistics($runner): void
    {
        $stats = $runner->getTestStatistics();
        
        $this->info('📈 Статистика планируемых тестов:');
        $this->info('');
        
        $headers = ['Тип операции', 'Количество тестов'];
        $rows = [];
        
        foreach ($stats as $operationType => $count) {
            if ($operationType === 'total') {
                continue;
            }
            
            $rows[] = [
                $this->formatOperationType($operationType),
                number_format($count)
            ];
        }
        
        // Добавляем итоговую строку
        $rows[] = ['', ''];
        $rows[] = ['ИТОГО', number_format($stats['total'])];
        
        $this->table($headers, $rows);
        
        $this->info('');
        $this->warn('⚠️  Это планируемые количества тестов без учета ограничений конфигурации');
        
        if ($stats['total'] > 10000) {
            $this->warn('⚠️  Полное количество тестов очень велико, рекомендуется использовать ограничения');
            $this->warn('    Пример: --max-tests=100 --max-combinations=2 --max-permutations=6');
        }
    }

    /**
     * Форматирует название типа операции для вывода
     */
    private function formatOperationType(string $operationType): string
    {
        $translations = [
            'message_create_single_one' => 'Сообщение: Создание одиночной капы',
            'message_create_single_many' => 'Сообщение: Создание нескольких кап',
            'message_create_group_one' => 'Сообщение: Групповое создание (одна)',
            'message_create_group_many' => 'Сообщение: Групповое создание (много)',
            'message_update_single_one' => 'Сообщение: Обновление одиночной капы',
            'message_update_single_many' => 'Сообщение: Обновление нескольких кап',
            'message_update_group_one' => 'Сообщение: Групповое обновление (одна)',
            'message_update_group_many' => 'Сообщение: Групповое обновление (много)',
            'reply_update_single_one' => 'Ответ: Обновление одиночной капы',
            'reply_update_single_many' => 'Ответ: Обновление нескольких кап',
            'reply_update_group_one' => 'Ответ: Групповое обновление (одна)',
            'reply_update_group_many' => 'Ответ: Групповое обновление (много)',
            'quote_update_single_one' => 'Цитата: Обновление одиночной капы',
            'quote_update_single_many' => 'Цитата: Обновление нескольких кап',
            'quote_update_group_one' => 'Цитата: Групповое обновление (одна)',
            'quote_update_group_many' => 'Цитата: Групповое обновление (много)',
        ];
        
        return $translations[$operationType] ?? $operationType;
    }

    /**
     * Показывает справку по использованию
     */
    public function showHelp(): void
    {
        $this->info('🚀 Система динамических тестов кап');
        $this->info('');
        $this->info('📋 Доступные типы тестирования:');
        $this->line('   • full    - Полный набор всех тестов (по умолчанию)');
        $this->line('   • quick   - Быстрое тестирование (ограниченный набор)');
        $this->line('   • create  - Только тесты создания кап');
        $this->line('   • update  - Только тесты обновления кап');
        $this->line('   • status  - Только тесты команд статуса');
        $this->line('   • reset   - Только тесты сброса полей');
        $this->line('   • stats   - Показать статистику без запуска тестов');
        $this->info('');
        $this->info('🔧 Примеры использования:');
        $this->line('   • php artisan test:dynamic-cap-system');
        $this->line('   • php artisan test:dynamic-cap-system quick');
        $this->line('   • php artisan test:dynamic-cap-system create --max-tests=20');
        $this->line('   • php artisan test:dynamic-cap-system full --max-combinations=2');
        $this->line('   • php artisan test:dynamic-cap-system status --silent');
        $this->line('   • php artisan test:dynamic-cap-system stats');
        $this->info('');
        $this->info('⚙️  Настройки:');
        $this->line('   • --max-tests=N        - Максимум тестов на тип операции');
        $this->line('   • --max-combinations=N - Максимум комбинаций полей');
        $this->line('   • --max-permutations=N - Максимум перестановок');
        $this->line('   • --timeout=N          - Таймаут в секундах');
        $this->line('   • --no-reports         - Не сохранять отчеты');
        $this->line('   • --no-cleanup         - Не очищать тестовые данные');
        $this->line('   • --silent             - Тихий режим');
        $this->info('');
        $this->warn('⚠️  Полное тестирование может занять много времени!');
        $this->warn('    Рекомендуется начать с quick или установить ограничения');
    }
} 