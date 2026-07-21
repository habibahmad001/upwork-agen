<?php

return [

    /**
     * Ollama API host
     * Default: http://localhost:11434
     */
    'host' => env('OLLAMA_HOST', 'http://localhost:11434'),

    /**
     * Default model to use
     * Options: phi3, llama3, llama3:8b, qwen:0.5b, mistral, etc.
     */
    'model' => env('OLLAMA_MODEL', 'phi3'),

    /**
     * Request timeout in seconds
     */
    'timeout' => env('OLLAMA_TIMEOUT', 120),

    /**
     * Enable Ollama service
     */
    'enabled' => env('OLLAMA_ENABLED', true),

];
