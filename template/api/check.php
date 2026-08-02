<?php

declare(strict_types=1);

use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\ResultCode;

require __DIR__ . '/_common.php';

if (!in_array($_SERVER['REQUEST_METHOD'] ?? 'GET', ['GET', 'POST'], true)) {
    header('Allow: GET, POST');
    template_json(['ok' => false, 'code' => 'METHOD_NOT_ALLOWED', 'message' => 'Use GET or POST.'], 405);
}

template_rate_limit($config);
$input = template_input();
$gameCode = trim((string) ($input['game'] ?? ''));
$playerId = trim((string) ($input['player_id'] ?? $input['playerId'] ?? ''));
$zoneId = trim((string) ($input['zone_id'] ?? $input['zoneId'] ?? ''));
$zoneId = $zoneId === '' ? null : $zoneId;
$demo = template_bool($input['demo'] ?? false);
$bypassCache = template_bool($input['bypass_cache'] ?? $input['bypassCache'] ?? false);
$providerSelection = trim((string) ($input['provider'] ?? ''));

if ($gameCode === '' || strlen($gameCode) > 80) {
    template_json(['ok' => false, 'code' => 'INVALID_GAME', 'message' => 'Choose a valid game.'], 400);
}
if ($playerId === '' || strlen($playerId) > 128) {
    template_json(['ok' => false, 'code' => ResultCode::INVALID_PLAYER, 'message' => 'Player ID is required and must be 128 characters or fewer.'], 400);
}
if ($zoneId !== null && strlen($zoneId) > 128) {
    template_json(['ok' => false, 'code' => 'INVALID_ZONE', 'message' => 'Zone/server ID must be 128 characters or fewer.'], 400);
}

$registry = new GameRegistry();
$game = $registry->get($gameCode);
if ($game === null) {
    template_json(['ok' => false, 'code' => ResultCode::GAME_NOT_FOUND, 'message' => 'Unknown game.'], 404);
}
$allowedProviders = [];
foreach ((array) ($game['providers'] ?? []) as $providerKey => $providerConfig) {
    if (!is_array($providerConfig) || ($providerConfig['enabled'] ?? true) === true) {
        $allowedProviders[] = (string) $providerKey;
    }
}

$gameStatus = (string) ($game['status'] ?? 'active');
if (!$demo && $gameStatus !== 'active') {
    $notes = trim((string) ($game['notes'] ?? ''));
    template_json([
        'ok' => false,
        'code' => ResultCode::MAINTENANCE_REQUIRED,
        'message' => sprintf(
            'Live lookup for %s is currently unavailable (%s).%s',
            (string) ($game['label'] ?? $game['code']),
            $gameStatus,
            $notes !== '' ? ' ' . $notes : '',
        ),
        'game' => (string) $game['code'],
        'player_id' => $playerId,
        'zone_id' => $zoneId,
        'provider' => null,
        'cached' => false,
        'mode' => 'live',
        'lookup_mode' => 'unavailable',
        'cache_bypassed' => $bypassCache,
        'game_status' => $gameStatus,
        'maintenance_notes' => $notes !== '' ? $notes : null,
        'allowed_providers' => $allowedProviders,
    ]);
}
if (($game['requiresZone'] ?? false) === true && $zoneId === null) {
    template_json([
        'ok' => false,
        'code' => ResultCode::ZONE_REQUIRED,
        'message' => 'This game requires a zone or server ID.',
        'game' => $game['code'],
    ], 422);
}

$lookupMode = match ($providerSelection) {
    '__all__' => 'all',
    '' => 'automatic',
    default => 'single',
};

if ($lookupMode === 'single' && !in_array($providerSelection, $allowedProviders, true)) {
    template_json([
        'ok' => false,
        'code' => ResultCode::PROVIDER_NOT_CONFIGURED,
        'message' => 'The selected provider is not configured for this game.',
        'allowed_providers' => $allowedProviders,
    ], 422);
}

if ($lookupMode === 'all' && $allowedProviders === []) {
    template_json([
        'ok' => false,
        'code' => ResultCode::PROVIDER_NOT_CONFIGURED,
        'message' => 'No enabled provider is configured for this game.',
        'allowed_providers' => [],
    ], 422);
}

if ($demo && !(bool) $config['allow_demo_mode']) {
    template_json(['ok' => false, 'code' => 'DEMO_DISABLED', 'message' => 'Demo mode is disabled.'], 403);
}

if ($lookupMode === 'all') {
    $providerResults = [];
    $successful = [];
    $attempts = [];
    $lookup = $demo ? null : template_lookup($config);

    foreach ($allowedProviders as $provider) {
        /** @var LookupResult $providerResult */
        $providerResult = $demo
            ? template_demo_result($game, $playerId, $zoneId, $provider)
            : $lookup->check($gameCode, $playerId, $zoneId, [$provider], $bypassCache);

        $resultArray = $providerResult->toArray();
        $resultArray['requested_provider'] = $provider;
        $providerResults[] = $resultArray;
        $attempts[] = [
            'provider' => $provider,
            'ok' => $providerResult->ok,
            'code' => $providerResult->code,
            'message' => $providerResult->message,
            'nickname' => $providerResult->nickname,
            'cached' => $providerResult->cached,
            'meta' => $providerResult->meta,
        ];

        if ($providerResult->ok) {
            $successful[] = $providerResult;
        }
    }

    $firstSuccess = $successful[0] ?? null;
    $successCount = count($successful);
    $total = count($providerResults);
    $cachedCount = count(array_filter($providerResults, static fn (array $result): bool => ($result['cached'] ?? false) === true));
    $payload = [
        'ok' => $successCount > 0,
        'code' => $successCount > 0 ? 'ALL_PROVIDER_RESULTS' : ResultCode::ALL_PROVIDERS_FAILED,
        'message' => sprintf('%d provider%s checked: %d succeeded and %d failed.', $total, $total === 1 ? '' : 's', $successCount, $total - $successCount),
        'game' => (string) $game['code'],
        'player_id' => $playerId,
        'zone_id' => $zoneId,
        'nickname' => $firstSuccess?->nickname,
        'provider' => 'all',
        'server' => $firstSuccess?->server,
        'country' => $firstSuccess?->country,
        'cached' => $cachedCount > 0 && $cachedCount === $total,
        'mode' => $demo ? 'demo' : 'live',
        'lookup_mode' => 'all',
        'cache_bypassed' => $bypassCache,
        'summary' => [
            'providers_checked' => $total,
            'succeeded' => $successCount,
            'failed' => $total - $successCount,
            'cached' => $cachedCount,
        ],
        'provider_results' => $providerResults,
        'attempts' => $attempts,
    ];

    // Provider failures are application results, not origin gateway failures.
    // Always keep transport HTTP 200 so Cloudflare/custom 5xx pages cannot replace diagnostics.
    template_json($payload);
}

if ($demo) {
    $provider = $lookupMode === 'single' ? $providerSelection : 'demo';
    $result = template_demo_result($game, $playerId, $zoneId, $provider)->toArray();
    $result['mode'] = 'demo';
    $result['lookup_mode'] = $lookupMode;
    $result['cache_bypassed'] = $bypassCache;
    template_json($result);
}

$providerOrder = $lookupMode === 'single' ? [$providerSelection] : null;
$result = template_lookup($config)->check($gameCode, $playerId, $zoneId, $providerOrder, $bypassCache);
$payload = $result->toArray();
$payload['mode'] = 'live';
$payload['lookup_mode'] = $lookupMode;
$payload['cache_bypassed'] = $bypassCache;
// A completed lookup request returns HTTP 200 even when the upstream provider
// reports an invalid account, rate limit, network error or malformed response.
// Consumers must inspect `ok` and `code`; this prevents Cloudflare from replacing
// useful provider diagnostics with a generic 502 origin page.
$payload['transport_status'] = 200;
$payload['upstream_failure'] = !$result->ok;
template_json($payload);
