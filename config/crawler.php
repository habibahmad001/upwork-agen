<?php

return [
    'enabled' => env('CRAWLER_ENABLED', true),
    'node_binary' => env('NODE_BINARY', 'node'),
    'crawler_path' => base_path('crawler/playwright/crawler.js'),
    'storage_json' => base_path('crawler/playwright/storage.json'),
    'timeout' => (int) env('CRAWLER_TIMEOUT', 120),
    'max_jobs' => (int) env('CRAWLER_MAX_JOBS', 50),
    'headless' => env('CRAWLER_HEADLESS', true),
    'user_data_dir' => env('CRAWLER_USER_DATA_DIR'),
];
