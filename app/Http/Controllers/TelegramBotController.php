<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramBotController extends Controller
{
    private $botToken;
    
    public function __construct()
    {
        $this->botToken = config('telegram.bot_token', '7903137926:AAFKezQgg7YowaFxc1qXJQEpo7zQRMQIaZY');
    }
    
    public function setWebhook(Request $request)
    {
        $webhookUrl = $request->get('url', config('app.url') . '/api/telegram/webhook');
        
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/setWebhook", [
                'url' => $webhookUrl,
                'allowed_updates' => ['message', 'edited_message', 'callback_query'],
            ]);
            
            $result = $response->json();
            
            if ($result['ok']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook установлен успешно',
                    'webhook_url' => $webhookUrl,
                    'result' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка установки webhook: ' . $result['description'],
                    'result' => $result
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Set webhook error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка соединения с Telegram API',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getWebhookInfo()
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$this->botToken}/getWebhookInfo");
            $result = $response->json();
            
            return response()->json([
                'success' => true,
                'webhook_info' => $result['result']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get webhook info error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения информации о webhook',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function deleteWebhook()
    {
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/deleteWebhook");
            $result = $response->json();
            
            if ($result['ok']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Webhook удален успешно',
                    'result' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка удаления webhook: ' . $result['description'],
                    'result' => $result
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Delete webhook error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка соединения с Telegram API',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getBotInfo()
    {
        try {
            $response = Http::get("https://api.telegram.org/bot{$this->botToken}/getMe");
            $result = $response->json();
            
            return response()->json([
                'success' => true,
                'bot_info' => $result['result']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Get bot info error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка получения информации о боте',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    public function getChats()
    {
        $chats = Chat::active()
            ->orderByRaw('
                CASE 
                    WHEN display_order > 0 THEN display_order
                    ELSE 999999 + id  
                END DESC
            ')
            ->withCount('messages')
            ->get();
        
        return response()->json([
            'success' => true,
            'chats' => $chats
        ]);
    }
    
    public function getChatMessages($chatId, Request $request)
    {
        $chat = Chat::findOrFail($chatId);
        
        // Параметры для пагинации
        $after = $request->get('after', 0);
        $before = $request->get('before', null);
        $limit = min($request->get('limit', 20), 20); // Максимум 20 сообщений
        
        // Если загружаем старые сообщения (скролл вверх)
        if ($before && $before > 0) {
            $messages = Message::getOlderForChat($chatId, $before, $limit);
        } else {
            // Обычная загрузка (новые сообщения)
            $messages = Message::getLatestForChat($chatId, $limit, $after);
        }
        
        // Форматируем сообщения для фронтенда
        $formattedMessages = $messages->map(function ($message) {
            return [
                'id' => $message->id,
                'message' => $message->message,
                'user' => $message->display_name,
                'timestamp' => $message->created_at->format('H:i:s'),
                'message_type' => $message->message_type,
                'telegram_user_id' => $message->telegram_user_id,
                'is_telegram' => !is_null($message->telegram_message_id),
                'is_outgoing' => $message->is_outgoing ?? false
            ];
        });
        
        return response()->json([
            'success' => true,
            'chat' => $chat,
            'messages' => $formattedMessages,
            'has_older' => $before ? true : (count($messages) >= $limit)
        ]);
    }
    
    public function sendMessage(Request $request)
    {
        $request->validate([
            'chat_id' => 'required|integer',
            'text' => 'required|string|max:4096',
        ]);
        
        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $request->chat_id,
                'text' => $request->text,
                'parse_mode' => 'HTML',
            ]);
            
            $result = $response->json();
            
            if ($result['ok']) {
                // Сохраняем отправленное сообщение в базу данных
                $this->saveOutgoingMessage($request->chat_id, $request->text, $result['result']);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Сообщение отправлено успешно',
                    'result' => $result
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Ошибка отправки сообщения: ' . $result['description'],
                    'result' => $result
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Send message error:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'success' => false,
                'message' => 'Ошибка соединения с Telegram API',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Сохраняем исходящее сообщение от бота в базу данных
     */
    private function saveOutgoingMessage($telegramChatId, $messageText, $telegramResult)
    {
        try {
            // Находим чат в базе данных по telegram chat_id
            $chat = Chat::where('chat_id', $telegramChatId)->first();
            
            if (!$chat) {
                Log::warning('Chat not found for outgoing message', ['chat_id' => $telegramChatId]);
                return;
            }
            
            // Получаем информацию о текущем пользователе
            $user = auth()->user();
            $senderName = $user ? $user->name : 'Bot Admin';
            
            // Создаем запись сообщения
            $message = Message::create([
                'chat_id' => $chat->id,
                'message' => $messageText,
                'user' => '🤖 ' . $senderName, // Префикс бота для различия
                'telegram_message_id' => $telegramResult['message_id'],
                'telegram_user_id' => $telegramResult['from']['id'], // ID бота
                'telegram_username' => $telegramResult['from']['username'] ?? null,
                'telegram_first_name' => $telegramResult['from']['first_name'] ?? 'Bot',
                'telegram_last_name' => $telegramResult['from']['last_name'] ?? null,
                'telegram_date' => Carbon::createFromTimestamp($telegramResult['date']),
                'message_type' => 'text',
                'telegram_raw_data' => $telegramResult,
                'is_outgoing' => true, // Помечаем как исходящее сообщение
            ]);
            
            // Обновляем только счетчик сообщений, НЕ обновляем last_message_at
            // так как исходящие сообщения не должны влиять на позиции чатов
            $chat->increment('message_count');
            
            Log::info('Outgoing message saved (chat positions not affected)', [
                'message_id' => $message->id,
                'chat_id' => $chat->id,
                'telegram_message_id' => $telegramResult['message_id'],
                'note' => 'last_message_at not updated for outgoing message'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to save outgoing message:', [
                'error' => $e->getMessage(),
                'chat_id' => $telegramChatId,
                'message' => $messageText
            ]);
        }
    }
} 