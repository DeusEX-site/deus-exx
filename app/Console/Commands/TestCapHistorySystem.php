<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use App\Models\Chat;
use App\Services\CapAnalysisService;

class TestCapHistorySystem extends Command
{
    protected $signature = 'test:cap-history {--cleanup : Удалить тестовые данные после теста}';
    protected $description = 'Тестирует систему истории кап';

    public function handle()
    {
        $this->info('🧪 Тестирование системы истории кап...');
        
        $capAnalysisService = app(CapAnalysisService::class);
        
        // Создаем тестовый чат и сообщения
        $chat = Chat::firstOrCreate([
            'chat_id' => -9999999,
            'type' => 'group',
            'title' => 'Test Cap History Chat'
        ]);
        
        // Шаг 1: Создание первоначальной капы
        $this->info("\n📝 Шаг 1: Создание первоначальных кап");
        
        $originalMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE AT CH
CAP: 20 30 30
Total: 100 200 200
Language: de en ru
Funnel: DeusEX, DeusEX2
Schedule: 18:00/01:00 18:00/02:00 GMT+03:00
Date: 24.02 25.02";

        $message1 = Message::create([
            'chat_id' => $chat->id,
            'message' => $originalMessage,
            'user' => 'Test User',
            'telegram_message_id' => 1001,
            'telegram_user_id' => 123456
        ]);

        $result1 = $capAnalysisService->analyzeAndSaveCapMessage($message1->id, $originalMessage);
        
        $this->info("Создано кап: {$result1['cap_entries_count']}");
        $this->info("Обновлено кап: {$result1['updated_entries_count']}");
        
        $totalCaps = Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->count();
        $this->info("Всего кап в базе: {$totalCaps}");
        
        // Шаг 2: Частичное обновление - только Total
        $this->info("\n🔄 Шаг 2: Частичное обновление капы для DE (только Total)");
        
        $partialUpdateMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE
CAP: 20
Total: 101";

        $message2 = Message::create([
            'chat_id' => $chat->id,
            'message' => $partialUpdateMessage,
            'user' => 'Test User',
            'telegram_message_id' => 1002,
            'telegram_user_id' => 123456
        ]);

        $result2 = $capAnalysisService->analyzeAndSaveCapMessage($message2->id, $partialUpdateMessage);
        
        $this->info("Создано кап: {$result2['cap_entries_count']}");
        $this->info("Обновлено кап: {$result2['updated_entries_count']}");
        
        $totalCapsAfter = Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->count();
        $historyCount = CapHistory::whereHas('cap', function($q) {
            $q->where('affiliate_name', 'G06')->where('recipient_name', 'TMedia');
        })->count();
        
        $this->info("Всего кап в базе после обновления: {$totalCapsAfter}");
        $this->info("Записей в истории: {$historyCount}");
        
        // Проверяем конкретные записи
        $deCap = Cap::where('affiliate_name', 'G06')
                    ->where('recipient_name', 'TMedia')
                    ->whereJsonContains('geos', 'DE')
                    ->first();
                    
        $atCap = Cap::where('affiliate_name', 'G06')
                    ->where('recipient_name', 'TMedia')
                    ->whereJsonContains('geos', 'AT')
                    ->first();
                    
        $chCap = Cap::where('affiliate_name', 'G06')
                    ->where('recipient_name', 'TMedia')
                    ->whereJsonContains('geos', 'CH')
                    ->first();

        // Результаты проверки
        $this->info("\n✅ Результаты проверки:");
        
        if ($deCap && $deCap->total_amount == 101) {
            $this->info("✅ DE капа обновлена (Total: {$deCap->total_amount})");
            
            // Проверяем, что остальные поля не изменились
            if ($deCap->language == 'de' && $deCap->funnel == 'DeusEX') {
                $this->info("✅ DE капа: остальные поля остались без изменений");
            } else {
                $this->error("❌ DE капа: другие поля были изменены неправильно (Language: {$deCap->language}, Funnel: {$deCap->funnel})");
            }
        } else {
            $this->error("❌ DE капа не обновлена правильно");
        }
        
        if ($atCap && $atCap->total_amount == 200) {
            $this->info("✅ AT капа осталась без изменений (Total: {$atCap->total_amount})");
        } else {
            $this->error("❌ AT капа была удалена или изменена");
        }
        
        if ($chCap && $chCap->total_amount == 200) {
            $this->info("✅ CH капа осталась без изменений (Total: {$chCap->total_amount})");
        } else {
            $this->error("❌ CH капа была удалена или изменена");
        }
        
        if ($historyCount == 1) {
            $this->info("✅ Создана 1 запись истории");
        } else {
            $this->error("❌ Неправильное количество записей истории: {$historyCount}");
        }
        
        if ($totalCapsAfter == $totalCaps) {
            $this->info("✅ Общее количество кап не изменилось");
        } else {
            $this->error("❌ Общее количество кап изменилось с {$totalCaps} на {$totalCapsAfter}");
        }
        
        // Шаг 3: Тест сброса полей до значений по умолчанию
        $this->info("\n🔄 Шаг 3: Тест сброса полей до значений по умолчанию");
        $this->info("Примечание: сбрасываются только необязательные поля (Total, Language, Funnel, Schedule, Date)");
        
        $resetFieldsMessage = "Affiliate: G06
Recipient: TMedia
Geo: DE
CAP: 20
Total:
Language:
Funnel:
Schedule:
Date:";

        $message3 = Message::create([
            'chat_id' => $chat->id,
            'message' => $resetFieldsMessage,
            'user' => 'Test User',
            'telegram_message_id' => 1003,
            'telegram_user_id' => 123456
        ]);

        $result3 = $capAnalysisService->analyzeAndSaveCapMessage($message3->id, $resetFieldsMessage);
        
        $this->info("Создано кап: {$result3['cap_entries_count']}");
        $this->info("Обновлено кап: {$result3['updated_entries_count']}");
        
        // Проверяем сброс до значений по умолчанию
        $deCapAfterReset = Cap::where('affiliate_name', 'G06')
                              ->where('recipient_name', 'TMedia')
                              ->whereJsonContains('geos', 'DE')
                              ->first();
        
        if ($deCapAfterReset) {
            $this->info("\n✅ Проверка сброса до значений по умолчанию:");
            
            if ($deCapAfterReset->total_amount == -1) {
                $this->info("✅ Total сброшен до значения по умолчанию (бесконечность: -1)");
            } else {
                $this->error("❌ Total не сброшен правильно: {$deCapAfterReset->total_amount}");
            }
            
            if ($deCapAfterReset->language == 'en') {
                $this->info("✅ Language сброшен до значения по умолчанию (en)");
            } else {
                $this->error("❌ Language не сброшен правильно: {$deCapAfterReset->language}");
            }
            
            if ($deCapAfterReset->funnel === null) {
                $this->info("✅ Funnel сброшен до значения по умолчанию (null)");
            } else {
                $this->error("❌ Funnel не сброшен правильно: {$deCapAfterReset->funnel}");
            }
            
            if ($deCapAfterReset->schedule == '24/7' && $deCapAfterReset->is_24_7) {
                $this->info("✅ Schedule сброшен до значения по умолчанию (24/7)");
            } else {
                $this->error("❌ Schedule не сброшен правильно: {$deCapAfterReset->schedule}");
            }
            
            if ($deCapAfterReset->date === null) {
                $this->info("✅ Date сброшен до значения по умолчанию (null)");
            } else {
                $this->error("❌ Date не сброшен правильно: {$deCapAfterReset->date}");
            }
        }
        
        $historyCountAfterReset = CapHistory::whereHas('cap', function($q) {
            $q->where('affiliate_name', 'G06')->where('recipient_name', 'TMedia');
        })->count();
        
        if ($historyCountAfterReset > $historyCount) {
            $this->info("✅ Создана дополнительная запись истории при сбросе полей");
        } else {
            $this->error("❌ История не создана при сбросе полей");
        }
        
        // Показываем историю
        $this->info("\n📜 История изменений:");
        $history = CapHistory::whereHas('cap', function($q) {
            $q->where('affiliate_name', 'G06')->where('recipient_name', 'TMedia');
        })->with('cap')->orderBy('archived_at')->get();
        
        foreach ($history as $index => $historyRecord) {
            $version = $index + 1;
            $this->info("- Версия {$version}: Гео: {$historyRecord->geos[0]}, Total: {$historyRecord->total_amount}, Language: {$historyRecord->language}, Funnel: {$historyRecord->funnel}, Заархивировано: {$historyRecord->archived_at}");
        }
        
        // Очистка тестовых данных
        if ($this->option('cleanup')) {
            $this->info("\n🧹 Очистка тестовых данных...");
            
            Cap::where('affiliate_name', 'G06')->where('recipient_name', 'TMedia')->delete();
            CapHistory::whereIn('id', $history->pluck('id'))->delete();
            Message::whereIn('id', [$message1->id, $message2->id, $message3->id])->delete();
            $chat->delete();
            
            $this->info("✅ Тестовые данные удалены");
        }
        
        $this->info("\n🎉 Тест завершен!");
        
        return Command::SUCCESS;
    }
} 