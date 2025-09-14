<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CalendarController; // CalendarControllerをインポート

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

// ▼▼▼ このルーティングを修正 ▼▼▼
// ログイン後の /dashboard アクセス時に、CalendarControllerのindexメソッドを呼び出すように変更
Route::get('/dashboard', [CalendarController::class, 'index'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // カレンダーのイベントデータ取得・保存用のルート
    Route::get('/calendar/events', [CalendarController::class, 'getEvents']);
    Route::post('/calendar/events', [CalendarController::class, 'store']);
    Route::put('/calendar/events/{id}', [CalendarController::class, 'update']);
    Route::delete('/calendar/events/{id}', [CalendarController::class, 'destroy']);
});

require __DIR__.'/auth.php';
