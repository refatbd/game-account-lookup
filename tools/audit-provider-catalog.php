<?php

declare(strict_types=1);

$root = dirname(__DIR__);
/** @var array<string, array<string, array<string, mixed>>> $catalog */
$catalog = require $root . '/resources/provider-catalog.php';
$json = in_array('--json', $argv, true);
$live = in_array('--live', $argv, true);
$verificationDates = [];
foreach ($catalog as $providers) {
    foreach ($providers as $config) {
        if (is_array($config) && !empty($config['verifiedAt'])) {
            $verificationDates[] = (string) $config['verifiedAt'];
        }
    }
}
sort($verificationDates);
$snapshotDate = $verificationDates === [] ? null : (string) end($verificationDates);

$report = [
    'generated_at' => gmdate(DATE_ATOM),
    'catalog_verified_at' => $snapshotDate,
    'live_check' => $live,
    'summary' => [],
    'games' => [],
];

foreach ($catalog as $game => $providers) {
    $entry = ['game' => $game, 'providers' => []];
    foreach ($providers as $provider => $config) {
        $status = (string) ($config['status'] ?? 'unknown');
        $report['summary'][$provider][$status] = ($report['summary'][$provider][$status] ?? 0) + 1;
        $url = (string) ($config['productUrl'] ?? $config['productPage'] ?? '');
        $providerEntry = [
            'status' => $status,
            'verified_at' => $config['verifiedAt'] ?? null,
            'official_url' => $url !== '' ? $url : null,
            'note' => $config['note'] ?? null,
        ];

        if ($live && $url !== '') {
            $providerEntry['live'] = checkUrl($url);
        }
        $entry['providers'][$provider] = $providerEntry;
    }
    $report['games'][] = $entry;
}

if ($json) {
    echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . PHP_EOL;
    exit(0);
}

echo "Provider catalog audit\n";
echo "Latest verification: " . ($snapshotDate ?? 'not-audited') . "\n";
echo "Definitions: " . count($catalog) . "\n\n";
foreach ($report['summary'] as $provider => $states) {
    echo strtoupper((string) $provider) . "\n";
    ksort($states);
    foreach ($states as $status => $count) {
        echo sprintf("  %-22s %d\n", $status, $count);
    }
    echo "\n";
}

if ($live) {
    $failures = 0;
    foreach ($report['games'] as $game) {
        foreach ($game['providers'] as $provider => $details) {
            if (!isset($details['live'])) {
                continue;
            }
            $liveResult = $details['live'];
            $ok = (bool) ($liveResult['reachable'] ?? false);
            if (!$ok) {
                $failures++;
            }
            echo sprintf(
                "[%s] %-24s %-12s HTTP %s %s\n",
                $ok ? 'OK' : 'FAIL',
                $game['game'],
                $provider,
                $liveResult['http_status'] ?? 'n/a',
                $details['official_url'] ?? '',
            );
        }
    }
    echo "\nUnreachable official pages: {$failures}\n";
}

/** @return array<string, mixed> */
function checkUrl(string $url): array
{
    if (!function_exists('curl_init')) {
        return ['reachable' => false, 'error' => 'PHP cURL extension is unavailable.'];
    }

    $handle = curl_init($url);
    if ($handle === false) {
        return ['reachable' => false, 'error' => 'Could not initialize cURL.'];
    }
    curl_setopt_array($handle, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 5,
        CURLOPT_TIMEOUT => 12,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_USERAGENT => 'GameAccountLookupProviderAudit/1.0 (+https://github.com/refatbd)',
        CURLOPT_HTTPHEADER => ['Accept: text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8'],
        CURLOPT_RANGE => '0-65535',
    ]);
    $body = curl_exec($handle);
    $error = curl_error($handle);
    $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
    $effective = (string) curl_getinfo($handle, CURLINFO_EFFECTIVE_URL);
    curl_close($handle);

    return [
        'reachable' => is_string($body) && $status >= 200 && $status < 400,
        'http_status' => $status,
        'effective_url' => $effective,
        'bytes_sampled' => is_string($body) ? strlen($body) : 0,
        'error' => $error !== '' ? $error : null,
    ];
}
