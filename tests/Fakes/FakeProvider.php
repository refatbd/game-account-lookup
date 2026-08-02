<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests\Fakes;

use Closure;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;

final class FakeProvider implements ProviderInterface
{
    public int $calls = 0;

    /**
     * @param Closure(array<string, mixed>, string, ?string): LookupResult $callback
     */
    public function __construct(
        private readonly string $providerKey,
        private readonly Closure $callback,
    ) {
    }

    public function key(): string
    {
        return $this->providerKey;
    }

    public function supports(array $game): bool
    {
        return isset($game['providers'][$this->providerKey]);
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $this->calls++;

        return ($this->callback)($game, $playerId, $zoneId);
    }
}
