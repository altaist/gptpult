<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LkController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\DocumentGenerationController;
use App\Http\Controllers\NewDocumentController;
use App\Http\Controllers\FilesController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentTestController;
use App\Http\Controllers\PaymentController;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/v2', function () {
    return view('v2');
});

Route::get('/dashboard', [LkController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('/lk', [LkController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('lk');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Группа маршрутов для документов
    Route::prefix('documents')->name('documents.')->group(function () {
        Route::post('/', [DocumentController::class, 'quickCreate'])->name('quick-create');
        Route::get('/', [DocumentController::class, 'index'])->name('index');
        Route::get('/{document}', [DocumentController::class, 'show'])->name('show');
        Route::get('/{document}/status', [DocumentController::class, 'checkStatus'])->name('status');
        Route::get('/{document}/edit', [DocumentController::class, 'edit'])->name('edit');
        Route::put('/{document}', [DocumentController::class, 'update'])->name('update');
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('destroy');
        Route::post('/{document}/download-word', [DocumentController::class, 'downloadWord'])->name('download-word');
        
        // Маршруты для полной генерации
        Route::post('/{document}/generate-full', [DocumentGenerationController::class, 'startFullGeneration'])->name('generate-full');
        Route::get('/{document}/generation-progress', [DocumentGenerationController::class, 'getGenerationProgress'])->name('generation-progress');
    });

    // Маршруты для работы с файлами
    Route::get('/files/example', function () {
        return Inertia::render('files/FileExample');
    })->name('files.example')->middleware(['auth', 'web']);

    Route::post('/files/upload', [FilesController::class, 'upload'])->name('files.upload')->middleware(['auth', 'web']);
    Route::get('/files/{file}', [FilesController::class, 'show'])->name('files.show')->middleware(['auth', 'web']);
    Route::get('/files/{file}/download', [FilesController::class, 'download'])->name('files.download')->middleware(['auth', 'web']);
    Route::get('/files/{file}/view', [FilesController::class, 'view'])->name('files.view')->middleware(['auth', 'web']);
    Route::put('/files/{file}', [FilesController::class, 'update'])->name('files.update')->middleware(['auth', 'web']);
    Route::delete('/files/{file}', [FilesController::class, 'destroy'])->name('files.destroy')->middleware(['auth', 'web']);

    // Маршруты для заказов
    Route::post('/orders/{document}/process', [OrderController::class, 'processOrder'])->name('orders.process');

});

// Тестовая страница оплаты
Route::get('/payment/test', [PaymentTestController::class, 'show'])->name('payment.test');
// Страница создания документа (доступна всем, но с проверкой авторизации в компоненте)
Route::get('/new', NewDocumentController::class)->name('documents.new');

// Маршруты для автоматической авторизации
Route::post('/login/auto', [App\Http\Controllers\Auth\AutoAuthController::class, 'autoLogin'])->name('login.auto');
Route::post('/register/auto', [App\Http\Controllers\Auth\AutoAuthController::class, 'autoRegister'])->name('register.auto');
Route::get('/logout', [App\Http\Controllers\Auth\AutoAuthController::class, 'logout'])->name('logout');

// Маршруты для платежей
Route::get('/payment/complete/{orderId}', [PaymentController::class, 'handlePaymentComplete'])
    ->name('payment.complete');

require __DIR__.'/auth.php';
