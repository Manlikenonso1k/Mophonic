<?php

use App\Http\Controllers\Api\SiteController;
use Illuminate\Support\Facades\Route;

Route::get('/site', [SiteController::class, 'index']);
Route::post('/subscribe', [SiteController::class, 'subscribe']);
