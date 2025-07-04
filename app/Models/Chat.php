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
            CASE 
                WHEN display_order > 0 THEN display_order
                ELSE 999999 + id  
            END ASC
        ');
    }

    public function scopeTopTen($query)
    {
        return $query->where('display_order', '>', 0)
                     ->orderBy('display_order', 'asc')
                     ->limit(10);
    }

    public function scopeWithoutTopTen($query)
    {
        return $query->where('display_order', 0);
    }

    public function isInTopTen()
    {
        return $this->display_order > 0;
    }

    public function getTopTenPosition()
    {
        if (!$this->isInTopTen()) {
            return false;
        }
        
        $position = static::active()
                          ->where('display_order', '>', 0)
                          ->where('display_order', '<', $this->display_order)
                          ->count();
        
        return $position + 1;
    }
} 