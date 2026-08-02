<?php

$payload = json_decode(stream_get_contents(STDIN), true);
echo json_encode([
    'ok' => true,
    'code' => 'SUCCESS',
    'message' => 'Game account found through browser validation.',
    'player_id' => $payload['player_id'] ?? null,
    'nickname' => 'Real Browser Player',
    'country' => 'BD',
    'provider' => 'codashop_browser',
    'meta' => ['purchase_submitted' => false],
], JSON_UNESCAPED_SLASHES);
