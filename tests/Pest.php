<?php

declare(strict_types=1);

use Gldt\Raven\Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(TestCase::class, RefreshDatabase::class)->in(__DIR__);

/**
 * POST a Nightwatch webhook payload with a valid HMAC signature.
 *
 * @param  array<string, mixed>  $payload
 */
function postNightwatchWebhook(array $payload, ?string $secret = 'test-secret'): TestResponse
{
    $body = json_encode($payload);
    $signature = hash_hmac('sha256', $body, (string) $secret);

    return test()->call(
        'POST',
        '/webhooks/nightwatch',
        [], [], [],
        [
            'HTTP_NIGHTWATCH_SIGNATURE' => $signature,
            'CONTENT_TYPE' => 'application/json',
        ],
        $body,
    );
}

/**
 * Build a minimal valid issue payload, overridable per test.
 *
 * @param  array<string, mixed>  $issue
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function nightwatchPayload(string $event, array $issue = [], array $overrides = []): array
{
    return array_replace_recursive([
        'event' => $event,
        'timestamp' => '2026-06-23T00:00:00Z',
        'payload' => [
            'issue' => array_replace([
                'id' => 'issue-uuid',
                'ref' => 7,
                'type' => 'exception',
                'title' => 'RuntimeException: kaboom',
                'status' => 'open',
                'priority' => 'high',
                'url' => 'https://nightwatch.test/issues/7',
                'details' => ['class' => 'RuntimeException', 'message' => 'kaboom'],
            ], $issue),
            'environment' => ['id' => 'env-1', 'name' => 'production'],
        ],
    ], $overrides);
}
