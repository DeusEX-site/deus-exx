<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cap extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'cap_amounts',
        'total_amount',
        'schedule',
        'date',
        'is_24_7',
        'affiliate_name',
        'broker_name',
        'geos',
        'work_hours',
        'highlighted_text'
    ];

    protected $casts = [
        'cap_amounts' => 'array',
        'geos' => 'array',
        'is_24_7' => 'boolean',
        'total_amount' => 'integer'
    ];

    /**
     * Связь с сообщением
     */
    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * Связь с историей изменений
     */
    public function history()
    {
        return $this->hasMany(CapHistory::class);
    }

    /**
     * Поиск кап по параметрам
     */
    public static function searchCaps($search = null, $chatId = null)
    {
        $query = self::with(['message' => function($q) {
            $q->with('chat');
        }]);

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
                  ->orWhere('broker_name', 'LIKE', "%{$search}%")
                  ->orWhere('schedule', 'LIKE', "%{$search}%")
                  ->orWhereHas('message', function($subQ) use ($search) {
                      $subQ->where('message', 'LIKE', "%{$search}%");
                  });
            });
        }

        return $query->orderBy('created_at', 'desc');
    }
} 