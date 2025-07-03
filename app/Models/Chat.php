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
        'display_order',
    ];

    protected $casts = [
        'chat_id' => 'integer',
        'is_active' => 'boolean',
        'last_message_at' => 'datetime',
        'message_count' => 'integer',
        'display_order' => 'integer',
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
        return $query->orderByRaw('
            display_order DESC,
            CASE 
                WHEN display_order = 0 THEN last_message_at 
                ELSE "1970-01-01" 
            END DESC
        ');
    }

    public function scopeTopThree($query)
    {
        return $query->orderBy('display_order', 'desc')
                     ->orderBy('last_message_at', 'desc')
                     ->limit(3);
    }

    public function scopeWithoutTopThree($query)
    {
        $topThreeIds = static::active()
                          ->orderBy('display_order', 'desc')
                          ->orderBy('last_message_at', 'desc')
                          ->limit(3)
                          ->pluck('id');
        
        return $query->whereNotIn('id', $topThreeIds);
    }

    public function isInTopThree()
    {
        $topThreeIds = static::active()
                          ->orderBy('display_order', 'desc')
                          ->orderBy('last_message_at', 'desc')
                          ->limit(3)
                          ->pluck('id');
        
        return $topThreeIds->contains($this->id);
    }

    public function getTopThreePosition()
    {
        $topThreeIds = static::active()
                          ->orderBy('display_order', 'desc')
                          ->orderBy('last_message_at', 'desc')
                          ->limit(3)
                          ->pluck('id');
        
        return $topThreeIds->search($this->id);
    }
} 