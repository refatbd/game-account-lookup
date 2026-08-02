<?php

declare(strict_types=1);

$bool = static function (string $name, bool $default): bool {
    $value = getenv($name);
    if ($value === false || $value === '') {
        return $default;
    }

    return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
};

$int = static function (string $name, int $default): int {
    $value = getenv($name);

    return $value !== false && ctype_digit($value) ? (int) $value : $default;
};

return [
    'debug' => $bool('GAME_LOOKUP_TEMPLATE_DEBUG', false),
    'verify_tls' => $bool('GAME_LOOKUP_TEMPLATE_VERIFY_TLS', true),
    'allow_demo_mode' => $bool('GAME_LOOKUP_TEMPLATE_ALLOW_DEMO', true),
    'demo_mode_default' => $bool('GAME_LOOKUP_TEMPLATE_DEMO_DEFAULT', false),
    'timeout' => $int('GAME_LOOKUP_TEMPLATE_TIMEOUT', 12),
    'connect_timeout' => $int('GAME_LOOKUP_TEMPLATE_CONNECT_TIMEOUT', 5),
    'cache_ttl' => $int('GAME_LOOKUP_TEMPLATE_CACHE_TTL', 300),
    'rate_limit' => $int('GAME_LOOKUP_TEMPLATE_RATE_LIMIT', 60),
    'rate_window' => $int('GAME_LOOKUP_TEMPLATE_RATE_WINDOW', 60),
    'storage_path' => __DIR__ . '/storage',
];
