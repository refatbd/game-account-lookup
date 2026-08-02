<?php

declare(strict_types=1);

$projectRoot = dirname(__DIR__);
$composerAutoload = $projectRoot . '/vendor/autoload.php';

if (is_file($composerAutoload)) {
    require $composerAutoload;
}

spl_autoload_register(static function (string $class) use ($projectRoot): void {
    $prefixes = [
        'Refatbd\\GameAccountLookup\\Template\\' => __DIR__ . '/lib/',
        'Refatbd\\GameAccountLookup\\' => $projectRoot . '/src/',
    ];

    foreach ($prefixes as $prefix => $basePath) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $path = $basePath . $relative . '.php';

        if (is_file($path)) {
            require $path;
        }

        return;
    }
});

$config = require __DIR__ . '/config.php';

foreach (['storage_path', 'storage_path/cache', 'storage_path/rate-limits'] as $entry) {
    $path = str_replace('storage_path', (string) $config['storage_path'], $entry);
    if (!is_dir($path)) {
        @mkdir($path, 0775, true);
    }
}

return $config;
