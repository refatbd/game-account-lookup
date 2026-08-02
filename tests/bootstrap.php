<?php

declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefixes = [
        'Refatbd\\GameAccountLookup\\Tests\\' => __DIR__ . '/',
        'Refatbd\\GameAccountLookup\\' => dirname(__DIR__) . '/src/',
    ];

    foreach ($prefixes as $prefix => $base) {
        if (!str_starts_with($class, $prefix)) {
            continue;
        }

        $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
        $file = $base . $relative . '.php';

        if (is_file($file)) {
            require $file;
        }

        return;
    }
});
