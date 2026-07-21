<?php

return [
    'provider' => env('AI_PROVIDER', 'mock'), // mock, openai, groq
    'providers' => [
        'mock' => [
            'enabled' => true,
        ],
        'openai' => [
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 30),
        ],
        'groq' => [
            'api_key' => env('GROQ_API_KEY'),
            'model' => env('GROQ_MODEL', 'llama3-70b-8192'),
            'base_url' => env('GROQ_BASE_URL', 'https://api.groq.com/openai/v1'),
            'timeout' => (int) env('GROQ_TIMEOUT', 30),
        ],
        'ollama' => [
            'host' => env('OLLAMA_HOST', 'http://localhost:11434'),
            'model' => env('OLLAMA_MODEL', 'qwen:0.5b'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 60),
        ],
    ],
    'threshold' => (float) env('AI_THRESHOLD', 80.0),
    'max_tokens' => (int) env('AI_MAX_TOKENS', 1000),
    'temperature' => (float) env('AI_TEMPERATURE', 0.3),
];
