<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Template;

final class RateLimiter
{
    public function __construct(
        private readonly string $directory,
        private readonly int $limit,
        private readonly int $windowSeconds,
    ) {
        if (!is_dir($this->directory)) {
            @mkdir($this->directory, 0775, true);
        }
    }

    /** @return array{allowed: bool, remaining: int, reset_at: int} */
    public function hit(string $identity): array
    {
        $path = rtrim($this->directory, '/\\') . DIRECTORY_SEPARATOR . hash('sha256', $identity) . '.json';
        $now = time();
        $record = ['started_at' => $now, 'count' => 0];

        $handle = @fopen($path, 'c+');
        if ($handle === false) {
            return ['allowed' => true, 'remaining' => $this->limit, 'reset_at' => $now + $this->windowSeconds];
        }

        try {
            flock($handle, LOCK_EX);
            $contents = stream_get_contents($handle);
            $decoded = $contents !== false && $contents !== '' ? json_decode($contents, true) : null;
            if (is_array($decoded)) {
                $record = [
                    'started_at' => (int) ($decoded['started_at'] ?? $now),
                    'count' => (int) ($decoded['count'] ?? 0),
                ];
            }

            if (($record['started_at'] + $this->windowSeconds) <= $now) {
                $record = ['started_at' => $now, 'count' => 0];
            }

            $record['count']++;
            $allowed = $record['count'] <= max(1, $this->limit);
            $remaining = max(0, $this->limit - $record['count']);
            $resetAt = $record['started_at'] + $this->windowSeconds;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, (string) json_encode($record));
            fflush($handle);
            flock($handle, LOCK_UN);

            return ['allowed' => $allowed, 'remaining' => $remaining, 'reset_at' => $resetAt];
        } finally {
            fclose($handle);
        }
    }
}
