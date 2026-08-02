<?php

declare(strict_types=1);

require __DIR__ . '/_common.php';

$registry = new Refatbd\GameAccountLookup\Registry\GameRegistry();
$games = $registry->list();
usort($games, static fn (array $a, array $b): int => strcasecmp((string) $a['label'], (string) $b['label']));

template_json([
    'ok' => true,
    'count' => count($games),
    'games' => $games,
]);
