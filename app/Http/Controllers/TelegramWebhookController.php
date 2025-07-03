<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {
            $update = $request->all();
            
            // Логируем входящие данные для отладки
            Log::info('Telegram webhook received:', $update);
            
            // Проверяем наличие сообщения
            if (!isset($update['message'])) {
                return response()->json(['status' => 'no_message'], 200);
            }
            
            $message = $update['message'];
            $chat = $message['chat'];
            
            // Создаем или обновляем чат
            $chatModel = $this->createOrUpdateChat($chat);
            
            // Создаем сообщение
            $messageModel = $this->createMessage($message, $chatModel);
            
            // Обновляем статистику чата
            $this->updateChatStats($chatModel);
            
            // Отправляем событие через веб-сокет (если работает)
            try {
                $displayName = $messageModel->display_name;
                $messageText = $messageModel->message;
                event(new MessageSent($messageText, $displayName));
            } catch (\Exception $e) {
                // Веб-сокеты не работают, ничего страшного
                Log::warning('WebSocket event failed:', ['error' => $e->getMessage()]);
            }
            
            return response()->json(['status' => 'success'], 200);
            
        } catch (\Exception $e) {
            Log::error('Telegram webhook error:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);
            
            return response()->json(['status' => 'error'], 500);
        }
    }
    
    private function createOrUpdateChat($chatData)
    {
        $chat = Chat::where('chat_id', $chatData['id'])->first();
        
        $data = [
            'chat_id' => $chatData['id'],
            'type' => $chatData['type'],
            'title' => $chatData['title'] ?? null,
            'username' => $chatData['username'] ?? null,
            'description' => $chatData['description'] ?? null,
            'is_active' => true,
        ];
        
        if ($chat) {
            $chat->update($data);
        } else {
            $chat = Chat::create($data);
        }
        
        return $chat;
    }
    
    private function createMessage($messageData, $chatModel)
    {
        $messageText = $this->extractMessageText($messageData);
        $messageType = $this->getMessageType($messageData);
        
        $user = $messageData['from'];
        $displayName = $this->getUserDisplayName($user);
        
        $message = Message::create([
            'chat_id' => $chatModel->id,
            'message' => $messageText,
            'user' => $displayName,
            'telegram_message_id' => $messageData['message_id'],
            'telegram_user_id' => $user['id'],
            'telegram_username' => $user['username'] ?? null,
            'telegram_first_name' => $user['first_name'] ?? null,
            'telegram_last_name' => $user['last_name'] ?? null,
            'telegram_date' => Carbon::createFromTimestamp($messageData['date']),
            'message_type' => $messageType,
            'telegram_raw_data' => $messageData,
        ]);
        
        return $message;
    }
    
    private function extractMessageText($messageData)
    {
        if (isset($messageData['text'])) {
            return $messageData['text'];
        }
        
        if (isset($messageData['caption'])) {
            return $messageData['caption'];
        }
        
        // Для других типов сообщений возвращаем описание
        return $this->getMessageDescription($messageData);
    }
    
    private function getMessageType($messageData)
    {
        if (isset($messageData['text'])) return 'text';
        if (isset($messageData['photo'])) return 'photo';
        if (isset($messageData['document'])) return 'document';
        if (isset($messageData['video'])) return 'video';
        if (isset($messageData['audio'])) return 'audio';
        if (isset($messageData['voice'])) return 'voice';
        if (isset($messageData['sticker'])) return 'sticker';
        if (isset($messageData['location'])) return 'location';
        if (isset($messageData['contact'])) return 'contact';
        
        return 'unknown';
    }
    
    private function getMessageDescription($messageData)
    {
        if (isset($messageData['photo'])) return '[Фото]';
        if (isset($messageData['document'])) return '[Документ: ' . ($messageData['document']['file_name'] ?? 'файл') . ']';
        if (isset($messageData['video'])) return '[Видео]';
        if (isset($messageData['audio'])) return '[Аудио]';
        if (isset($messageData['voice'])) return '[Голосовое сообщение]';
        if (isset($messageData['sticker'])) return '[Стикер: ' . ($messageData['sticker']['emoji'] ?? '🙂') . ']';
        if (isset($messageData['location'])) return '[Геолокация]';
        if (isset($messageData['contact'])) return '[Контакт: ' . ($messageData['contact']['first_name'] ?? 'контакт') . ']';
        
        return '[Неизвестный тип сообщения]';
    }
    
    private function getUserDisplayName($user)
    {
        if (!empty($user['first_name']) || !empty($user['last_name'])) {
            return trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? ''));
        }
        
        if (!empty($user['username'])) {
            return '@' . $user['username'];
        }
        
        return 'Пользователь #' . $user['id'];
    }
    
    private function updateChatStats($chatModel)
    {
        $chatModel->increment('message_count');
        $chatModel->update(['last_message_at' => now()]);
    }
} 