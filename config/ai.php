<?php

return [

    /**
     * AI Provider to use
     * Options: mock, ollama, openai, groq
     */
    'provider' => env('AI_PROVIDER', 'mock'),

    /**
     * Minimum score threshold for notifications (0-100)
     */
    'threshold' => env('AI_THRESHOLD', 80),

    /**
     * Provider configurations
     */
    'providers' => [
        'mock' => [
            'enabled' => true,
        ],

        'ollama' => [
            'enabled' => env('OLLAMA_ENABLED', true),
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'phi3'),
            'timeout' => env('OLLAMA_TIMEOUT', 120),
        ],

        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('OPENAI_MAX_TOKENS', 1000),
            'base_url' => 'https://api.openai.com/v1',
        ],

        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'openai/gpt-oss-120b'),
            'base_url' => env('GROQ_API_BASE', 'https://api.groq.com/openai/v1'),
        ],
    ],

];
