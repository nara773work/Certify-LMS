<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| JSON API のルート定義。
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// ============================================================
// S-A-05　Sanctum Cookie 認証 + JS フロント通知表示
// ============================================================
Route::middleware(['auth:sanctum'])->group(function () {

    Route::get('/v1/notifications', [ApiNotificationController::class, 'index'])
    ->name('notifications.index');
    Route::post('/v1/notifications/{notification}/read', [ApiNotificationController::class, 'read'])
    ->name('notifications.read');
    Route::post('/v1/notifications/read-all', [ApiNotificationController::class, 'readAll'])
    ->name('notifications.readAll');

});

