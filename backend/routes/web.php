<?php

use App\Http\Controllers\PaystackWebhookController;
use App\Http\Controllers\ShopPaymentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin');
});

// Paystack appends ?reference=… to a single global callback URL.
Route::get('/shop/payment/callback', [ShopPaymentController::class, 'callback'])
    ->name('shop.payment.callback');

// One webhook endpoint for every event; CSRF is excluded per-route because
// Laravel 11+ no longer ships an app-level VerifyCsrfToken middleware.
Route::post('/webhooks/paystack', [PaystackWebhookController::class, 'handle'])
    ->name('webhooks.paystack');
