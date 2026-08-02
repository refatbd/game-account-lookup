<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Contracts;

use Refatbd\GameAccountLookup\DTO\LookupResult;

interface ProviderInterface
{
    public function key(): string;

    /**
     * @param array<string, mixed> $game
     */
    public function supports(array $game): bool;

    /**
     * @param array<string, mixed> $game
     */
    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult;
}
