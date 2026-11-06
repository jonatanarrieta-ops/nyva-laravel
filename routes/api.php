<?php

use App\Http\Controllers\Api\ConversationController;
use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('establishments/{establishment:slug}')->name('api.establishments.')->group(function () {
    Route::apiResource('conversations', ConversationController::class)
        ->only(['store', 'show', 'destroy'])
        ->scoped(['conversation' => 'token'])
        ->middlewareFor(['store', 'destroy'], 'throttle:chatbot-conversations')
        ->middlewareFor('show', 'throttle:chatbot-history');

    Route::apiResource('conversations.messages', MessageController::class)
        ->only(['store'])
        ->scoped(['conversation' => 'token'])
        ->middleware('throttle:chatbot-messages');
});
