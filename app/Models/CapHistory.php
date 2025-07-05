<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapHistory extends Model
{
    use HasFactory;

    protected $table = 'cap_history';

    protected $fillable = [
        'cap_id',
        'source_message_id',
        'target_message_id',
        'match_key',
        'old_values',
        'new_values',
        'changed_fields',
        'action'
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'changed_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Связь с капой
     */
    public function cap()
    {
        return $this->belongsTo(Cap::class);
    }

    /**
     * Связь с исходным сообщением (которое содержит новые настройки)
     */
    public function sourceMessage()
    {
        return $this->belongsTo(Message::class, 'source_message_id');
    }

    /**
     * Связь с целевым сообщением (которое было обновлено)
     */
    public function targetMessage()
    {
        return $this->belongsTo(Message::class, 'target_message_id');
    }

    /**
     * Получение истории для определенной капы
     */
    public static function getCapHistory($capId)
    {
        return self::where('cap_id', $capId)
            ->with(['sourceMessage.chat', 'targetMessage.chat'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Получение истории для определенного match_key
     */
    public static function getMatchKeyHistory($matchKey)
    {
        return self::where('match_key', $matchKey)
            ->with(['cap.message.chat', 'sourceMessage.chat', 'targetMessage.chat'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Создание записи в истории
     */
    public static function createHistoryEntry($capId, $sourceMessageId, $targetMessageId, $matchKey, $oldValues, $newValues, $action = 'updated')
    {
        // Определяем какие поля изменились
        $changedFields = [];
        
        if (is_array($oldValues) && is_array($newValues)) {
            foreach ($newValues as $field => $newValue) {
                $oldValue = $oldValues[$field] ?? null;
                
                // Сравнение значений с учетом массивов
                if (is_array($newValue) && is_array($oldValue)) {
                    if (json_encode($newValue) !== json_encode($oldValue)) {
                        $changedFields[] = $field;
                    }
                } elseif ($newValue !== $oldValue) {
                    $changedFields[] = $field;
                }
            }
        }

        return self::create([
            'cap_id' => $capId,
            'source_message_id' => $sourceMessageId,
            'target_message_id' => $targetMessageId,
            'match_key' => $matchKey,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'changed_fields' => $changedFields,
            'action' => $action
        ]);
    }
} 