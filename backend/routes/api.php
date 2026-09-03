<?php

use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ShopController;
use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/site', [SiteController::class, 'index']);
Route::post('/subscribe', [SiteController::class, 'subscribe']);

Route::get('/shop', [ShopController::class, 'index']);
Route::get('/shop/products/{slug}', [ShopController::class, 'show']);

Route::post('/orders', [OrderController::class, 'store']);
Route::get('/orders/{reference}', [OrderController::class, 'show']);
