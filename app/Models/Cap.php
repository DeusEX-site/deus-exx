<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cap extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'original_message_id',
        'cap_amounts',
        'total_amount',
        'schedule',
        'date',
        'is_24_7',
        'affiliate_name',
        'recipient_name',
        'geos',
        'work_hours',
        'start_time',
        'end_time',
        'timezone',
        'language',
        'funnel',
        'pending_acq',
        'freeze_status_on_acq',
        'highlighted_text',
        'status',
        'status_updated_at'
    ];

    protected $casts = [
        'cap_amounts' => 'array',
        'geos' => 'array',
        'is_24_7' => 'boolean',
        'total_amount' => 'integer',
        'pending_acq' => 'boolean',
        'freeze_status_on_acq' => 'boolean',
        'status_updated_at' => 'datetime'
    ];

    /**
     * Связь с сообщением
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Связь с сообщением, которое обновило капу
     */
    public function originalMessage()
    {
        return $this->belongsTo(Message::class, 'original_message_id');
    }

    /**
     * Связь с историей кап
     */
    public function history()
    {
        return $this->hasMany(CapHistory::class);
    }

    /**
     * Найти дублирующую капу по ключевым полям (только активные)
     */
    public static function findDuplicate($affiliateName, $recipientName, $geo)
    {
        return self::where('affiliate_name', $affiliateName)
                   ->where('recipient_name', $recipientName)
                   ->whereJsonContains('geos', $geo)
                   ->whereIn('status', ['RUN', 'STOP']) // DELETE не участвует в поиске дубликатов
                   ->first();
    }

    /**
     * Изменить статус капы
     */
    public function updateStatus($status)
    {
        $this->update([
            'status' => $status,
            'status_updated_at' => now()
        ]);
    }

    /**
     * Scope для активных кап (только RUN)
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'RUN');
    }

    /**
     * Scope для всех кап кроме удаленных
     */
    public function scopeNotDeleted($query)
    {
        return $query->where('status', '!=', 'DELETE');
    }

    /**
     * Проверка является ли капа активной
     */
    public function isActive()
    {
        return $this->status === 'RUN';
    }

    /**
     * Проверка является ли капа остановленной
     */
    public function isStopped()
    {
        return $this->status === 'STOP';
    }

    /**
     * Проверка является ли капа удаленной
     */
    public function isDeleted()
    {
        return $this->status === 'DELETE';
    }

    /**
     * Поиск кап по параметрам (по умолчанию RUN и STOP, скрываем DELETE)
     */
    public static function searchCaps($search = null, $chatId = null, $includeInactive = false)
    {
        $query = self::with(['message' => function($q) {
            $q->with('chat');
        }]);

        // По умолчанию показываем активные и остановленные, скрываем удаленные
        if (!$includeInactive) {
            $query->whereIn('status', ['RUN', 'STOP']);
        }

        // Фильтр по чату
        if ($chatId) {
            $query->whereHas('message', function($q) use ($chatId) {
                $q->where('chat_id', $chatId);
            });
        }

        // Поиск по тексту
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('affiliate_name', 'LIKE', "%{$search}%")
                  ->orWhere('recipient_name', 'LIKE', "%{$search}%")
                  ->orWhere('schedule', 'LIKE', "%{$search}%")
                  ->orWhere('language', 'LIKE', "%{$search}%")
                  ->orWhere('funnel', 'LIKE', "%{$search}%")
                  ->orWhereHas('message', function($subQ) use ($search) {
                      $subQ->where('message', 'LIKE', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }
} 