<?php

$normalizeCorsOrigin = static function (?string $value): ?string {
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    if (! str_contains($value, '://')) {
        return rtrim($value, '/');
    }

    $parts = parse_url($value);

    if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
        return rtrim($value, '/');
    }

    $origin = $parts['scheme'] . '://' . $parts['host'];

    if (isset($parts['port'])) {
        $origin .= ':' . $parts['port'];
    }

    return $origin;
};

$corsOrigins = array_filter(array_map(
    $normalizeCorsOrigin,
    explode(',', env('CORS_ALLOWED_ORIGINS', ''))
));

$frontendUrl = $normalizeCorsOrigin(env('FRONTEND_URL', ''));

if ($frontendUrl !== null) {
    $corsOrigins[] = $frontendUrl;
}

if (empty($corsOrigins)) {
    $corsOrigins = ['http://localhost:5173', 'http://127.0.0.1:5173'];
}

$corsOrigins = array_values(array_unique($corsOrigins));

$corsOriginPatterns = [];

if (env('APP_ENV') === 'local') {
    $corsOriginPatterns = [
        '#^http://localhost(:\\d+)?$#',
        '#^http://127\\.0\\.0\\.1(:\\d+)?$#',
    ];
}

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

    // Add /me + profile/* because the SPA uses session cookies on these paths too.
    'paths' => ['api/*', 'sanctum/csrf-cookie', 'login', 'logout', 'me', 'profile/*', 'password/forgot', 'password/reset'],

    'allowed_methods' => ['*'],

    'allowed_origins' => $corsOrigins,

    'allowed_origins_patterns' => $corsOriginPatterns,

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
