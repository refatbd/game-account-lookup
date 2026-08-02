<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

$registry = new Refatbd\GameAccountLookup\Registry\GameRegistry();
$games = $registry->list();
$active = count(array_filter($games, static fn (array $game): bool => $game['status'] === 'active'));

$curlReady = extension_loaded('curl');
$jsonReady = extension_loaded('json');
$opensslReady = extension_loaded('openssl') && function_exists('openssl_encrypt');

template_json([
    'ok' => true,
    'live_ready' => $curlReady && $jsonReady && $opensslReady,
    'service' => 'Game Account Lookup Test Template',
    'package' => 'refatbd/game-account-lookup',
    'php_version' => PHP_VERSION,
    'extensions' => [
        'curl' => $curlReady,
        'json' => $jsonReady,
        'openssl' => $opensslReady,
    ],
    'games' => [
        'total' => count($games),
        'active' => $active,
    ],
    'demo_mode_available' => (bool) $config['allow_demo_mode'],
    'timestamp' => gmdate(DATE_ATOM),
]);
