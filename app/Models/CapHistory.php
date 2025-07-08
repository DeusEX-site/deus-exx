<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CapHistory extends Model
{
    use HasFactory;

    protected $table = 'caps_history';

    protected $fillable = [
        'cap_id',
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
        'status_updated_at',
        'archived_at'
    ];

    protected $casts = [
        'cap_amounts' => 'array',
        'geos' => 'array',
        'is_24_7' => 'boolean',
        'total_amount' => 'integer',
        'pending_acq' => 'boolean',
        'freeze_status_on_acq' => 'boolean',
        'status_updated_at' => 'datetime',
        'archived_at' => 'datetime'
    ];

    /**
     * Связь с основной записью капы
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
     * Связь с оригинальным сообщением (для обновлений)
     */
    public function originalMessage()
    {
        return $this->belongsTo(Message::class, 'original_message_id');
    }

    /**
     * Создать запись истории из существующей капы
     */
    public static function createFromCap(Cap $cap)
    {
        return self::create([
            'cap_id' => $cap->id,
            'message_id' => $cap->message_id,
            'original_message_id' => $cap->original_message_id,
            'cap_amounts' => $cap->cap_amounts,
            'total_amount' => $cap->total_amount,
            'schedule' => $cap->schedule,
            'date' => $cap->date,
            'is_24_7' => $cap->is_24_7,
            'affiliate_name' => $cap->affiliate_name,
            'recipient_name' => $cap->recipient_name,
            'geos' => $cap->geos,
            'work_hours' => $cap->work_hours,
            'start_time' => $cap->start_time,
            'end_time' => $cap->end_time,
            'timezone' => $cap->timezone,
            'language' => $cap->language,
            'funnel' => $cap->funnel,
            'pending_acq' => $cap->pending_acq,
            'freeze_status_on_acq' => $cap->freeze_status_on_acq,
            'highlighted_text' => $cap->highlighted_text,
            'status' => $cap->status,
            'status_updated_at' => $cap->status_updated_at,
            'archived_at' => now()
        ]);
    }
} 