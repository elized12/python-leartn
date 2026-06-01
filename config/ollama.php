<?php

return [
    'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434/api/chat'),
    'model' => env('OLLAMA_MODEL', 'qwen2.5-coder:7b'),
    'timeout' => (int) env('OLLAMA_TIMEOUT', 120),
    'pull_timeout' => (int) env('OLLAMA_PULL_TIMEOUT', 600),
    'enabled' => filter_var(env('OLLAMA_ENABLED', true), FILTER_VALIDATE_BOOL),
];
