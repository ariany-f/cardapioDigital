<?php

use App\Http\Controllers\Api\DeliveryWebhookController;
use Illuminate\Support\Facades\Route;

Route::post('/webhooks/delivery', [DeliveryWebhookController::class, 'handle'])
    ->middleware('throttle:webhook')
    ->name('webhooks.delivery');
