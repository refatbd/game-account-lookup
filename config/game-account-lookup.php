<?php

declare(strict_types=1);

return [
    'timeout' => (int) env('GAME_LOOKUP_TIMEOUT', 12),
    'connect_timeout' => (int) env('GAME_LOOKUP_CONNECT_TIMEOUT', 5),
    'verify_tls' => (bool) env('GAME_LOOKUP_VERIFY_TLS', true),
    'debug' => (bool) env('GAME_LOOKUP_DEBUG', false),

    'cache' => [
        'enabled' => (bool) env('GAME_LOOKUP_CACHE', true),
        'ttl' => (int) env('GAME_LOOKUP_CACHE_TTL', 300),
        'store' => env('GAME_LOOKUP_CACHE_STORE'),
    ],

    'session_ttl' => (int) env('GAME_LOOKUP_SESSION_TTL', 1800),

    /*
     * Add or override game definitions from your application.
     *
     * 'games' => [
     *     'mygame' => [
     *         'label' => 'My Game',
     *         'requiresZone' => false,
     *         'providers' => [...],
     *     ],
     * ],
     */
    'games' => [],
];
