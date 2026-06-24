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

        // Per-application config for a single shared Raven install. Point each
        // application's Nightwatch webhook at {path}/{source} — e.g.
        // /webhooks/nightwatch/billing — and give each source its own signing
        // secret and (optionally) its own GitHub repository and token:
        //
        //   RAVEN_WEBHOOK_SOURCES=billing,storefront
        //   RAVEN_WEBHOOK_SECRET_BILLING=...
        //   RAVEN_WEBHOOK_REPOSITORY_BILLING=acme/billing
        //   RAVEN_WEBHOOK_TOKEN_BILLING=...            # optional; falls back to RAVEN_GITHUB_TOKEN
        //   RAVEN_WEBHOOK_SECRET_STOREFRONT=...
        //   RAVEN_WEBHOOK_REPOSITORY_STOREFRONT=acme/storefront
        //
        // Built here (not read via env() at request time) so it survives
        // `php artisan config:cache`. A source with no secret is dropped, so an
        // unconfigured source can't be spoofed. A source that omits its
        // repository or token falls back to the top-level github.* config.
        'sources' => array_reduce(
            array_filter(array_map('trim', explode(',', (string) env('RAVEN_WEBHOOK_SOURCES', '')))),
            function (array $carry, string $source) {
                $key = strtoupper(str_replace('-', '_', $source));
                $secret = env('RAVEN_WEBHOOK_SECRET_'.$key);

                if (blank($secret)) {
                    return $carry;
                }

                $carry[$source] = [
                    'secret' => $secret,
                    'repository' => env('RAVEN_WEBHOOK_REPOSITORY_'.$key, env('RAVEN_GITHUB_REPOSITORY')),
                    'token' => env('RAVEN_WEBHOOK_TOKEN_'.$key, env('RAVEN_GITHUB_TOKEN')),
                ];

                return $carry;
            },
            [],
        ),
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
