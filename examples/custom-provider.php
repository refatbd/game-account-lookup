<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\GameAccountLookup;
use Refatbd\GameAccountLookup\Providers\CallbackProvider;
use Refatbd\GameAccountLookup\ResultCode;

$provider = new CallbackProvider(
    'my_provider',
    static function (array $game, string $playerId, ?string $zoneId): LookupResult {
        // Replace this example with an official SDK, your own API, or database.
        $nickname = ['123456' => 'ExamplePlayer'][$playerId] ?? null;

        return $nickname !== null
            ? LookupResult::success($game['code'], $playerId, $nickname, 'my_provider', $zoneId)
            : LookupResult::failure(
                ResultCode::INVALID_PLAYER,
                'Player not found.',
                $game['code'],
                $playerId,
                $zoneId,
                'my_provider',
            );
    },
);

$lookup = GameAccountLookup::make()
    ->registerProvider($provider)
    ->registerGame('my-game', [
        'label' => 'My Game',
        'aliases' => ['mg'],
        'requiresZone' => false,
        'providers' => [
            'my_provider' => [],
        ],
    ]);

print_r($lookup->check('mg', '123456')->toArray());
