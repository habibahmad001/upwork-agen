<?php

return [
    'enabled' => env('WHATSAPP_ENABLED', true),
    'phone_id' => env('WHATSAPP_PHONE_ID'),
    'access_token' => env('WHATSAPP_ACCESS_TOKEN'),
    'phone_number' => env('WHATSAPP_PHONE_NUMBER', '+923228594463'),
    'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID'),
    'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
    'base_url' => 'https://graph.facebook.com',
    'rate_limit' => (int) env('WHATSAPP_RATE_LIMIT', 10), // per minute
    'rate_limit_window' => 60, // seconds
    'template_name' => env('WHATSAPP_TEMPLATE_NAME'),
    'message_template' => [
        'max_length' => 4096,
        'include_link' => true,
        'include_budget' => true,
        'include_score' => true,
        'include_reason' => true,
    ],
];
