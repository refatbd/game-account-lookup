<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Refatbd\GameAccountLookup\Cache\ArrayCache;
use Refatbd\GameAccountLookup\GameAccountLookup;

$lookup = GameAccountLookup::make([
    'cache' => new ArrayCache(),
    'cache_ttl' => 300,
    'timeout' => 12,
]);

$result = $lookup->check('freefire', '4422076728');

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
