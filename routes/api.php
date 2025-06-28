<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PaymentController;

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
    Route::get('/user/transitions', function (Request $request) {
        $user = $request->user();
        $transitions = $user->transitions()->latest()->limit(50)->get();
        
        return response()->json([
            'success' => true,
            'transitions' => $transitions
        ]);
    })->name('api.user.transitions')
      ->middleware('auth');
});
