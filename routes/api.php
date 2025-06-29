<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\LkController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API маршруты для платежей (без CSRF проверки, но с веб-аутентификацией)
Route::middleware('web')->group(function () {
    // API роут для создания платежей (всегда возвращает JSON)
    Route::post('/payment/yookassa/create/{orderId}', [PaymentController::class, 'createYookassaPaymentApi'])
        ->name('api.payment.yookassa.create')
        ->middleware('auth');

    // API роут для проверки статуса платежа
    Route::get('/payment/status/{orderId}', [PaymentController::class, 'checkPaymentStatusApi'])
        ->name('api.payment.status')
        ->middleware('auth');
        
    // API роут для получения транзакций пользователя
    Route::get('/user/transitions', [LkController::class, 'getTransitionHistory'])
        ->name('api.user.transitions')
        ->middleware('auth');

    // API роут для тестового уменьшения баланса (только для разработки)
    Route::post('/user/test-decrement-balance', [LkController::class, 'testDecrementBalance'])
        ->name('api.user.test-decrement-balance')
        ->middleware('auth');
});
