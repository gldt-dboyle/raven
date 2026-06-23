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
