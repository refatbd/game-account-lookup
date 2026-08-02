<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Cache;

use Refatbd\GameAccountLookup\Contracts\CacheInterface;

final class NullCache implements CacheInterface
{
    public function get(string $key): mixed
    {
        return null;
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
    }

    public function forget(string $key): void
    {
    }
}
