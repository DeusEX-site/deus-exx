<?php

namespace App\Services;

use App\Models\Cap;
use App\Models\CapHistory;
use App\Models\Message;
use Illuminate\Support\Facades\DB;

class CapUpdateService
{
    private $capAnalysisService;

    public function __construct(CapAnalysisService $capAnalysisService)
    {
        $this->capAnalysisService = $capAnalysisService;
    }

    /**
     * Обработать новое сообщение с обновлением существующих кап
     */
    public function processNewMessage($messageId, $messageText)
    {
        // Сначала анализируем новое сообщение
        $newCapEntries = $this->capAnalysisService->extractSeparateCapEntries($messageText);
        
        $updatedCaps = [];
        $newCaps = [];

        DB::transaction(function() use ($messageId, $messageText, $newCapEntries, &$updatedCaps, &$newCaps) {
            foreach ($newCapEntries as $newCapEntry) {
                // Ищем существующие капы по совпадению aff, brok, geo
                $existingCap = $this->findMatchingCap(
                    $newCapEntry['affiliate_name'],
                    $newCapEntry['broker_name'],
                    $newCapEntry['geos']
                );

                if ($existingCap) {
                    // Обновляем существующую капу
                    $reason = "Обновлено по совпадению aff: {$newCapEntry['affiliate_name']}, brok: {$newCapEntry['broker_name']}, geo: " . implode(',', $newCapEntry['geos'] ?? []);
                    
                    $existingCap->updateWithHistory([
                        'cap_amounts' => [$newCapEntry['cap_amount']],
                        'total_amount' => $newCapEntry['total_amount'],
                        'schedule' => $newCapEntry['schedule'],
                        'date' => $newCapEntry['date'],
                        'is_24_7' => $newCapEntry['is_24_7'],
                        'work_hours' => $newCapEntry['work_hours'],
                        'highlighted_text' => $newCapEntry['highlighted_text']
                    ], $reason);

                    $updatedCaps[] = $existingCap;
                } else {
                    // Создаем новую капу
                    $newCap = Cap::create([
                        'message_id' => $messageId,
                        'cap_amounts' => [$newCapEntry['cap_amount']],
                        'total_amount' => $newCapEntry['total_amount'],
                        'schedule' => $newCapEntry['schedule'],
                        'date' => $newCapEntry['date'],
                        'is_24_7' => $newCapEntry['is_24_7'],
                        'affiliate_name' => $newCapEntry['affiliate_name'],
                        'broker_name' => $newCapEntry['broker_name'],
                        'geos' => $newCapEntry['geos'],
                        'work_hours' => $newCapEntry['work_hours'],
                        'highlighted_text' => $newCapEntry['highlighted_text']
                    ]);

                    $newCaps[] = $newCap;
                }
            }
        });

        return [
            'updated_caps' => count($updatedCaps),
            'new_caps' => count($newCaps),
            'total_entries' => count($newCapEntries)
        ];
    }

    /**
     * Найти существующую капу по совпадению aff, brok, geo
     */
    private function findMatchingCap($affiliateName, $brokerName, $geos)
    {
        if (!$affiliateName || !$brokerName || empty($geos)) {
            return null;
        }

        // Точное совпадение по всем трем параметрам
        $query = Cap::where('affiliate_name', $affiliateName)
                    ->where('broker_name', $brokerName);

        // Проверяем совпадение гео (хотя бы одно совпадение)
        foreach ($geos as $geo) {
            $query->orWhereJsonContains('geos', $geo);
        }

        // Возвращаем первую найденную запись
        return $query->orderBy('created_at', 'desc')->first();
    }

    /**
     * Найти точное совпадение капы (все параметры)
     */
    private function findExactMatch($affiliateName, $brokerName, $geos)
    {
        if (!$affiliateName || !$brokerName || empty($geos)) {
            return null;
        }

        $caps = Cap::where('affiliate_name', $affiliateName)
                   ->where('broker_name', $brokerName)
                   ->get();

        foreach ($caps as $cap) {
            // Проверяем полное совпадение гео
            if ($this->arraysEqual($cap->geos ?? [], $geos)) {
                return $cap;
            }
        }

        return null;
    }

    /**
     * Проверить равенство массивов (порядок не важен)
     */
    private function arraysEqual($arr1, $arr2)
    {
        if (count($arr1) !== count($arr2)) {
            return false;
        }

        sort($arr1);
        sort($arr2);

        return $arr1 === $arr2;
    }

    /**
     * Получить все обновления для конкретной капы
     */
    public function getCapUpdates($capId, $includeHidden = false)
    {
        return CapHistory::getHistoryForCap($capId, $includeHidden);
    }

    /**
     * Получить историю обновлений с фильтрами
     */
    public function getUpdateHistory($affiliate = null, $broker = null, $geo = null, $includeHidden = false)
    {
        return CapHistory::searchHistory($affiliate, $broker, $geo, $includeHidden);
    }

    /**
     * Показать/скрыть запись истории
     */
    public function toggleHistoryVisibility($historyId)
    {
        $history = CapHistory::find($historyId);
        if ($history) {
            return $history->toggleVisibility();
        }
        return null;
    }

    /**
     * Получить статистику обновлений
     */
    public function getUpdateStatistics()
    {
        $stats = [
            'total_caps' => Cap::count(),
            'total_history_records' => CapHistory::count(),
            'updates_today' => CapHistory::whereDate('created_at', now()->toDateString())
                                       ->where('action_type', 'updated')
                                       ->count(),
            'new_caps_today' => CapHistory::whereDate('created_at', now()->toDateString())
                                          ->where('action_type', 'created')
                                          ->count(),
            'hidden_records' => CapHistory::where('is_hidden', true)->count(),
            'visible_records' => CapHistory::where('is_hidden', false)->count()
        ];

        return $stats;
    }

    /**
     * Получить последние обновления
     */
    public function getRecentUpdates($limit = 10, $includeHidden = false)
    {
        $query = CapHistory::with(['message' => function($q) {
            $q->with('chat');
        }])
        ->orderBy('created_at', 'desc');

        if (!$includeHidden) {
            $query->where('is_hidden', false);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Пакетное обновление видимости истории
     */
    public function bulkUpdateVisibility($historyIds, $isHidden)
    {
        return CapHistory::whereIn('id', $historyIds)
                         ->update(['is_hidden' => $isHidden]);
    }

    /**
     * Найти потенциальные дубликаты кап
     */
    public function findPotentialDuplicates()
    {
        $caps = Cap::all();
        $duplicates = [];

        foreach ($caps as $cap) {
            $matches = Cap::where('id', '!=', $cap->id)
                         ->where('affiliate_name', $cap->affiliate_name)
                         ->where('broker_name', $cap->broker_name)
                         ->get();

            foreach ($matches as $match) {
                if ($this->arraysEqual($cap->geos ?? [], $match->geos ?? [])) {
                    $duplicates[] = [
                        'original' => $cap,
                        'duplicate' => $match
                    ];
                }
            }
        }

        return $duplicates;
    }

    /**
     * Объединить дубликаты кап
     */
    public function mergeDuplicates($originalId, $duplicateId)
    {
        $original = Cap::find($originalId);
        $duplicate = Cap::find($duplicateId);

        if (!$original || !$duplicate) {
            return false;
        }

        DB::transaction(function() use ($original, $duplicate) {
            // Переносим историю дубликата к оригиналу
            CapHistory::where('cap_id', $duplicate->id)
                     ->update(['cap_id' => $original->id]);

            // Создаем запись об объединении
            CapHistory::create([
                'cap_id' => $original->id,
                'message_id' => $original->message_id,
                'action_type' => 'merged',
                'reason' => "Объединен дубликат ID: {$duplicate->id}",
                'updated_by' => 'system',
                'is_hidden' => true
            ]);

            // Удаляем дубликат
            $duplicate->delete();
        });

        return true;
    }
} 