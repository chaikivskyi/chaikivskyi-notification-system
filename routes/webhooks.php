<?php

use App\Http\Controllers\Webhooks\MailpitController;
use App\Http\Middleware\VerifyMailpitWebhookCredentials;
use Illuminate\Support\Facades\Route;

Route::post('/mailpit', MailpitController::class)
    ->middleware(VerifyMailpitWebhookCredentials::class)
    ->name('mailpit');
