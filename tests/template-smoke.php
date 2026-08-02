<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';
require dirname(__DIR__) . '/template/lib/FileCache.php';

use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\Template\FileCache;

$root = dirname(__DIR__);
$required = [
    'template/index.php',
    'template/bootstrap.php',
    'template/api/health.php',
    'template/api/games.php',
    'template/api/check.php',
    'template/assets/app.css',
    'template/assets/app.js',
    'template/start.sh',
    'template/start.bat',
    'template/README.md',
];

foreach ($required as $relative) {
    if (!is_file($root . '/' . $relative)) {
        throw new RuntimeException('Missing template file: ' . $relative);
    }
}

$registry = new GameRegistry();
$games = $registry->list();
if (count($games) !== count($registry->all())) {
    throw new RuntimeException('Template registry list does not match the raw registry.');
}

$temp = sys_get_temp_dir() . '/game-account-lookup-template-' . bin2hex(random_bytes(4));
$cache = new FileCache($temp);
$result = LookupResult::success('freefire', '4422076728', 'TemplatePlayer', 'demo');
$cache->put('sample', $result, 30);
$cached = $cache->get('sample');

if (!$cached instanceof LookupResult || $cached->nickname !== 'TemplatePlayer') {
    throw new RuntimeException('Template file cache did not round-trip LookupResult.');
}

foreach (glob($temp . '/*') ?: [] as $file) {
    @unlink($file);
}
@rmdir($temp);



$checkSource = (string) file_get_contents($root . '/template/api/check.php');
if (str_contains($checkSource, 'default => 502') || str_contains($checkSource, '$successCount > 0 ? 200 : 502')) {
    throw new RuntimeException('Template still converts provider failures into gateway 502 responses.');
}
if (!str_contains($checkSource, "'transport_status'")) {
    throw new RuntimeException('Template transport policy marker is missing.');
}
$indexSource = (string) file_get_contents($root . '/template/index.php');
if (!str_contains($indexSource, 'Provider diagnostics') || !str_contains($indexSource, 'gameAvailability')) {
    throw new RuntimeException('Template provider diagnostics or maintenance notice is missing.');
}
echo "Template smoke test passed.\n";
