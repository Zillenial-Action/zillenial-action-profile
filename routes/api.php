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

// Customer portal Sostrip: Bearer token (guard "customer"), terpisah dari session admin.
Route::prefix('auth')->group(function () {
    Route::post('/register', [Api\CustomerAuthController::class, 'register'])
        ->middleware('throttle:customer-register');
    Route::post('/login', [Api\CustomerAuthController::class, 'login'])
        ->middleware('throttle:customer-login');
    Route::post('/google/exchange', [Api\CustomerAuthController::class, 'googleExchange'])
        ->middleware('throttle:customer-oauth');

    Route::middleware('auth:customer')->group(function () {
        Route::get('/me', [Api\CustomerAuthController::class, 'me']);
        Route::post('/logout', [Api\CustomerAuthController::class, 'logout']);
    });
});

Route::get('/account/orders', [Api\CustomerAccountController::class, 'orders'])
    ->middleware('auth:customer');

// Fundraiser: customer membuat kode voucher dari program yang disetel admin.
Route::middleware('auth:customer')->prefix('fundraiser')->group(function () {
    Route::get('/', [Api\FundraiserController::class, 'index']);
    Route::post('/{program}/kode', [Api\FundraiserController::class, 'generate'])
        ->middleware('throttle:fundraiser-generate');
});
