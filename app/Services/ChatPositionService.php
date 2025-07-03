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
     * @return array Результат операции
     */
    public function handleNewMessage(int $chatId): array
    {
        try {
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
     * Находит чат в топ-10 для замены
     * 
     * @param \Illuminate\Database\Eloquent\Collection $topTenChats
     * @return Chat|null
     */
    private function findChatToReplace($topTenChats): ?Chat
    {
        $oneMinuteAgo = Carbon::now()->subMinute();
        
        Log::info('Looking for chat to replace', [
            'current_time' => Carbon::now()->toDateTimeString(),
            'one_minute_ago' => $oneMinuteAgo->toDateTimeString(),
            'top_ten_chats' => $topTenChats->map(function ($chat) use ($oneMinuteAgo) {
                $lastMessageTime = $chat->last_message_at;
                $minutesAgo = $lastMessageTime ? $lastMessageTime->diffInMinutes(Carbon::now()) : null;
                $isOld = $lastMessageTime && $lastMessageTime->lt($oneMinuteAgo);
                
                return [
                    'id' => $chat->id,
                    'title' => $chat->display_name,
                    'last_message_at' => $lastMessageTime ? $lastMessageTime->toDateTimeString() : 'null',
                    'minutes_ago' => $minutesAgo,
                    'is_older_than_minute' => $isOld
                ];
            })
        ]);
        
        // Ищем чат с последним сообщением дольше минуты назад
        $chatToReplace = $topTenChats->first(function ($chat) use ($oneMinuteAgo) {
            $isOld = $chat->last_message_at && $chat->last_message_at->lt($oneMinuteAgo);
            return $isOld;
        });
        
        if ($chatToReplace) {
            Log::info('Found chat to replace', [
                'chat_id' => $chatToReplace->id,
                'chat_title' => $chatToReplace->display_name,
                'last_message_at' => $chatToReplace->last_message_at->toDateTimeString(),
                'minutes_ago' => $chatToReplace->last_message_at->diffInMinutes(Carbon::now())
            ]);
        } else {
            Log::info('No chat found for replacement - all chats have recent messages (less than 1 minute old)');
        }
        
        return $chatToReplace;
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