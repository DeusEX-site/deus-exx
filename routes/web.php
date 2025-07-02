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

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
