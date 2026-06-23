<?php

declare(strict_types=1);

namespace Gldt\Raven\Http\Controllers;

use Gldt\Raven\Data\NightwatchWebhookEvent;
use Gldt\Raven\Jobs\ProcessNightwatchEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NightwatchWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $event = NightwatchWebhookEvent::fromArray($request->json()->all());

        ProcessNightwatchEvent::dispatch($event)
            ->onConnection(config('raven.queue.connection'))
            ->onQueue(config('raven.queue.queue'));

        return response()->json(['received' => true]);
    }
}
