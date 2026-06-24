<?php

declare(strict_types=1);

use Gldt\Raven\Http\Controllers\NightwatchWebhookController;
use Gldt\Raven\Http\Middleware\VerifyNightwatchSignature;
use Illuminate\Support\Facades\Route;

Route::post(config('raven.webhook.path').'/{source?}', NightwatchWebhookController::class)
    ->where('source', '[A-Za-z0-9_-]+')
    ->middleware([...config('raven.webhook.middleware'), VerifyNightwatchSignature::class])
    ->name('raven.webhook');
