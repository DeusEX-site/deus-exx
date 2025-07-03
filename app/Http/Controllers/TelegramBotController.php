<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
            ->orderByActivity()
            ->withCount('messages')
            ->get();
        
        return response()->json([
            'success' => true,
            'chats' => $chats
        ]);
    }
    
    public function getChatMessages($chatId)
    {
        $chat = Chat::findOrFail($chatId);
        $messages = Message::getLatestForChat($chatId, 100);
        
        return response()->json([
            'success' => true,
            'chat' => $chat,
            'messages' => $messages
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
} 