<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \Refatbd\GameAccountLookup\DTO\LookupResult check(string $game, string|int $playerId, string|int|null $zoneId = null, ?array $providerOrder = null)
 * @method static \Refatbd\GameAccountLookup\Registry\GameRegistry registry()
 * @method static void forget(string $game, string|int $playerId, string|int|null $zoneId = null)
 */
final class GameAccountLookup extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'game-account-lookup';
    }
}
