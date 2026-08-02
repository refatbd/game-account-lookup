<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Template;

use Refatbd\GameAccountLookup\Contracts\CacheInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;

final class FileCache implements CacheInterface
{
    public function __construct(private readonly string $directory)
    {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    public function get(string $key): mixed
    {
        $path = $this->path($key);
        if (!is_file($path)) {
            return null;
        }

        $payload = @file_get_contents($path);
        if ($payload === false) {
            return null;
        }

        $record = json_decode($payload, true);
        if (!is_array($record) || !isset($record['expires_at'], $record['result'])) {
            @unlink($path);
            return null;
        }

        if ((int) $record['expires_at'] < time()) {
            @unlink($path);
            return null;
        }

        $result = $record['result'];
        if (!is_array($result)) {
            return null;
        }

        return new LookupResult(
            (bool) ($result['ok'] ?? false),
            (string) ($result['code'] ?? ''),
            (string) ($result['message'] ?? ''),
            isset($result['game']) ? (string) $result['game'] : null,
            isset($result['player_id']) ? (string) $result['player_id'] : null,
            isset($result['zone_id']) && $result['zone_id'] !== null ? (string) $result['zone_id'] : null,
            isset($result['nickname']) && $result['nickname'] !== null ? (string) $result['nickname'] : null,
            isset($result['provider']) && $result['provider'] !== null ? (string) $result['provider'] : null,
            isset($result['server']) && $result['server'] !== null ? (string) $result['server'] : null,
            (bool) ($result['cached'] ?? false),
            is_array($result['meta'] ?? null) ? $result['meta'] : [],
            is_array($result['attempts'] ?? null) ? $result['attempts'] : [],
        );
    }

    public function put(string $key, mixed $value, int $ttlSeconds): void
    {
        if (!$value instanceof LookupResult) {
            return;
        }

        $record = json_encode([
            'expires_at' => time() + max(1, $ttlSeconds),
            'result' => $value->toArray(),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($record === false) {
            return;
        }

        $path = $this->path($key);
        $temporary = $path . '.' . bin2hex(random_bytes(4)) . '.tmp';
        if (@file_put_contents($temporary, $record, LOCK_EX) !== false) {
            @rename($temporary, $path);
        }
        @unlink($temporary);
    }

    public function forget(string $key): void
    {
        @unlink($this->path($key));
    }

    private function path(string $key): string
    {
        return rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . hash('sha256', $key) . '.json';
    }
}
