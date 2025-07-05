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
        'message_id',
        'action_type',
        'old_values',
        'cap_amounts',
        'total_amount',
        'schedule',
        'date',
        'is_24_7',
        'affiliate_name',
        'broker_name',
        'geos',
        'work_hours',
        'highlighted_text',
        'reason',
        'updated_by',
        'is_hidden'
    ];

    protected $casts = [
        'old_values' => 'array',
        'cap_amounts' => 'array',
        'geos' => 'array',
        'is_24_7' => 'boolean',
        'is_hidden' => 'boolean',
        'total_amount' => 'integer'
    ];

    /**
     * Связь с основной записью cap
     */
    public function cap()
    {
        return $this->belongsTo(Cap::class);
    }

    /**
     * Связь с сообщением
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Создание записи истории для новой капы
     */
    public static function createForNewCap(Cap $cap, $reason = null)
    {
        return self::create([
            'cap_id' => $cap->id,
            'message_id' => $cap->message_id,
            'action_type' => 'created',
            'cap_amounts' => $cap->cap_amounts,
            'total_amount' => $cap->total_amount,
            'schedule' => $cap->schedule,
            'date' => $cap->date,
            'is_24_7' => $cap->is_24_7,
            'affiliate_name' => $cap->affiliate_name,
            'broker_name' => $cap->broker_name,
            'geos' => $cap->geos,
            'work_hours' => $cap->work_hours,
            'highlighted_text' => $cap->highlighted_text,
            'reason' => $reason ?: 'Initial cap creation',
            'updated_by' => 'system',
            'is_hidden' => false // Показываем изначальное создание
        ]);
    }

    /**
     * Создание записи истории для обновления капы
     */
    public static function createForCapUpdate(Cap $cap, $oldValues, $reason = null)
    {
        return self::create([
            'cap_id' => $cap->id,
            'message_id' => $cap->message_id,
            'action_type' => 'updated',
            'old_values' => $oldValues,
            'cap_amounts' => $cap->cap_amounts,
            'total_amount' => $cap->total_amount,
            'schedule' => $cap->schedule,
            'date' => $cap->date,
            'is_24_7' => $cap->is_24_7,
            'affiliate_name' => $cap->affiliate_name,
            'broker_name' => $cap->broker_name,
            'geos' => $cap->geos,
            'work_hours' => $cap->work_hours,
            'highlighted_text' => $cap->highlighted_text,
            'reason' => $reason ?: 'Cap updated by matching message',
            'updated_by' => 'system',
            'is_hidden' => true // Скрываем изменения по умолчанию
        ]);
    }

    /**
     * Создание записи истории для замены капы
     */
    public static function createForCapReplacement(Cap $cap, $oldValues, $reason = null)
    {
        return self::create([
            'cap_id' => $cap->id,
            'message_id' => $cap->message_id,
            'action_type' => 'replaced',
            'old_values' => $oldValues,
            'cap_amounts' => $cap->cap_amounts,
            'total_amount' => $cap->total_amount,
            'schedule' => $cap->schedule,
            'date' => $cap->date,
            'is_24_7' => $cap->is_24_7,
            'affiliate_name' => $cap->affiliate_name,
            'broker_name' => $cap->broker_name,
            'geos' => $cap->geos,
            'work_hours' => $cap->work_hours,
            'highlighted_text' => $cap->highlighted_text,
            'reason' => $reason ?: 'Cap replaced by matching message',
            'updated_by' => 'system',
            'is_hidden' => true // Скрываем замены по умолчанию
        ]);
    }

    /**
     * Получить историю для конкретной капы
     */
    public static function getHistoryForCap($capId, $includeHidden = false)
    {
        $query = self::where('cap_id', $capId)
                    ->with(['message' => function($q) {
                        $q->with('chat');
                    }])
                    ->orderBy('created_at', 'desc');

        if (!$includeHidden) {
            $query->where('is_hidden', false);
        }

        return $query->get();
    }

    /**
     * Получить историю для поиска (по aff, brok, geo)
     */
    public static function searchHistory($affiliate = null, $broker = null, $geo = null, $includeHidden = false)
    {
        $query = self::with(['message' => function($q) {
            $q->with('chat');
        }]);

        if ($affiliate) {
            $query->where('affiliate_name', 'LIKE', "%{$affiliate}%");
        }

        if ($broker) {
            $query->where('broker_name', 'LIKE', "%{$broker}%");
        }

        if ($geo) {
            $query->whereJsonContains('geos', $geo);
        }

        if (!$includeHidden) {
            $query->where('is_hidden', false);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Показать/скрыть запись истории
     */
    public function toggleVisibility()
    {
        $this->is_hidden = !$this->is_hidden;
        $this->save();
        return $this;
    }

    /**
     * Получить форматированное описание изменений
     */
    public function getChangeDescription()
    {
        switch ($this->action_type) {
            case 'created':
                return "Создана новая капа {$this->affiliate_name} - {$this->broker_name}";
            
            case 'updated':
                $changes = [];
                if ($this->old_values) {
                    foreach ($this->old_values as $field => $oldValue) {
                        $newValue = $this->getAttribute($field);
                        if ($oldValue !== $newValue) {
                            $changes[] = "{$field}: {$oldValue} → {$newValue}";
                        }
                    }
                }
                return "Обновлена капа: " . implode(', ', $changes);
            
            case 'replaced':
                return "Заменена капа {$this->affiliate_name} - {$this->broker_name} новыми настройками";
            
            default:
                return "Неизвестное действие";
        }
    }
} 