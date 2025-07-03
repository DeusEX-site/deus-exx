<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'type',
        'title',
        'username',
        'description',
        'is_active',
        'last_message_at',
        'message_count',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function getDisplayNameAttribute()
    {
        return $this->title ?: $this->username ?: 'Чат #' . $this->chat_id;
    }

    public function getTypeDisplayAttribute()
    {
        return match($this->type) {
            'private' => 'Приватный',
            'group' => 'Группа',
            'supergroup' => 'Супергруппа',
            'channel' => 'Канал',
            default => 'Неизвестно'
        };
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrderByActivity($query)
    {
        return $query->orderBy('last_message_at', 'desc');
    }
} 