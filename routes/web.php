<?php

use App\Http\Controllers\ProfileController;
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

// Тестовый роут для отправки сообщений через веб-сокет
Route::get('/send-message', function () {
    $message = request('message', 'Тестовое сообщение');
    $user = auth()->user() ? auth()->user()->name : 'Гость';
    
    // Отправляем событие через веб-сокет
    event(new \App\Events\MessageSent($message, $user));
    
    return response()->json([
        'status' => 'success',
        'message' => 'Сообщение отправлено!',
        'data' => [
            'message' => $message,
            'user' => $user,
            'timestamp' => now()->format('H:i:s')
        ]
    ]);
});

// API для polling режима (когда веб-сокеты не работают)
Route::get('/api/messages/latest', function () {
    // Простое хранилище в сессии (в продакшне лучше использовать Redis/Database)
    $messages = session('broadcast_messages', []);
    $afterId = (int) request('after', 0);
    
    // Фильтруем сообщения после указанного ID
    $newMessages = array_filter($messages, function($msg) use ($afterId) {
        return $msg['id'] > $afterId;
    });
    
    return response()->json([
        'messages' => array_values($newMessages),
        'total' => count($newMessages)
    ]);
});

// Вспомогательный роут для добавления сообщения в сессию (для polling)
Route::middleware('web')->group(function () {
    Route::get('/send-message', function () {
        $message = request('message', 'Тестовое сообщение');
        $user = auth()->user() ? auth()->user()->name : 'Гость';
        $timestamp = now()->format('H:i:s');
        
        // Отправляем событие через веб-сокет (если работает)
        try {
            event(new \App\Events\MessageSent($message, $user));
        } catch (Exception $e) {
            // Веб-сокеты не работают, ничего страшного
        }
        
        // Также сохраняем в сессию для polling режима
        $messages = session('broadcast_messages', []);
        $newMessage = [
            'id' => count($messages) + 1,
            'message' => $message,
            'user' => $user,
            'timestamp' => $timestamp
        ];
        
        $messages[] = $newMessage;
        
        // Ограничиваем количество сообщений в сессии (последние 50)
        if (count($messages) > 50) {
            $messages = array_slice($messages, -50);
        }
        
        session(['broadcast_messages' => $messages]);
        
        return response()->json([
            'status' => 'success',
            'message' => 'Сообщение отправлено!',
            'data' => $newMessage
        ]);
    });
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
