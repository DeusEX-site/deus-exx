<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TelegramBotController;
use App\Models\Message;
use App\Models\Chat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

// Роут для отправки сообщений
Route::get('/send-message', function () {
    $messageText = request('message', 'Тестовое сообщение');
    $user = auth()->user() ? auth()->user()->name : 'Гость';
    
    // Сохраняем сообщение в базу данных
    $message = Message::create([
        'message' => $messageText,
        'user' => $user,
    ]);
    
    // Отправляем событие через веб-сокет (если работает)
    try {
        event(new \App\Events\MessageSent($messageText, $user));
    } catch (Exception $e) {
        // Веб-сокеты не работают, ничего страшного
    }
    
    return response()->json([
        'status' => 'success',
        'message' => 'Сообщение отправлено!',
        'data' => [
            'id' => $message->id,
            'message' => $messageText,
            'user' => $user,
            'timestamp' => $message->created_at->format('H:i:s')
        ]
    ]);
});

// API для polling режима - получение последних сообщений из БД
Route::get('/api/messages/latest', function () {
    $afterId = (int) request('after', 0);
    
    // Получаем сообщения из базы данных
    $messages = Message::getLatest(50, $afterId);
    
    // Форматируем для фронтенда
    $formattedMessages = $messages->map(function ($message) {
        return [
            'id' => $message->id,
            'message' => $message->message,
            'user' => $message->user,
            'timestamp' => $message->created_at->format('H:i:s')
        ];
    });
    
    return response()->json([
        'messages' => $formattedMessages,
        'total' => count($formattedMessages)
    ]);
});

// API для получения чатов
Route::get('/api/chats', function () {
    $chats = Chat::active()
        ->orderByRaw('
            CASE 
                WHEN display_order > 0 THEN display_order
                ELSE 999999 + id  
            END ASC
        ')
        ->withCount('messages')
        ->get();
    
    return response()->json([
        'chats' => $chats
    ]);
});

// API для получения позиций чатов
Route::get('/api/chats/positions', function () {
    $chatPositionService = app(\App\Services\ChatPositionService::class);
    $positions = $chatPositionService->getCurrentPositions();
    
    return response()->json([
        'success' => true,
        'positions' => $positions
    ]);
});

// API для проверки swap-а чатов (без полной перезагрузки)
Route::get('/api/chats/check-swap', function () {
    // Возвращаем только информацию о необходимости swap-а
    // Фронтенд будет вызывать этот endpoint для проверки изменений
    return response()->json([
        'success' => true,
        'swap_needed' => false, // Пока просто заглушка
        'message' => 'No swap needed'
    ]);
});

// API для инициализации позиций чатов
Route::post('/api/chats/init-positions', function () {
    $chatPositionService = app(\App\Services\ChatPositionService::class);
    $chatPositionService->initializePositions();
    
    return response()->json([
        'success' => true,
        'message' => 'Chat positions initialized'
    ]);
});

// API для глобальной рассылки (только для авторизованных пользователей)
Route::middleware('auth')->post('/api/broadcast', function (Request $request) {
    try {
        $message = $request->input('message');
        
        if (empty($message)) {
            return response()->json([
                'success' => false,
                'message' => 'Сообщение не может быть пустым'
            ], 400);
        }

        // Получаем все активные чаты
        $chats = Chat::active()->get();
        
        if ($chats->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Нет активных чатов для рассылки'
            ]);
        }

        $sentCount = 0;
        $errors = [];

        // Отправляем сообщение в каждый чат
        foreach ($chats as $chat) {
            try {
                // Используем TelegramBotController для отправки
                $botController = app(\App\Http\Controllers\TelegramBotController::class);
                
                $sendRequest = new Request([
                    'chat_id' => $chat->chat_id,
                    'text' => $message
                ]);
                
                $response = $botController->sendMessage($sendRequest);
                $responseData = json_decode($response->getContent(), true);
                
                if ($responseData['success']) {
                    $sentCount++;
                } else {
                    $errors[] = "Чат {$chat->display_name}: {$responseData['message']}";
                }
                
                // Небольшая задержка между отправками
                usleep(100000); // 0.1 секунды
                
            } catch (\Exception $e) {
                $errors[] = "Чат {$chat->display_name}: {$e->getMessage()}";
                \Log::error('Broadcast error for chat ' . $chat->id, [
                    'error' => $e->getMessage(),
                    'chat' => $chat->toArray()
                ]);
            }
        }

        \Log::info('Broadcast completed', [
            'sent_count' => $sentCount,
            'total_chats' => $chats->count(),
            'errors_count' => count($errors),
            'message' => $message
        ]);

        return response()->json([
            'success' => true,
            'sent_count' => $sentCount,
            'total_chats' => $chats->count(),
            'errors' => $errors,
            'message' => $sentCount > 0 ? 'Рассылка выполнена' : 'Не удалось отправить ни одного сообщения'
        ]);

    } catch (\Exception $e) {
        \Log::error('Broadcast failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка сервера: ' . $e->getMessage()
        ], 500);
    }
});

// API для получения сообщений чата
Route::get('/api/chats/{chatId}/messages', [TelegramBotController::class, 'getChatMessages']);

// API для анализа сообщений с капой
Route::middleware('auth')->get('/api/cap-analysis', function (Request $request) {
    try {
        $search = $request->get('search', '');
        $chatId = $request->get('chat_id', null);
        $geo = $request->get('geo', '');
        $broker = $request->get('broker', '');
        $affiliate = $request->get('affiliate', '');
        $language = $request->get('language', '');
        $funnel = $request->get('funnel', '');
        $schedule = $request->get('schedule', '');
        $total = $request->get('total', '');
        $status = $request->get('status', '');
        
        // Создаем экземпляр сервиса анализа
        $capAnalysisService = new \App\Services\CapAnalysisService();
        
        // Получаем результаты из базы данных с фильтрами
        $results = $capAnalysisService->searchCapsWithFilters($search, $chatId, [
            'geo' => $geo,
            'recipient' => $broker, // broker переименован в recipient
            'affiliate' => $affiliate,
            'language' => $language,
            'funnel' => $funnel,
            'schedule' => $schedule,
            'total' => $total,
            'status' => $status
        ]);
        
        return response()->json([
            'success' => true,
            'messages' => $results,
            'total' => count($results)
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Cap analysis failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка анализа: ' . $e->getMessage()
        ], 500);
    }
});

// API для получения списков фильтров
Route::middleware('auth')->get('/api/cap-analysis-filters', function (Request $request) {
    try {
        $capAnalysisService = new \App\Services\CapAnalysisService();
        $filterOptions = $capAnalysisService->getFilterOptions();
        
        return response()->json([
            'success' => true,
            'geos' => $filterOptions['geos'],
            'brokers' => $filterOptions['recipients'], // brokers -> recipients
            'affiliates' => $filterOptions['affiliates'],
            'languages' => $filterOptions['languages'],
            'funnels' => $filterOptions['funnels']
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Cap analysis filters failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка загрузки фильтров: ' . $e->getMessage()
        ], 500);
    }
});

// API для получения истории капы
Route::middleware('auth')->get('/api/cap-history/{capId}', function (Request $request, $capId) {
    try {
        $cap = \App\Models\Cap::findOrFail($capId);
        
        // Получаем историю капы
        $history = \App\Models\CapHistory::where('cap_id', $capId)
            ->with(['message' => function($q) {
                $q->with('chat');
            }, 'originalMessage' => function($q) {
                $q->with('chat');
            }])
            ->orderBy('created_at', 'desc')
            ->get();
        
        $historyData = $history->map(function($item) {
            // Используем original_message_id если есть, иначе fallback на message_id
            $messageToShow = $item->originalMessage ?? $item->message;
            
            return [
                'id' => $item->id,
                'message' => $messageToShow->message ?? 'Сообщение удалено',
                'user' => $messageToShow->display_name ?? 'Неизвестный',
                'chat_name' => $messageToShow->chat->display_name ?? 'Неизвестный чат',
                'timestamp' => $item->created_at->format('d.m.Y H:i'),
                'archived_at' => $item->archived_at->format('d.m.Y H:i'),
                'status' => $item->status,
                'analysis' => [
                    'cap_amounts' => $item->cap_amounts,
                    'total_amount' => $item->total_amount,
                    'schedule' => $item->schedule,
                    'date' => $item->date,
                    'affiliate_name' => $item->affiliate_name,
                    'recipient_name' => $item->recipient_name,
                    'geos' => $item->geos ?? [],
                    'language' => $item->language,
                    'funnel' => $item->funnel,
                    'status' => $item->status
                ]
            ];
        });
        
        return response()->json([
            'success' => true,
            'history' => $historyData,
            'total' => $historyData->count()
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Cap history failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Ошибка получения истории: ' . $e->getMessage()
        ], 500);
    }
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

// Страница анализа капы
Route::get('/cap-analysis', function () {
    return view('cap-analysis');
})->middleware(['auth'])->name('cap-analysis');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Settings page
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');
    
    // Telegram Bot Management Routes
    Route::prefix('telegram')->group(function () {
        Route::post('/webhook/set', [TelegramBotController::class, 'setWebhook'])->name('telegram.webhook.set');
        Route::get('/webhook/info', [TelegramBotController::class, 'getWebhookInfo'])->name('telegram.webhook.info');
        Route::delete('/webhook', [TelegramBotController::class, 'deleteWebhook'])->name('telegram.webhook.delete');
        Route::get('/bot/info', [TelegramBotController::class, 'getBotInfo'])->name('telegram.bot.info');
        Route::get('/chats', [TelegramBotController::class, 'getChats'])->name('telegram.chats');
        Route::get('/chats/{chatId}/messages', [TelegramBotController::class, 'getChatMessages'])->name('telegram.chat.messages');
        Route::post('/send-message', [TelegramBotController::class, 'sendMessage'])->name('telegram.send.message');
    });
});



require __DIR__.'/auth.php';
