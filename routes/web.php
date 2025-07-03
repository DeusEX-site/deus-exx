<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TelegramBotController;
use App\Models\Message;
use App\Models\Chat;
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
        ->orderByActivity()
        ->withCount('messages')
        ->get();
    
    return response()->json([
        'chats' => $chats
    ]);
});

// API для получения сообщений чата
Route::get('/api/chats/{chatId}/messages', function ($chatId) {
    $afterId = (int) request('after', 0);
    $messages = Message::getLatestForChat($chatId, 50, $afterId);
    
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
        'messages' => $formattedMessages,
        'total' => count($formattedMessages)
    ]);
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

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
