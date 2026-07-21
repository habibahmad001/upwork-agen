<?php

return [

    'mailer' => env('MAIL_MAILER', 'smtp'),

    'host' => env('MAIL_HOST', 'smtp.mailgun.org'),

    'port' => env('MAIL_PORT', 587),

    'encryption' => env('MAIL_ENCRYPTION', 'tls'),

    'username' => env('MAIL_USERNAME'),

    'password' => env('MAIL_PASSWORD'),

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Laravel'),
    ],

    // Recipient for job notifications
    'notification_recipient' => env('ADMIN_NOTIFICATION_EMAIL'),

    'enabled' => env('MAIL_ENABLED', true),

];
