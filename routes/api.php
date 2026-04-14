<?php

use App\Http\Controllers\API\AnalyticsController;
use App\Http\Controllers\API\BotConfigController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\WhatsAppController;
use Illuminate\Support\Facades\Route;

Route::post('/webhook/whatsapp', [WhatsAppController::class, 'webhook'])
    ->middleware('verify.chatery');

Route::get('/bot/config/{botId}', [BotConfigController::class, 'show']);

Route::post('/chat/message', [ChatController::class, 'sendMessage']);
Route::get('/chat/history/{sessionId}', [ChatController::class, 'getHistory']);
Route::post('/chat/rate', [ChatController::class, 'rateMessage']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/analytics/summary', [AnalyticsController::class, 'summary']);
});
