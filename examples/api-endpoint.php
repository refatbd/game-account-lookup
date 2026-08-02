<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Refatbd\GameAccountLookup\GameAccountLookup;

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

$game = trim((string) ($_GET['game'] ?? ''));
$playerId = trim((string) ($_GET['player_id'] ?? ''));
$zoneId = isset($_GET['zone_id']) ? trim((string) $_GET['zone_id']) : null;

if ($game === '' || $playerId === '') {
    http_response_code(422);
    echo json_encode([
        'ok' => false,
        'message' => 'game and player_id are required.',
    ]);
    exit;
}

/*
 * Production checklist:
 * - Add authentication when this is not a public endpoint.
 * - Apply per-IP and per-user rate limits.
 * - Use persistent PSR/Laravel/Redis cache rather than no cache.
 * - Never expose provider credentials from browser-side JavaScript.
 */
$result = GameAccountLookup::make()->check($game, $playerId, $zoneId);

http_response_code($result->ok ? 200 : 422);
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
