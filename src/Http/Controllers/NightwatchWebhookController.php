<?php

declare(strict_types=1);

namespace Gldt\Raven\Http\Controllers;

use Gldt\Raven\Data\NightwatchWebhookEvent;
use Gldt\Raven\Enums\NightwatchEventType;
use Gldt\Raven\Jobs\ProcessNightwatchEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NightwatchWebhookController
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->json()->all();
        $type = $data['event'] ?? null;

        // Acknowledge — but ignore — unmapped or malformed event types
        if (! is_string($type) || NightwatchEventType::tryFrom($type) === null) {
            return response()->json(['received' => true, 'ignored' => true]);
        }

        $event = NightwatchWebhookEvent::fromArray($data);

        ProcessNightwatchEvent::dispatch($event)
            ->onConnection(config('raven.queue.connection'))
            ->onQueue(config('raven.queue.queue'));

        return response()->json(['received' => true]);
    }
}
