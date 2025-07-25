<?php

namespace App\Http\Controllers;

use App\Models\Chat;
use App\Models\Message;
use App\Events\MessageSent;
use App\Services\ChatPositionService;
use App\Services\CapAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TelegramWebhookController extends Controller
{
    protected $chatPositionService;
    protected $capAnalysisService;

    public function __construct(ChatPositionService $chatPositionService, CapAnalysisService $capAnalysisService)
    {
        $this->chatPositionService = $chatPositionService;
        $this->capAnalysisService = $capAnalysisService;
    }

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
            
            // Обрабатываем позиции чатов для всех сообщений
            // (ChatPositionService сам определит, нужно ли что-то менять)
            $positionResult = $this->chatPositionService->handleNewMessage($chatModel->id, false);
            if ($positionResult['status'] === 'swapped') {
                Log::info('Chat positions changed', $positionResult);
            } elseif ($positionResult['status'] === 'no_change') {
                Log::info('No position changes needed', [
                    'chat_id' => $chatModel->id,
                    'chat_title' => $chatModel->display_name,
                    'status' => $positionResult['status']
                ]);
            }
            
            // Обновляем статистику чата (счетчик сообщений и время)
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
        
        $replyToMessageId = null;
        $quotedText = null;
        $textToAnalyze = $messageText;

        // 1. Определяем текст цитаты, отдавая приоритет полю 'quote' на верхнем уровне
        if (isset($messageData['quote']['text'])) {
            $quotedText = $messageData['quote']['text'];
        } 
        // Если quote не найден, ищем в reply_to_message (для совместимости)
        elseif (isset($messageData['reply_to_message']['text'])) {
            $quotedText = $messageData['reply_to_message']['text'];
        }

        // 2. Находим ID оригинального сообщения, на которое был дан ответ
        if (isset($messageData['reply_to_message'])) {
            $replyToData = $messageData['reply_to_message'];
            
            $replyToMessage = Message::where('telegram_message_id', $replyToData['message_id'])
                                   ->where('chat_id', $chatModel->id)
                                   ->first();
            
            if ($replyToMessage) {
                $replyToMessageId = $replyToMessage->id;
                
                // 3. Сравниваем текст цитаты с текстом оригинального сообщения из БД
                // и решаем, какой текст анализировать
                if ($quotedText !== null && strlen($quotedText) !== strlen($replyToMessage->message)) {
                    $textToAnalyze = $quotedText;
                }
            }
        }
        
        // Определяем тип cap-сообщения
        $capMessageType = $this->determineCapMessageType($messageText);
        
        $message = Message::create([
            'chat_id' => $chatModel->id,
            'message' => $messageText,
            'quoted_text' => $quotedText,
            'user' => $displayName,
            'telegram_message_id' => $messageData['message_id'],
            'reply_to_message_id' => $replyToMessageId,
            'telegram_user_id' => $user['id'],
            'telegram_username' => $user['username'] ?? null,
            'telegram_first_name' => $user['first_name'] ?? null,
            'telegram_last_name' => $user['last_name'] ?? null,
            'telegram_date' => Carbon::createFromTimestamp($messageData['date']),
            'message_type' => $capMessageType,
            'telegram_raw_data' => $messageData,
        ]);
        
        // Анализируем определенный текст
        try {
            // ПРИВОДИМ ТЕКСТ К НИЖНЕМУ РЕГИСТРУ перед анализом кап
            $lowercaseText = strtolower($textToAnalyze);
            $this->capAnalysisService->analyzeAndSaveCapMessage($message->id, $lowercaseText);
        } catch (\Exception $e) {
            Log::warning('Cap analysis failed for message', [
                'message_id' => $message->id,
                'error' => $e->getMessage()
            ]);
        }
        
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
        // Обновляем last_message_at только для входящих сообщений
        // (исходящие сообщения обрабатываются в TelegramBotController)
        $chatModel->update(['last_message_at' => now()]);
    }

    private function determineCapMessageType($messageText)
    {
        // Подсчитываем количество блоков affiliate
        $affiliateBlocks = preg_match_all('/^affiliate:\s*(.+)$/m', $messageText);
        
        // Если больше одного блока - групповое сообщение
        if ($affiliateBlocks > 1) {
            // Проверяем количество кап в каждом блоке
            $blocks = preg_split('/\n\s*\n/', $messageText);
            $hasMultiCaps = false;
            
            foreach ($blocks as $block) {
                if (preg_match('/^cap:\s*(.+)$/m', $block, $matches)) {
                    $caps = preg_split('/\s+/', trim($matches[1]));
                    if (count($caps) > 1) {
                        $hasMultiCaps = true;
                        break;
                    }
                }
            }
            
            return $hasMultiCaps ? 'group_multi' : 'group_single';
        } else {
            // Одиночное сообщение - проверяем количество кап
            if (preg_match('/^cap:\s*(.+)$/m', $messageText, $matches)) {
                $caps = preg_split('/\s+/', trim($matches[1]));
                return count($caps) > 1 ? 'single_multi' : 'single_single';
            }
        }
        
        return 'unknown';
    }
} 