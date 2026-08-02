<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$files = [
    $root . '/README.md',
    $root . '/docs/SUPPORTED_GAMES.md',
    $root . '/docs/PROVIDER_AVAILABILITY.md',
];
$before = [];
foreach ($files as $file) {
    $before[$file] = hash_file('sha256', $file);
}

ob_start();
require __DIR__ . '/generate-supported-games.php';
ob_end_clean();

$stale = [];
foreach ($files as $file) {
    if ($before[$file] !== hash_file('sha256', $file)) {
        $stale[] = substr($file, strlen($root) + 1);
    }
}

if ($stale !== []) {
    fwrite(STDERR, "Generated documentation was stale: " . implode(', ', $stale) . PHP_EOL);
    exit(1);
}

echo "Generated supported-game documentation is current.\n";
