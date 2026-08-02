<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Http;

final class HttpResponse
{
    /**
     * @param array<string, list<string>> $headers
     */
    public function __construct(
        public readonly int $status,
        public readonly string $body,
        public readonly array $headers = [],
        public readonly ?string $error = null,
        public readonly ?int $durationMs = null,
        public readonly ?string $effectiveUrl = null,
    ) {
    }

    public function successful(): bool
    {
        return $this->error === null && $this->status >= 200 && $this->status < 300;
    }

    public function failed(): bool
    {
        return !$this->successful();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function json(): ?array
    {
        try {
            $decoded = json_decode($this->body, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : null;
        } catch (\JsonException) {
            return null;
        }
    }
}
