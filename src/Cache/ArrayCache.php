<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Cache;

use Refatbd\GameAccountLookup\Contracts\CacheInterface;

final class ArrayCache implements CacheInterface
{
    /** @var array<string, array{value: mixed, expires_at: int}> */
    private array $items = [];

    public function get(string $key): mixed
    {
        $item = $this->items[$key] ?? null;

        if ($item === null) {
            return null;
        }

        if ($item['expires_at'] < time()) {
            unset($this->items[$key]);

            return null;
        }

        return $item['value'];
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        $this->items[$key] = [
            'value' => $value,
            'expires_at' => time() + max(1, $ttlSeconds),
        ];
    }

    public function forget(string $key): void
    {
        unset($this->items[$key]);
    }
}
