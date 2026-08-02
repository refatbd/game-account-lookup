<?php

declare(strict_types=1);

use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\GameAccountLookup;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Template\FileCache;
use Refatbd\GameAccountLookup\Template\RateLimiter;

/** @var array<string, mixed> $config */
$config = require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header("Referrer-Policy: same-origin");
header("Content-Security-Policy: default-src 'none'; frame-ancestors 'none'");

/** @param array<string, mixed> $payload */
function template_json(array $payload, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

/** @return array<string, mixed> */
function template_input(): array
{
    $input = array_merge($_GET, $_POST);
    $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));

    if (str_contains($contentType, 'application/json')) {
        $body = file_get_contents('php://input');
        $decoded = is_string($body) ? json_decode($body, true) : null;
        if (is_array($decoded)) {
            $input = array_merge($input, $decoded);
        }
    }

    return $input;
}

function template_bool(mixed $value): bool
{
    if (is_bool($value)) {
        return $value;
    }

    return filter_var((string) $value, FILTER_VALIDATE_BOOL);
}

/** @param array<string, mixed> $config */
function template_rate_limit(array $config): void
{
    $identity = (string) ($_SERVER['REMOTE_ADDR'] ?? 'local');
    $limiter = new RateLimiter(
        (string) $config['storage_path'] . '/rate-limits',
        (int) $config['rate_limit'],
        (int) $config['rate_window'],
    );
    $state = $limiter->hit($identity);

    header('X-RateLimit-Limit: ' . (int) $config['rate_limit']);
    header('X-RateLimit-Remaining: ' . $state['remaining']);
    header('X-RateLimit-Reset: ' . $state['reset_at']);

    if (!$state['allowed']) {
        template_json([
            'ok' => false,
            'code' => ResultCode::RATE_LIMITED,
            'message' => 'Too many local test requests. Try again after the rate-limit window resets.',
        ], 429);
    }
}

/** @param array<string, mixed> $config */
function template_lookup(array $config): GameAccountLookup
{
    return GameAccountLookup::make([
        'debug' => (bool) $config['debug'],
        'verify_tls' => (bool) $config['verify_tls'],
        'timeout' => (int) $config['timeout'],
        'connect_timeout' => (int) $config['connect_timeout'],
        'cache_ttl' => (int) $config['cache_ttl'],
        'cache' => new FileCache((string) $config['storage_path'] . '/cache'),
    ]);
}

function template_demo_result(array $game, string $playerId, ?string $zoneId, string $provider = 'demo'): LookupResult
{
    $nickname = 'DemoPlayer-' . strtoupper(substr(hash('sha256', $game['code'] . '|' . $playerId . '|' . ($zoneId ?? '') . '|' . $provider), 0, 8));

    return LookupResult::success(
        (string) $game['code'],
        $playerId,
        $nickname,
        $provider,
        $zoneId,
        $zoneId,
        ['demo' => true, 'simulated_provider' => $provider],
    )->withAttempts([[
        'provider' => $provider,
        'ok' => true,
        'code' => ResultCode::SUCCESS,
        'message' => 'Generated locally without contacting an upstream provider.',
    ]]);
}
