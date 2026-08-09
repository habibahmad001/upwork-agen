<?php

return [
    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'pusher' => [
        'app_id' => env('PUSHER_APP_ID'),
        'app_key' => env('PUSHER_APP_KEY'),
        'app_secret' => env('PUSHER_APP_SECRET'),
        'cluster' => env('PUSHER_CLUSTER', 'mt1'),
        'use_tls' => env('PUSHER_USE_TLS', true),
        'timeout' => env('PUSHER_TIMEOUT', 30),
        'channel' => env('PUSHER_CHANNEL', 'jobs'),
        'event' => env('PUSHER_EVENT', 'new-job'),
    ],

    'groq' => [
        'api_key' => env('GROQ_API_KEY'),
    ],
];
