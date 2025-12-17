<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    // Thêm /me + profile/* vì SPA dùng session/cookie ở các endpoint này (không nằm dưới /api)
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'me', 'profile/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_map('trim', explode(',', env('CORS_ALLOWED_ORIGINS', 'http://localhost:5173,http://127.0.0.1:5173'))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => [
        'Content-Type',
        'X-Requested-With',
        'X-XSRF-TOKEN',
        'XSRF-TOKEN',
        'X-CSRF-TOKEN',
        'Accept',
        'Authorization',
    ],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
