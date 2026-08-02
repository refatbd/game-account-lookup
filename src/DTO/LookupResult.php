<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\DTO;

use JsonSerializable;
use Refatbd\GameAccountLookup\ResultCode;

final class LookupResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $meta
     * @param list<array<string, mixed>> $attempts
     */
    public function __construct(
        public readonly bool $ok,
        public readonly string $code,
        public readonly string $message,
        public readonly ?string $game = null,
        public readonly ?string $playerId = null,
        public readonly ?string $zoneId = null,
        public readonly ?string $nickname = null,
        public readonly ?string $provider = null,
        public readonly ?string $server = null,
        public readonly bool $cached = false,
        public readonly array $meta = [],
        public readonly array $attempts = [],
        public readonly ?string $country = null,
    ) {
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function success(
        string $game,
        string $playerId,
        string $nickname,
        string $provider,
        ?string $zoneId = null,
        ?string $server = null,
        array $meta = [],
        ?string $country = null,
    ): self {
        return new self(
            true,
            ResultCode::SUCCESS,
            'Game account found.',
            $game,
            $playerId,
            $zoneId,
            $nickname,
            $provider,
            $server,
            false,
            $meta,
            [],
            $country,
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    public static function failure(
        string $code,
        string $message,
        ?string $game = null,
        ?string $playerId = null,
        ?string $zoneId = null,
        ?string $provider = null,
        array $meta = [],
    ): self {
        return new self(
            false,
            $code,
            $message,
            $game,
            $playerId,
            $zoneId,
            null,
            $provider,
            null,
            false,
            $meta,
        );
    }

    /**
     * @param list<array<string, mixed>> $attempts
     */
    public function withAttempts(array $attempts): self
    {
        return new self(
            $this->ok,
            $this->code,
            $this->message,
            $this->game,
            $this->playerId,
            $this->zoneId,
            $this->nickname,
            $this->provider,
            $this->server,
            $this->cached,
            $this->meta,
            $attempts,
            $this->country,
        );
    }

    /**
     * @param array<string, mixed> $meta
     */
    public function withMeta(array $meta): self
    {
        return new self(
            $this->ok,
            $this->code,
            $this->message,
            $this->game,
            $this->playerId,
            $this->zoneId,
            $this->nickname,
            $this->provider,
            $this->server,
            $this->cached,
            $meta,
            $this->attempts,
            $this->country,
        );
    }

    public function asCached(): self
    {
        return new self(
            $this->ok,
            ResultCode::CACHED,
            'Game account found in cache.',
            $this->game,
            $this->playerId,
            $this->zoneId,
            $this->nickname,
            $this->provider,
            $this->server,
            true,
            $this->meta,
            $this->attempts,
            $this->country,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'ok' => $this->ok,
            'code' => $this->code,
            'message' => $this->message,
            'game' => $this->game,
            'player_id' => $this->playerId,
            'zone_id' => $this->zoneId,
            'nickname' => $this->nickname,
            'provider' => $this->provider,
            'server' => $this->server,
            'country' => $this->country,
            'cached' => $this->cached,
        ];

        if ($this->meta !== []) {
            $data['meta'] = $this->meta;
        }

        if ($this->attempts !== []) {
            $data['attempts'] = $this->attempts;
        }

        return $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
