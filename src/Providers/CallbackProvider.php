<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Closure;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;

/**
 * Lightweight provider adapter for applications that already have a lookup
 * function, internal API, official SDK, or database.
 */
final class CallbackProvider implements ProviderInterface
{
    /**
     * @param Closure(array<string, mixed>, string, ?string): LookupResult $callback
     * @param Closure(array<string, mixed>): bool|null $supports
     */
    public function __construct(
        private readonly string $providerKey,
        private readonly Closure $callback,
        private readonly ?Closure $supports = null,
    ) {
    }

    public function key(): string
    {
        return $this->providerKey;
    }

    public function supports(array $game): bool
    {
        if ($this->supports !== null) {
            return (bool) ($this->supports)($game);
        }

        return isset($game['providers'][$this->providerKey]);
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        return ($this->callback)($game, $playerId, $zoneId);
    }
}
