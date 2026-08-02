<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Laravel;

use Refatbd\GameAccountLookup\Contracts\CacheInterface;

final class LaravelCache implements CacheInterface
{
    public function __construct(private readonly object $repository)
    {
    }

    public function get(string $key): mixed
    {
        return $this->repository->get($key);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->repository->put($key, $value, $ttlSeconds);
    }

    public function forget(string $key): void
    {
        $this->repository->forget($key);
    }
}
