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

// Customer authentication uses the session cookie bridge provided by Sanctum.
Route::prefix('auth')->group(function () {
    Route::post('/register', [Api\CustomerAuthController::class, 'register'])
        ->middleware('throttle:customer-register');
    Route::post('/login', [Api\CustomerAuthController::class, 'login'])
        ->middleware('throttle:customer-login');
    Route::post('/logout', [Api\CustomerAuthController::class, 'logout'])
        ->middleware('auth:customer');
    Route::get('/me', [Api\CustomerAuthController::class, 'me'])
        ->middleware('auth:customer');
    Route::post('/forgot-password', [Api\CustomerAuthController::class, 'forgotPassword'])
        ->middleware('throttle:customer-password');
    Route::post('/reset-password', [Api\CustomerAuthController::class, 'resetPassword'])
        ->middleware('throttle:customer-password');
    Route::post('/email/verification-notification', [Api\CustomerAuthController::class, 'sendVerificationNotification'])
        ->middleware(['auth:customer', 'throttle:customer-verification']);
});

Route::get('/account/orders', [Api\CustomerAccountController::class, 'orders'])
    ->middleware(['auth:customer', 'customer.verified']);
