<?php

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserNotificationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('/users', UserController::class)->only(['index']);

Route::prefix('user-notifications')->controller(UserNotificationController::class)->group(function () {
    Route::get('/', 'index')->name('user-notifications.index');
    Route::post('/', 'store')->name('user-notifications.store');
    Route::post('/bulk', 'storeBulk')->name('user-notifications.store-bulk');
    Route::get('/status', 'status')->name('user-notifications.status');
    Route::patch('/{userNotification}/cancel', 'cancel')->name('user-notifications.cancel');
});
