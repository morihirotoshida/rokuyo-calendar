<?php

use App\Http\Controllers\CalendarController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ログインしていない時に表示されるウェルカムページ
Route::get('/', function () {
    return view('welcome');
});

// ログイン後のダッシュボード（カレンダー表示）
Route::get('/dashboard', [CalendarController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

// ログインしているユーザーのみがアクセスできるルートグループ
Route::middleware('auth')->group(function () {
    // プロフィール編集用のルート
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // ★★ ここからが重要 ★★
    // カレンダーの予約データを扱うためのAPIルートをweb.phpに引っ越し
    // URLの先頭に '/calendar' をつけてグループ化
    Route::prefix('calendar')->group(function () {
        Route::get('/events', [CalendarController::class, 'getEvents'])->name('calendar.events.get');
        Route::post('/events', [CalendarController::class, 'store'])->name('calendar.events.store');
        Route::put('/events/{id}', [CalendarController::class, 'update'])->name('calendar.events.update');
        Route::delete('/events/{id}', [CalendarController::class, 'destroy'])->name('calendar.events.destroy');
    });
});

require __DIR__.'/auth.php';

