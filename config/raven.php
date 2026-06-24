<?php

return [

    'github' => [
        'token' => env('RAVEN_GITHUB_TOKEN'),
        'repository' => env('RAVEN_GITHUB_REPOSITORY'),
        'api_url' => env('RAVEN_GITHUB_API_URL', 'https://api.github.com'),
    ],

    'webhook' => [
        'signing_secret' => env('RAVEN_WEBHOOK_SECRET'),
        'path' => env('RAVEN_WEBHOOK_PATH', 'webhooks/nightwatch'),
        'middleware' => [
            'api',
        ],

        // Per-environment signing secrets for a single shared Raven install.
        // Point each environment's Nightwatch webhook at {path}/{source} — e.g.
        // /webhooks/nightwatch/prod — and give each source its own secret:
        //
        //   RAVEN_WEBHOOK_SOURCES=dev,staging,prod
        //   RAVEN_WEBHOOK_SECRET_DEV=...
        //   RAVEN_WEBHOOK_SECRET_STAGING=...
        //   RAVEN_WEBHOOK_SECRET_PROD=...
        //
        // Built here (not read via env() at request time) so it survives
        // `php artisan config:cache`. Sources with no secret are dropped, so an
        // unconfigured source can't be spoofed.
        'sources' => array_filter(array_reduce(
            array_filter(array_map('trim', explode(',', (string) env('RAVEN_WEBHOOK_SOURCES', '')))),
            function (array $carry, string $source) {
                $carry[$source] = env('RAVEN_WEBHOOK_SECRET_'.strtoupper(str_replace('-', '_', $source)));

                return $carry;
            },
            [],
        )),
    ],

    'queue' => [
        'connection' => env('RAVEN_QUEUE_CONNECTION'),
        'queue' => env('RAVEN_QUEUE'),
    ],

    'labels' => [
        'default' => ['nightwatch'],
        'exception' => ['bug'],
        'performance' => ['performance'],
    ],

];
