<?php

use App\Http\Controllers\DatoCmsWebhookController;
use App\Http\Controllers\WelcomeController;
use Illuminate\Support\Facades\Route;

Route::pattern('locale', 'en|fr');

// DatoCMS cache invalidation webhook
Route::post('/invalidate-datocms-cache', [DatoCmsWebhookController::class, 'invalidateCache'])
    ->name('datocms.invalidate-cache');

Route::get('/{locale?}', [WelcomeController::class, 'index']);
