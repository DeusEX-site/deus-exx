<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Message;
use App\Models\Chat;

class TestCapAnalysis extends Command
{
    protected $signature = 'test:cap-analysis';
    protected $description = 'Test the cap analysis system with sample messages';

    public function handle()
    {
        $this->info('Testing Cap Analysis System...');

        // Создаем тестовые сообщения если их нет
        $this->createTestMessages();

        // Тестируем анализ
        $this->testAnalysis();

        $this->info('Cap analysis test completed!');
    }

    private function createTestMessages()
    {
        // Создаем тестовый чат если его нет
        $chat = Chat::firstOrCreate([
            'chat_id' => -1001234567890,
            'type' => 'supergroup',
            'title' => 'Test Cap Chat',
            'is_active' => true,
        ]);

        // Тестовые сообщения
        $testMessages = [
            'cap 30 XYZ Affiliate - BinaryBroker : DE,FR,UK',
            'сар 50 TestAffiliate - CryptoTrader : RU,UA,KZ 10-18',
            'сар 25 MyAffiliate - ForexPro : US,CA,AU 24/7',
            'cap 100 SuperAffiliate - BinaryOptions : IT,ES,PT 14.05',
            'Общий объем 500 лидов на сегодня',
            'кап 15 RussianAffiliate - TradeMax : RU,BY,KZ 09-17',
            'CAP 75 EnglishAffiliate - TradingPlatform : GB,IE,US 25.12',
            'Нужно 200 лидов до конца месяца',
            'cap 20 TestCompany - BrokerName : FR,DE,NL 24/7',
            'Лимит 300 на сегодня, cap 50 на партнера'
        ];

        foreach ($testMessages as $messageText) {
            Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => 'TestUser',
                'telegram_message_id' => rand(1000, 9999),
                'telegram_user_id' => 123456789,
                'telegram_username' => 'testuser',
                'telegram_first_name' => 'Test',
                'telegram_last_name' => 'User',
                'telegram_date' => now(),
                'message_type' => 'text',
                'is_outgoing' => false,
            ]);
        }

        $this->info('Test messages created.');
    }

    private function testAnalysis()
    {
        // Поиск сообщений с капой
        $capPatterns = [
            'cap', 'сар', 'сар', 'CAP', 'САР', 'САР',
            'кап', 'КАП', 'каП', 'Кап'
        ];

        $query = Message::query();
        $query->where(function($q) use ($capPatterns) {
            foreach ($capPatterns as $pattern) {
                $q->orWhere('message', 'LIKE', "%{$pattern}%");
            }
        });

        $messages = $query->limit(10)->get();

        $this->info("Found {$messages->count()} messages with cap patterns:");
        $this->line('');

        foreach ($messages as $message) {
            $analysis = $this->analyzeCapMessage($message->message);
            
            $this->info("Message: {$message->message}");
            $this->line("Analysis:");
            $this->line("  - Has cap word: " . ($analysis['has_cap_word'] ? 'Yes' : 'No'));
            $this->line("  - Cap amount: " . ($analysis['cap_amount'] ?: 'Not found'));
            $this->line("  - Total amount: " . ($analysis['total_amount'] ?: 'Not found'));
            $this->line("  - Schedule: " . ($analysis['schedule'] ?: 'Not found'));
            $this->line("  - Date: " . ($analysis['date'] ?: 'Not found'));
            $this->line("  - Is 24/7: " . ($analysis['is_24_7'] ? 'Yes' : 'No'));
            $this->line("  - Affiliate: " . ($analysis['affiliate_name'] ?: 'Not found'));
            $this->line("  - Broker: " . ($analysis['broker_name'] ?: 'Not found'));
            $this->line("  - Geos: " . (count($analysis['geos']) > 0 ? implode(', ', $analysis['geos']) : 'Not found'));
            $this->line('');
        }
    }

    private function analyzeCapMessage($message)
    {
        $analysis = [
            'has_cap_word' => false,
            'cap_amount' => null,
            'total_amount' => null,
            'schedule' => null,
            'date' => null,
            'is_24_7' => false,
            'affiliate_name' => null,
            'broker_name' => null,
            'geos' => [],
            'work_hours' => null,
            'raw_numbers' => []
        ];

        // Проверяем наличие слов cap/сар/сар
        $capWords = ['cap', 'сар', 'сар', 'CAP', 'САР', 'САР', 'кап', 'КАП'];
        foreach ($capWords as $word) {
            if (stripos($message, $word) !== false) {
                $analysis['has_cap_word'] = true;
                break;
            }
        }

        // Ищем 24/7
        if (preg_match('/24\/7|24-7/', $message)) {
            $analysis['is_24_7'] = true;
            $analysis['schedule'] = '24/7';
        }

        // Ищем время работы (10-19, 09-18, etc.)
        if (preg_match('/(\d{1,2})-(\d{1,2})/', $message, $matches)) {
            $analysis['work_hours'] = $matches[0];
            $analysis['schedule'] = $matches[0];
        }

        // Ищем даты (14.05, 25.12, etc.)
        if (preg_match('/(\d{1,2})\.(\d{1,2})/', $message, $matches)) {
            $analysis['date'] = $matches[0];
        }

        // Ищем все числа
        preg_match_all('/\b(\d+)\b/', $message, $numbers);
        if (!empty($numbers[1])) {
            $analysis['raw_numbers'] = array_map('intval', $numbers[1]);

            // Анализируем числа
            foreach ($analysis['raw_numbers'] as $number) {
                // Пропускаем очевидные даты и времена
                if ($number > 31 && $number < 2000) {
                    // Ищем cap amount (обычно после слова cap)
                    $capPattern = '/(?:cap|сар|сар|кап)\s*(\d+)/i';
                    if (preg_match($capPattern, $message, $capMatch)) {
                        $analysis['cap_amount'] = intval($capMatch[1]);
                    }

                    // Если число не после cap слова, это может быть total amount
                    if (!$analysis['cap_amount'] || $number > $analysis['cap_amount']) {
                        $analysis['total_amount'] = max($analysis['total_amount'] ?: 0, $number);
                    }
                }
            }
        }

        // Ищем названия (паттерн: название - название)
        if (preg_match('/([a-zA-Zа-яА-Я\s]+)\s*-\s*([a-zA-Zа-яА-Я\s]+)\s*:/', $message, $matches)) {
            $analysis['affiliate_name'] = trim($matches[1]);
            $analysis['broker_name'] = trim($matches[2]);
        }

        // Ищем гео после двоеточия
        if (preg_match('/:(.+)$/m', $message, $matches)) {
            $geoString = trim($matches[1]);
            $geos = array_map('trim', explode(',', $geoString));
            $analysis['geos'] = array_filter($geos, function($geo) {
                return strlen($geo) > 1 && strlen($geo) < 50;
            });
        }

        return $analysis;
    }
} 