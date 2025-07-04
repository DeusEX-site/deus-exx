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
        
        $query = Message::query();
        
        // Поиск сообщений со словами cap, сар, сар (на разных языках)
        $capPatterns = [
            'cap', 'сар', 'сар', 'CAP', 'САР', 'САР',
            'кап', 'КАП', 'каП', 'Кап'
        ];
        
        $query->where(function($q) use ($capPatterns) {
            foreach ($capPatterns as $pattern) {
                $q->orWhere('message', 'LIKE', "%{$pattern}%");
            }
        });
        
        // Если указан конкретный чат
        if ($chatId) {
            $query->where('chat_id', $chatId);
        }
        
        // Дополнительный поиск по тексту
        if ($search) {
            $query->where('message', 'LIKE', "%{$search}%");
        }
        
        $messages = $query->orderBy('created_at', 'desc')
                         ->limit(1000)
                         ->get();
        
        // Анализируем каждое сообщение
        $analyzedMessages = $messages->map(function($message) {
            $analysis = analyzeCapMessage($message->message);
            return [
                'id' => $message->id,
                'message' => $message->message,
                'user' => $message->user,
                'chat_id' => $message->chat_id,
                'chat_name' => $message->chat ? ($message->chat->title ?: $message->chat->username) : 'Unknown',
                'created_at' => $message->created_at,
                'timestamp' => $message->created_at->format('d.m.Y H:i:s'),
                'analysis' => $analysis
            ];
        });
        
        return response()->json([
            'success' => true,
            'messages' => $analyzedMessages,
            'total' => $analyzedMessages->count()
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

// Функция анализа сообщения с капой
function analyzeCapMessage($message) {
    $analysis = [
        'has_cap_word' => false,
        'cap_amount' => null,
        'total_amount' => null,
        'schedule' => null,
        'date' => null,
        'is_24_7' => false,
        'affiliate_name' => null,
        'broker_name' => null,
        'geos' => [],
        'work_hours' => null,
        'raw_numbers' => []
    ];
    
    // Проверяем наличие слов cap/сар/сар
    $capWords = ['cap', 'сар', 'сар', 'CAP', 'САР', 'САР', 'кап', 'КАП'];
    foreach ($capWords as $word) {
        if (stripos($message, $word) !== false) {
            $analysis['has_cap_word'] = true;
            break;
        }
    }
    
    // Ищем 24/7
    if (preg_match('/24\/7|24-7/', $message)) {
        $analysis['is_24_7'] = true;
        $analysis['schedule'] = '24/7';
    }
    
    // Ищем время работы (10-19, 09-18, etc.)
    if (preg_match('/(\d{1,2})-(\d{1,2})/', $message, $matches)) {
        $analysis['work_hours'] = $matches[0];
        $analysis['schedule'] = $matches[0];
    }
    
    // Ищем даты (14.05, 25.12, etc.)
    if (preg_match('/(\d{1,2})\.(\d{1,2})/', $message, $matches)) {
        $analysis['date'] = $matches[0];
    }
    
    // Ищем все числа
    preg_match_all('/\b(\d+)\b/', $message, $numbers);
    if (!empty($numbers[1])) {
        $analysis['raw_numbers'] = array_map('intval', $numbers[1]);
        
        // Анализируем числа
        foreach ($analysis['raw_numbers'] as $number) {
            // Пропускаем очевидные даты и времена
            if ($number > 31 && $number < 2000) {
                // Ищем cap amount (обычно после слова cap)
                $capPattern = '/(?:cap|сар|сар|кап)\s*(\d+)/i';
                if (preg_match($capPattern, $message, $capMatch)) {
                    $analysis['cap_amount'] = intval($capMatch[1]);
                }
                
                // Если число не после cap слова, это может быть total amount
                if (!$analysis['cap_amount'] || $number > $analysis['cap_amount']) {
                    $analysis['total_amount'] = max($analysis['total_amount'] ?: 0, $number);
                }
            }
        }
    }
    
    // Ищем названия (паттерн: название - название)
    if (preg_match('/([a-zA-Zа-яА-Я\s]+)\s*-\s*([a-zA-Zа-яА-Я\s]+)\s*:/', $message, $matches)) {
        $analysis['affiliate_name'] = trim($matches[1]);
        $analysis['broker_name'] = trim($matches[2]);
    }
    
    // Ищем гео после двоеточия
    if (preg_match('/:(.+)$/m', $message, $matches)) {
        $geoString = trim($matches[1]);
        $geos = array_map('trim', explode(',', $geoString));
        $analysis['geos'] = array_filter($geos, function($geo) {
            return strlen($geo) > 1 && strlen($geo) < 50;
        });
    }
    
    return $analysis;
}

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
