<?php

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ["http://192.168.1.113:5173"],

    'allowed_origins_patterns' => [
        env('CORS_ALLOWED_ORIGINS_PATTERN', '#^http://192\.168\.\d+\.\d+(:\d+)?$#'),
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];