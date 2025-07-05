<?php

namespace App\Services;

use App\Models\Cap;
use App\Models\Message;
use App\Models\Chat;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CapNotificationService
{
    private $botToken;
    private $enabled;
    private $adminChatId;
    
    public function __construct()
    {
        $this->botToken = config('telegram.bot_token');
        $this->enabled = config('telegram.cap_notifications.enabled', true);
        $this->adminChatId = config('telegram.cap_notifications.admin_chat_id');
    }
    
    /**
     * Отправляет уведомление о новой капе
     */
    public function sendNewCapNotification($cap, $sourceMessage)
    {
        if (!$this->enabled || !$this->botToken) {
            return false;
        }
        
        try {
            $text = $this->formatNewCapMessage($cap, $sourceMessage);
            
            // Отправляем в админский чат (если настроен)
            if ($this->adminChatId) {
                $this->sendToChat($this->adminChatId, $text);
            }
            
            // Отправляем в исходный чат
            $originalChat = $sourceMessage->chat;
            if ($originalChat && $originalChat->chat_id) {
                $this->sendToChat($originalChat->chat_id, $text);
            }
            
            Log::info('Cap notification sent', [
                'cap_id' => $cap->id,
                'message_id' => $sourceMessage->id,
                'type' => 'new_cap'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send cap notification', [
                'cap_id' => $cap->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Отправляет уведомление об обновлении капы
     */
    public function sendCapUpdateNotification($cap, $sourceMessage, $oldValues, $newValues, $changedFields)
    {
        if (!$this->enabled || !$this->botToken) {
            return false;
        }
        
        try {
            $text = $this->formatUpdateCapMessage($cap, $sourceMessage, $oldValues, $newValues, $changedFields);
            
            // Отправляем в админский чат (если настроен)
            if ($this->adminChatId) {
                $this->sendToChat($this->adminChatId, $text);
            }
            
            // Отправляем в исходный чат
            $originalChat = $sourceMessage->chat;
            if ($originalChat && $originalChat->chat_id) {
                $this->sendToChat($originalChat->chat_id, $text);
            }
            
            // Отправляем в чат где была обновлена капа (если отличается)
            $targetChat = $cap->message->chat;
            if ($targetChat && $targetChat->chat_id && $targetChat->chat_id !== $originalChat->chat_id) {
                $updateText = $this->formatTargetUpdateMessage($cap, $sourceMessage, $oldValues, $newValues, $changedFields);
                $this->sendToChat($targetChat->chat_id, $updateText);
            }
            
            Log::info('Cap update notification sent', [
                'cap_id' => $cap->id,
                'source_message_id' => $sourceMessage->id,
                'changed_fields' => $changedFields,
                'type' => 'cap_update'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send cap update notification', [
                'cap_id' => $cap->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Отправляет уведомление о том, что данные не изменились
     */
    public function sendUnchangedCapNotification($cap, $sourceMessage)
    {
        if (!$this->enabled || !$this->botToken) {
            return false;
        }
        
        try {
            $text = $this->formatUnchangedCapMessage($cap, $sourceMessage);
            
            // Отправляем в админский чат (если настроен)
            if ($this->adminChatId) {
                $this->sendToChat($this->adminChatId, $text);
            }
            
            // Отправляем в исходный чат
            $originalChat = $sourceMessage->chat;
            if ($originalChat && $originalChat->chat_id) {
                $this->sendToChat($originalChat->chat_id, $text);
            }
            
            Log::info('Cap unchanged notification sent', [
                'cap_id' => $cap->id,
                'message_id' => $sourceMessage->id,
                'type' => 'cap_unchanged'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send cap unchanged notification', [
                'cap_id' => $cap->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Отправляет групповое уведомление о множественных обновлениях
     */
    public function sendBulkUpdateNotification($sourceMessage, $updates)
    {
        if (!$this->enabled || !$this->botToken || empty($updates)) {
            return false;
        }
        
        try {
            $text = $this->formatBulkUpdateMessage($sourceMessage, $updates);
            
            // Отправляем в админский чат (если настроен)
            if ($this->adminChatId) {
                $this->sendToChat($this->adminChatId, $text);
            }
            
            // Отправляем в исходный чат
            $originalChat = $sourceMessage->chat;
            if ($originalChat && $originalChat->chat_id) {
                $this->sendToChat($originalChat->chat_id, $text);
            }
            
            Log::info('Bulk cap update notification sent', [
                'source_message_id' => $sourceMessage->id,
                'updates_count' => count($updates),
                'type' => 'bulk_update'
            ]);
            
            return true;
            
        } catch (\Exception $e) {
            Log::error('Failed to send bulk update notification', [
                'source_message_id' => $sourceMessage->id,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }
    
    /**
     * Отправляет сообщение в конкретный чат
     */
    private function sendToChat($chatId, $text)
    {
        $response = Http::timeout(10)->post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true
        ]);
        
        $result = $response->json();
        
        if (!$result['ok']) {
            throw new \Exception('Telegram API error: ' . ($result['description'] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    /**
     * Форматирует сообщение о новой капе
     */
    private function formatNewCapMessage($cap, $sourceMessage)
    {
        $emoji = '💰';
        $header = "{$emoji} <b>НОВАЯ КАПА</b>";
        
        $capInfo = $this->formatCapInfo($cap);
        $sourceInfo = $this->formatSourceInfo($sourceMessage);
        
        return "{$header}\n\n{$capInfo}\n\n{$sourceInfo}";
    }
    
    /**
     * Форматирует сообщение об обновлении капы
     */
    private function formatUpdateCapMessage($cap, $sourceMessage, $oldValues, $newValues, $changedFields)
    {
        $emoji = '🔄';
        $header = "{$emoji} <b>ОБНОВЛЕНИЕ КАПЫ</b>";
        
        $capInfo = $this->formatCapInfo($cap);
        $changesInfo = $this->formatChangesInfo($oldValues, $newValues, $changedFields);
        $sourceInfo = $this->formatSourceInfo($sourceMessage);
        
        return "{$header}\n\n{$capInfo}\n\n{$changesInfo}\n\n{$sourceInfo}";
    }
    
    /**
     * Форматирует сообщение о том, что капа не изменилась
     */
    private function formatUnchangedCapMessage($cap, $sourceMessage)
    {
        $emoji = '✅';
        $header = "{$emoji} <b>КАПА БЕЗ ИЗМЕНЕНИЙ</b>";
        
        $capInfo = $this->formatCapInfo($cap);
        $sourceInfo = $this->formatSourceInfo($sourceMessage);
        $statusInfo = "📋 <b>Статус:</b> Данные полностью совпадают с существующими";
        
        return "{$header}\n\n{$capInfo}\n\n{$statusInfo}\n\n{$sourceInfo}";
    }

    /**
     * Форматирует сообщение для чата где обновилась капа
     */
    private function formatTargetUpdateMessage($cap, $sourceMessage, $oldValues, $newValues, $changedFields)
    {
        $emoji = '📝';
        $header = "{$emoji} <b>ВАША КАПА ОБНОВЛЕНА</b>";
        
        $capInfo = $this->formatCapInfo($cap);
        $changesInfo = $this->formatChangesInfo($oldValues, $newValues, $changedFields);
        $sourceInfo = "🔗 <b>Источник обновления:</b> {$sourceMessage->chat->title}";
        
        return "{$header}\n\n{$capInfo}\n\n{$changesInfo}\n\n{$sourceInfo}";
    }
    
    /**
     * Форматирует сообщение о групповом обновлении
     */
    private function formatBulkUpdateMessage($sourceMessage, $updates)
    {
        $emoji = '📊';
        $count = count($updates);
        $header = "{$emoji} <b>МАССОВОЕ ОБНОВЛЕНИЕ КАП ({$count})</b>";
        
        $sourceInfo = $this->formatSourceInfo($sourceMessage);
        
        $updatesInfo = "";
        foreach ($updates as $index => $update) {
            $cap = $update['cap'];
            $changedFields = $update['changed_fields'];
            
            $num = $index + 1;
            $affiliate = $cap->affiliate_name ?: '—';
            $broker = $cap->broker_name ?: '—';
            $geos = !empty($cap->geos) ? implode(', ', $cap->geos) : '—';
            $fields = !empty($changedFields) ? implode(', ', $this->translateFields($changedFields)) : '—';
            
            $updatesInfo .= "{$num}. <b>{$affiliate} - {$broker}</b>\n";
            $updatesInfo .= "   📍 Гео: {$geos}\n";
            $updatesInfo .= "   🔧 Изменено: {$fields}\n\n";
        }
        
        return "{$header}\n\n{$updatesInfo}{$sourceInfo}";
    }
    
    /**
     * Форматирует информацию о капе
     */
    private function formatCapInfo($cap)
    {
        $affiliate = $cap->affiliate_name ?: '—';
        $broker = $cap->broker_name ?: '—';
        $capAmounts = !empty($cap->cap_amounts) ? implode(', ', $cap->cap_amounts) : '—';
        $totalAmount = $cap->total_amount === -1 ? '∞' : ($cap->total_amount ?: '—');
        $schedule = $cap->schedule ?: '24/7';
        $date = $cap->date ?: '∞';
        $geos = !empty($cap->geos) ? implode(', ', $cap->geos) : '—';
        
        return "💼 <b>Аффилейт:</b> {$affiliate}\n" .
               "🏢 <b>Брокер:</b> {$broker}\n" .
               "💰 <b>Капа:</b> {$capAmounts}\n" .
               "📊 <b>Общий лимит:</b> {$totalAmount}\n" .
               "⏰ <b>Расписание:</b> {$schedule}\n" .
               "📅 <b>Дата:</b> {$date}\n" .
               "📍 <b>Гео:</b> {$geos}";
    }
    
    /**
     * Форматирует информацию об изменениях
     */
    private function formatChangesInfo($oldValues, $newValues, $changedFields)
    {
        if (empty($changedFields)) {
            return "🔧 <b>Изменения:</b> Нет изменений";
        }
        
        $changes = [];
        foreach ($changedFields as $field) {
            $oldValue = $this->formatFieldValue($field, $oldValues[$field] ?? null);
            $newValue = $this->formatFieldValue($field, $newValues[$field] ?? null);
            $fieldName = $this->translateField($field);
            
            $changes[] = "   {$fieldName}: {$oldValue} → {$newValue}";
        }
        
        return "🔧 <b>Изменения:</b>\n" . implode("\n", $changes);
    }
    
    /**
     * Форматирует информацию об источнике
     */
    private function formatSourceInfo($sourceMessage)
    {
        $chatName = $sourceMessage->chat->title ?: 'Неизвестный чат';
        $userName = $sourceMessage->display_name ?: 'Неизвестный пользователь';
        $time = $sourceMessage->created_at->format('d.m.Y H:i');
        
        return "🔗 <b>Источник:</b> {$chatName}\n" .
               "👤 <b>Пользователь:</b> {$userName}\n" .
               "🕐 <b>Время:</b> {$time}";
    }
    
    /**
     * Форматирует значение поля для отображения
     */
    private function formatFieldValue($field, $value)
    {
        if ($value === null || $value === '') {
            return '—';
        }
        
        if (is_array($value)) {
            return implode(', ', $value);
        }
        
        if ($field === 'total_amount' && $value === -1) {
            return '∞';
        }
        
        if (is_bool($value)) {
            return $value ? 'Да' : 'Нет';
        }
        
        return (string) $value;
    }
    
    /**
     * Переводит название поля на русский
     */
    private function translateField($field)
    {
        $translations = [
            'cap_amounts' => '💰 Капа',
            'total_amount' => '📊 Общий лимит',
            'schedule' => '⏰ Расписание',
            'date' => '📅 Дата',
            'is_24_7' => '🕐 24/7',
            'work_hours' => '⏱️ Часы работы',
            'highlighted_text' => '🖍️ Выделенный текст'
        ];
        
        return $translations[$field] ?? $field;
    }
    
    /**
     * Переводит массив полей на русский
     */
    private function translateFields($fields)
    {
        return array_map([$this, 'translateField'], $fields);
    }
    
    /**
     * Проверяет включены ли уведомления
     */
    public function isEnabled()
    {
        return $this->enabled && !empty($this->botToken);
    }
    
    /**
     * Включает/выключает уведомления
     */
    public function setEnabled($enabled)
    {
        $this->enabled = $enabled;
    }
} 