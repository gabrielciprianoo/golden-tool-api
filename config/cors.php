<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    // 🔥 Lee desde .env
    'allowed_origins' => explode(',', env('CORS_ALLOWED_ORIGINS', '')),

    // 🔥 Permite toda la red local automáticamente
    'allowed_origins_patterns' => [
        env('CORS_ALLOWED_ORIGINS_PATTERN'),
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];