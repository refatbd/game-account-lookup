<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\Normalizer;

/**
 * Optional AU2/Dancing Idol-compatible adapter.
 *
 * The historical public endpoint is HTTP-only, so the built-in game definition
 * is disabled. Maintainers may point this adapter to an authorized HTTPS proxy
 * or explicitly accept insecure HTTP in their own configuration.
 */
final class DancingIdolProvider implements ProviderInterface
{
    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
    ) {
    }

    public function key(): string
    {
        return 'dancingidol';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config)
            && ($config['enabled'] ?? false) === true
            && !empty($config['endpoint']);
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = $game['providers'][$this->key()] ?? [];

        if (!$this->supports($game)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Dancing Idol provider is disabled or not configured.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $endpoint = (string) $config['endpoint'];
        if (str_starts_with(strtolower($endpoint), 'http://')
            && ($config['allowInsecureHttp'] ?? false) !== true) {
            return LookupResult::failure(
                ResultCode::PROVIDER_RESTRICTED,
                'Refusing an insecure HTTP provider endpoint.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $url = str_contains($endpoint, '{playerId}')
            ? str_replace('{playerId}', rawurlencode($playerId), $endpoint)
            : $endpoint . rawurlencode($playerId);

        $headers = is_array($config['headers'] ?? null) ? $config['headers'] : [];
        $response = $this->http->get($url, $headers);

        if ($response->failed()) {
            return LookupResult::failure(
                ResultCode::NETWORK_ERROR,
                $response->error ?? sprintf('Provider returned HTTP %d.', $response->status),
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $data = $response->json();
        if ($data === null) {
            return LookupResult::failure(
                ResultCode::INVALID_RESPONSE,
                'Provider returned a non-JSON response.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $paths = $config['nicknamePaths'] ?? ['data.rolename'];
        $nickname = Normalizer::nickname(Arr::firstFilled($data, is_array($paths) ? $paths : []));

        if ($nickname === null) {
            return LookupResult::failure(
                ResultCode::INVALID_PLAYER,
                (string) ($config['invalidMessage'] ?? 'Invalid player ID.'),
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $this->debug ? ['response' => $data] : [],
            );
        }

        return LookupResult::success(
            (string) ($game['code'] ?? ''),
            $playerId,
            $nickname,
            $this->key(),
            $zoneId,
        );
    }
}
