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

            // Проверяем, находится ли чат в топ-3
            if ($chat->isInTopThree()) {
                // Если чат уже в топ-3, позиция не изменяется вообще
                return ['status' => 'no_change', 'message' => 'Chat already in top 3, no position change needed'];
            }

            // Чат не в топ-3, проверяем нужно ли менять позиции
            $topThreeChats = Chat::active()->topThree()->get();
            
            if ($topThreeChats->count() < 3) {
                // Если в топ-3 меньше 3 чатов, просто добавляем этот чат
                $this->promoteChatToTopThree($chat);
                return ['status' => 'promoted', 'message' => 'Chat promoted to top 3'];
            }

            // Ищем чат в топ-3 у которого последнее сообщение дольше минуты назад
            $chatToReplace = $this->findChatToReplace($topThreeChats);
            
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
     * Находит чат в топ-3 для замены
     * 
     * @param \Illuminate\Database\Eloquent\Collection $topThreeChats
     * @return Chat|null
     */
    private function findChatToReplace($topThreeChats): ?Chat
    {
        $oneMinuteAgo = Carbon::now()->subMinute();
        
        // Ищем чат с последним сообщением дольше минуты назад
        return $topThreeChats->first(function ($chat) use ($oneMinuteAgo) {
            return $chat->last_message_at && $chat->last_message_at->lt($oneMinuteAgo);
        });
    }

    /**
     * Повышает чат в топ-3
     * 
     * @param Chat $chat
     * @return void
     */
    private function promoteChatToTopThree(Chat $chat): void
    {
        DB::transaction(function () use ($chat) {
            // Получаем максимальный display_order среди топ-3
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
            $chats = Chat::active()
                        ->orderBy('last_message_at', 'desc')
                        ->get();
            
            foreach ($chats as $index => $chat) {
                $order = $index < 3 ? (3 - $index) : 0;
                $chat->update(['display_order' => $order]);
            }
        });
        
        Log::info('Chat positions initialized');
    }

    /**
     * Получает информацию о текущих позициях чатов
     * 
     * @return array
     */
    public function getCurrentPositions(): array
    {
        $topThree = Chat::active()->topThree()->get();
        $others = Chat::active()->withoutTopThree()->get();
        
        return [
            'top_three' => $topThree->map(function ($chat) {
                return [
                    'id' => $chat->id,
                    'title' => $chat->display_name,
                    'display_order' => $chat->display_order,
                    'last_message_at' => $chat->last_message_at,
                    'position' => $chat->getTopThreePosition()
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