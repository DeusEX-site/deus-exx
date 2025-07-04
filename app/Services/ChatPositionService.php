<?php

namespace App\Services;

use App\Models\Chat;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ChatPositionService
{
    /**
     * Проверяет и обновляет позиции чатов при получении нового сообщения
     * 
     * @param int $chatId ID чата, который получил новое сообщение
     * @param bool $isOutgoing Является ли сообщение исходящим (от бота)
     * @return array Результат операции
     */
    public function handleNewMessage(int $chatId, bool $isOutgoing = false): array
    {
        try {
            // Если сообщение исходящее (от бота), не обрабатываем позиции
            if ($isOutgoing) {
                Log::info('Outgoing message ignored for position changes', [
                    'chat_id' => $chatId,
                    'message_is_outgoing' => true
                ]);
                return ['status' => 'ignored', 'message' => 'Outgoing message ignored for position changes'];
            }

            $chat = Chat::active()->find($chatId);
            
            if (!$chat) {
                return ['status' => 'error', 'message' => 'Chat not found'];
            }

            // Проверяем, находится ли чат в топ-10
            if ($chat->isInTopTen()) {
                // Если чат уже в топ-10, позиция не изменяется вообще
                Log::info('Chat already in top 10, no position change', [
                    'chat_id' => $chat->id,
                    'chat_title' => $chat->display_name,
                    'display_order' => $chat->display_order
                ]);
                return ['status' => 'no_change', 'message' => 'Chat already in top 10, no position change needed'];
            }

            // Чат не в топ-10, проверяем нужно ли менять позиции
            $topTenChats = Chat::active()->topTen()->get();
            
            if ($topTenChats->count() < 10) {
                // Если в топ-10 меньше 10 чатов, просто добавляем этот чат
                $this->promoteChatToTopTen($chat);
                return ['status' => 'promoted', 'message' => 'Chat promoted to top 10'];
            }

            // Ищем чат в топ-10 у которого последнее сообщение дольше минуты назад
            $chatToReplace = $this->findChatToReplace($topTenChats);
            
            if ($chatToReplace) {
                // Меняем чаты местами
                $this->swapChats($chat, $chatToReplace);
                
                Log::info('Chat positions swapped', [
                    'promoted_chat' => $chat->id,
                    'demoted_chat' => $chatToReplace->id,
                    'promoted_chat_title' => $chat->display_name,
                    'demoted_chat_title' => $chatToReplace->display_name
                ]);
                
                return [
                    'status' => 'swapped',
                    'message' => 'Chat positions swapped',
                    'promoted_chat' => $chat->id,
                    'demoted_chat' => $chatToReplace->id,
                    'promoted_chat_title' => $chat->display_name,
                    'demoted_chat_title' => $chatToReplace->display_name
                ];
            }

            return ['status' => 'no_change', 'message' => 'No suitable chat found for replacement'];
            
        } catch (\Exception $e) {
            Log::error('Error handling new message for chat positions', [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return ['status' => 'error', 'message' => 'Internal error'];
        }
    }

    /**
     * Находит чат в топ-10 для замены, учитывая только входящие сообщения
     * 
     * @param \Illuminate\Database\Eloquent\Collection $topTenChats
     * @return Chat|null
     */
    private function findChatToReplace($topTenChats): ?Chat
    {
        // Находим чат с самым старым последним входящим сообщением среди всех топ-10
        $chatToReplace = $topTenChats->sortBy(function ($chat) {
            $lastIncomingMessageTime = $this->getLastIncomingMessageTime($chat);
            // Чаты без входящих сообщений имеют приоритет для замены (самые старые)
            return $lastIncomingMessageTime ? $lastIncomingMessageTime->timestamp : 0;
        })->first();
        
        Log::info('Looking for chat to replace (always replace oldest)', [
            'current_time' => Carbon::now()->toDateTimeString(),
            'top_ten_chats' => $topTenChats->map(function ($chat) {
                $lastIncomingMessageTime = $this->getLastIncomingMessageTime($chat);
                $minutesAgo = $lastIncomingMessageTime ? $lastIncomingMessageTime->diffInMinutes(Carbon::now()) : null;
                
                return [
                    'id' => $chat->id,
                    'title' => $chat->display_name,
                    'last_incoming_message_at' => $lastIncomingMessageTime ? $lastIncomingMessageTime->toDateTimeString() : 'never',
                    'minutes_ago' => $minutesAgo ?: 'never',
                    'display_order' => $chat->display_order
                ];
            })->toArray()
        ]);
        
        if ($chatToReplace) {
            $lastIncomingMessageTime = $this->getLastIncomingMessageTime($chatToReplace);
            Log::info('Found chat to replace (oldest in top-10)', [
                'chat_id' => $chatToReplace->id,
                'chat_title' => $chatToReplace->display_name,
                'last_incoming_message_at' => $lastIncomingMessageTime ? $lastIncomingMessageTime->toDateTimeString() : 'never',
                'minutes_ago' => $lastIncomingMessageTime ? $lastIncomingMessageTime->diffInMinutes(Carbon::now()) : 'never',
                'display_order' => $chatToReplace->display_order
            ]);
        }
        
        return $chatToReplace;
    }

    /**
     * Получает время последнего входящего сообщения для чата
     * 
     * @param Chat $chat
     * @return Carbon|null
     */
    private function getLastIncomingMessageTime(Chat $chat): ?Carbon
    {
        $lastIncomingMessage = $chat->messages()
            ->where('is_outgoing', false)
            ->orderBy('created_at', 'desc')
            ->first();
        
        return $lastIncomingMessage ? $lastIncomingMessage->created_at : null;
    }

    /**
     * Повышает чат в топ-10
     * 
     * @param Chat $chat
     * @return void
     */
    private function promoteChatToTopTen(Chat $chat): void
    {
        DB::transaction(function () use ($chat) {
            // Получаем максимальный display_order среди топ-10
            $maxOrder = Chat::active()->max('display_order') ?? 0;
            
            // Присваиваем новый display_order
            $chat->update([
                'display_order' => $maxOrder + 1
            ]);
        });
    }

    /**
     * Меняет два чата местами
     * 
     * @param Chat $promotedChat Чат, который нужно повысить
     * @param Chat $demotedChat Чат, который нужно понизить
     * @return void
     */
    private function swapChats(Chat $promotedChat, Chat $demotedChat): void
    {
        DB::transaction(function () use ($promotedChat, $demotedChat) {
            $oldPromotedOrder = $promotedChat->display_order;
            $oldDemotedOrder = $demotedChat->display_order;
            
            // Меняем display_order местами
            $promotedChat->update([
                'display_order' => $oldDemotedOrder
            ]);
            
            $demotedChat->update([
                'display_order' => $oldPromotedOrder
            ]);
        });
    }

    /**
     * Инициализирует позиции для существующих чатов
     * 
     * @return void
     */
    public function initializePositions(): void
    {
        DB::transaction(function () {
            // Получаем чаты, у которых display_order = 0 (не инициализированы)
            $uninitializedChats = Chat::active()
                                    ->where('display_order', 0)
                                    ->orderBy('last_message_at', 'desc')
                                    ->get();
            
            // Получаем текущие топ-10 чаты
            $topTenChats = Chat::active()
                               ->where('display_order', '>', 0)
                               ->orderBy('display_order', 'desc')
                               ->get();
            
            // Если топ-10 не заполнен, добавляем чаты из неинициализированных
            $availableSlots = 10 - $topTenChats->count();
            
            if ($availableSlots > 0) {
                $maxOrder = $topTenChats->max('display_order') ?? 0;
                
                foreach ($uninitializedChats->take($availableSlots) as $chat) {
                    $maxOrder++;
                    $chat->update(['display_order' => $maxOrder]);
                }
            }
        });
        
        Log::info('Chat positions initialized (preserving existing top-10)');
    }

    /**
     * Получает информацию о текущих позициях чатов
     * 
     * @return array
     */
    public function getCurrentPositions(): array
    {
        $topTen = Chat::active()->topTen()->get();
        $others = Chat::active()->withoutTopTen()->get();
        
        return [
            'top_ten' => $topTen->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->display_name,
                    'display_order' => $chat->display_order,
                    'last_message_at' => $chat->last_message_at,
                    'position' => $chat->getTopTenPosition()
                ];
            }),
            'others' => $others->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->display_name,
                    'display_order' => $chat->display_order,
                    'last_message_at' => $chat->last_message_at
                ];
            })
        ];
    }
} 