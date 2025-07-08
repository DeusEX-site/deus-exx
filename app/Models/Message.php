<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'message',
        'user',
        'message_id', // For compatibility with tests
        'telegram_message_id',
        'reply_to_message_id',
        'telegram_user_id',
        'telegram_username',
        'telegram_first_name',
        'telegram_last_name',
        'telegram_date',
        'message_type',
        'telegram_raw_data',
        'is_outgoing',
        'display_name',
        'user_id', // For compatibility with tests
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'telegram_message_id' => 'integer',
        'telegram_user_id' => 'integer',
        'telegram_date' => 'datetime',
        'telegram_raw_data' => 'array',
        'is_outgoing' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    /**
     * Связь с капами
     */
    public function caps()
    {
        return $this->hasMany(Cap::class);
    }

    /**
     * Связь с сообщением, на которое отвечают
     */
    public function replyToMessage()
    {
        return $this->belongsTo(Message::class, 'reply_to_message_id');
    }

    /**
     * Связь с ответами на это сообщение
     */
    public function replies()
    {
        return $this->hasMany(Message::class, 'reply_to_message_id');
    }

    public function getDisplayNameAttribute()
    {
        // Приоритет: сохраненное display_name -> telegram поля -> user -> по умолчанию
        if ($this->attributes['display_name']) {
            return $this->attributes['display_name'];
        }
        
        if ($this->telegram_first_name || $this->telegram_last_name) {
            return trim($this->telegram_first_name . ' ' . $this->telegram_last_name);
        }
        
        if ($this->telegram_username) {
            return '@' . $this->telegram_username;
        }
        
        return $this->user ?: 'Пользователь #' . $this->telegram_user_id;
    }

    public function getMessageTypeDisplayAttribute()
    {
        return match($this->message_type) {
            'text' => 'Текст',
            'photo' => 'Фото',
            'document' => 'Документ',
            'video' => 'Видео',
            'audio' => 'Аудио',
            'voice' => 'Голосовое',
            'sticker' => 'Стикер',
            'location' => 'Геолокация',
            'contact' => 'Контакт',
            default => 'Неизвестно'
        };
    }

    // Возвращаем последние сообщения
    public static function getLatest($limit = 50, $afterId = 0)
    {
        return static::where('id', '>', $afterId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }

    // Возвращаем последние сообщения для конкретного чата
    public static function getLatestForChat($chatId, $limit = 20, $afterId = 0)
    {
        if ($afterId > 0) {
            // Если есть afterId, значит загружаем новые сообщения после определенного ID
            return static::where('chat_id', $chatId)
                ->where('id', '>', $afterId)
                ->orderBy('id', 'asc') // От старых к новым
                ->limit($limit)
                ->get();
        } else {
            // Первоначальная загрузка: берем последние N сообщений
            return static::where('chat_id', $chatId)
                ->orderBy('id', 'desc')
                ->limit($limit)
                ->get()
                ->reverse() // Переворачиваем чтобы были от старых к новым
                ->values();
        }
    }

    // Возвращаем старые сообщения для пагинации (при скролле вверх)
    public static function getOlderForChat($chatId, $beforeId, $limit = 20)
    {
        return static::where('chat_id', $chatId)
            ->where('id', '<', $beforeId)
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get()
            ->reverse() // От старых к новым
            ->values();
    }

    public function scopeFromTelegram($query)
    {
        return $query->whereNotNull('telegram_message_id');
    }

    public function scopeByMessageType($query, $type)
    {
        return $query->where('message_type', $type);
    }
    
    public function scopeOutgoing($query)
    {
        return $query->where('is_outgoing', true);
    }
    
    public function scopeIncoming($query)
    {
        return $query->where('is_outgoing', false);
    }
} 