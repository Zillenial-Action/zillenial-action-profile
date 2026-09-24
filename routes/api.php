<?php

use App\Http\Controllers\Api;
use App\Http\Controllers\PortalController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Events (public)
Route::get('/events', [Api\EventController::class, 'index']);
Route::get('/events/{slug}', [Api\EventController::class, 'show']);

// Payment methods (public)
Route::get('/payment-methods', [Api\PaymentMethodController::class, 'index']);

// Voucher validation
Route::post('/voucher/validate', [PortalController::class, 'validateVoucher'])
    ->middleware('throttle:voucher')
    ->name('api.voucher.validate');

// Checkout
Route::post('/checkout', [Api\CheckoutController::class, 'store'])
    ->middleware('throttle:checkout');

// Transaction status
Route::get('/transaksi/{invoice}', [Api\TransaksiController::class, 'show'])
    ->middleware('throttle:transaction-status');
