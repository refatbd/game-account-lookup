<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup;

use Refatbd\GameAccountLookup\Cache\NullCache;
use Refatbd\GameAccountLookup\Contracts\CacheInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpClient;
use Refatbd\GameAccountLookup\Providers\CodashopDynamicProvider;
use Refatbd\GameAccountLookup\Providers\CodashopBrowserProvider;
use Refatbd\GameAccountLookup\Providers\CodashopProvider;
use Refatbd\GameAccountLookup\Providers\DancingIdolProvider;
use Refatbd\GameAccountLookup\Providers\GopayGamesProvider;
use Refatbd\GameAccountLookup\Providers\GarenaProvider;
use Refatbd\GameAccountLookup\Providers\MidasbuyProvider;
use Refatbd\GameAccountLookup\Providers\MidasbuyBrowserProvider;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Throwable;

final class GameAccountLookup
{
    /** @var array<string, ProviderInterface> */
    private array $providers = [];

    public function __construct(
        private readonly GameRegistry $registry = new GameRegistry(),
        ?CacheInterface $cache = null,
        private readonly int $cacheTtl = 300,
        private readonly bool $debug = false,
    ) {
        $this->cache = $cache ?? new NullCache();
    }

    private CacheInterface $cache;

    /**
     * Create a ready-to-use instance with bundled providers.
     *
     * @param array{
     *   timeout?: int,
     *   connect_timeout?: int,
     *   verify_tls?: bool,
     *   debug?: bool,
     *   cache?: CacheInterface,
     *   cache_ttl?: int,
     *   session_cache?: CacheInterface,
     *   session_ttl?: int,
     *   logger?: callable(string, array<string, mixed>): void
     * } $options
     */
    public static function make(array $options = []): self
    {
        $debug = (bool) ($options['debug'] ?? false);
        $http = new HttpClient(
            timeout: (int) ($options['timeout'] ?? 12),
            connectTimeout: (int) ($options['connect_timeout'] ?? 5),
            verifyTls: (bool) ($options['verify_tls'] ?? true),
            logger: $options['logger'] ?? null,
            sessionCache: $options['session_cache'] ?? ($options['cache'] ?? null),
            sessionTtl: (int) ($options['session_ttl'] ?? 1800),
        );

        $instance = new self(
            registry: new GameRegistry(),
            cache: $options['cache'] ?? null,
            cacheTtl: (int) ($options['cache_ttl'] ?? 300),
            debug: $debug,
        );

        return $instance
            ->registerProvider(new GarenaProvider($http, $debug))
            ->registerProvider(new MidasbuyProvider($http, $debug))
            ->registerProvider(new MidasbuyBrowserProvider(dirname(__DIR__), $debug))
            ->registerProvider(new CodashopBrowserProvider(dirname(__DIR__), $debug))
            ->registerProvider(new GopayGamesProvider($http, $debug))
            ->registerProvider(new CodashopProvider($http, $debug))
            ->registerProvider(new CodashopDynamicProvider($http, $debug))
            ->registerProvider(new DancingIdolProvider($http, $debug));
    }

    public function registerProvider(ProviderInterface $provider): self
    {
        $this->providers[$provider->key()] = $provider;

        return $this;
    }

    /**
     * @param array<string, mixed> $definition
     */
    public function registerGame(string $code, array $definition): self
    {
        $this->registry->register($code, $definition);

        return $this;
    }

    public function registry(): GameRegistry
    {
        return $this->registry;
    }

    /**
     * @param list<string>|null $providerOrder
     */
    public function check(
        string $game,
        string|int $playerId,
        string|int|null $zoneId = null,
        ?array $providerOrder = null,
        bool $bypassCache = false,
    ): LookupResult {
        $playerId = Normalizer::identifier($playerId);
        $zoneId = $zoneId === null ? null : Normalizer::identifier($zoneId);
        $definition = $this->registry->get($game);

        if ($definition === null) {
            return LookupResult::failure(
                ResultCode::GAME_NOT_FOUND,
                sprintf('Unknown game "%s".', $game),
                playerId: $playerId,
                zoneId: $zoneId,
            );
        }

        $canonical = (string) $definition['code'];

        if ($playerId === '') {
            return LookupResult::failure(
                ResultCode::INVALID_PLAYER,
                'Player ID cannot be empty.',
                $canonical,
                $playerId,
                $zoneId,
            );
        }

        $gameStatus = (string) ($definition['status'] ?? 'active');
        if ($gameStatus !== 'active') {
            $notes = trim((string) ($definition['notes'] ?? ''));
            $message = sprintf('Live lookup for %s is currently unavailable (%s).', (string) ($definition['label'] ?? $canonical), $gameStatus);
            if ($notes !== '') {
                $message .= ' ' . $notes;
            }

            return LookupResult::failure(
                ResultCode::MAINTENANCE_REQUIRED,
                $message,
                $canonical,
                $playerId,
                $zoneId,
                meta: [
                    'game_status' => $gameStatus,
                    'maintenance_notes' => $notes !== '' ? $notes : null,
                    'configured_providers' => array_keys((array) ($definition['providers'] ?? [])),
                ],
            );
        }

        if (($definition['requiresZone'] ?? false) === true && ($zoneId === null || $zoneId === '')) {
            return LookupResult::failure(
                ResultCode::ZONE_REQUIRED,
                'This game requires a zone or server ID.',
                $canonical,
                $playerId,
                $zoneId,
            );
        }

        $order = $providerOrder ?? array_keys((array) ($definition['providers'] ?? []));
        $cacheKey = $this->cacheKey($canonical, $playerId, $zoneId, $order);
        $cached = $bypassCache ? null : $this->cache->get($cacheKey);

        if ($cached instanceof LookupResult && $cached->ok) {
            return $cached->asCached();
        }

        $attempts = [];
        $last = null;

        foreach ($order as $providerKey) {
            $providerKey = (string) $providerKey;
            $provider = $this->providers[$providerKey] ?? null;

            if ($provider === null || !$provider->supports($definition)) {
                $attempts[] = [
                    'provider' => $providerKey,
                    'code' => ResultCode::PROVIDER_NOT_CONFIGURED,
                    'message' => 'Provider is unavailable or disabled.',
                ];
                continue;
            }

            try {
                $result = $provider->lookup($definition, $playerId, $zoneId);
            } catch (Throwable $exception) {
                $message = 'Provider request failed before a valid response was received.';
                if (str_contains(strtolower($exception->getMessage()), 'curl_init')) {
                    $message = 'PHP cURL extension is missing or unavailable.';
                } elseif ($this->debug) {
                    $message = $exception->getMessage();
                }

                $result = LookupResult::failure(
                    ResultCode::NETWORK_ERROR,
                    $message,
                    $canonical,
                    $playerId,
                    $zoneId,
                    $providerKey,
                    [
                        'exception_type' => $exception::class,
                        'exception_message' => $this->debug ? $exception->getMessage() : null,
                    ],
                );
            }

            $attempts[] = [
                'provider' => $providerKey,
                'ok' => $result->ok,
                'code' => $result->code,
                'message' => $result->message,
                'nickname' => $result->nickname,
                'cached' => $result->cached,
                'meta' => $result->meta,
            ];
            $last = $result;

            if ($result->ok) {
                $result = $result->withAttempts($attempts);
                if (!$bypassCache) {
                    $this->cache->put($cacheKey, $result, $this->cacheTtl);
                }

                return $result;
            }
        }

        if ($last !== null) {
            return new LookupResult(
                false,
                ResultCode::ALL_PROVIDERS_FAILED,
                'No configured provider could resolve this account.',
                $canonical,
                $playerId,
                $zoneId,
                null,
                $last->provider,
                null,
                false,
                $last->meta,
                $attempts,
            );
        }

        return LookupResult::failure(
            ResultCode::PROVIDER_NOT_CONFIGURED,
            'No enabled provider is configured for this game.',
            $canonical,
            $playerId,
            $zoneId,
            meta: ['attempts' => $attempts],
        );
    }

    public function forget(string $game, string|int $playerId, string|int|null $zoneId = null): void
    {
        $canonical = $this->registry->resolve($game) ?? Normalizer::gameCode($game);
        $definition = $this->registry->get($game);
        $orders = [[]];

        if ($definition !== null) {
            $providers = array_keys((array) ($definition['providers'] ?? []));
            $orders = [$providers];
            foreach ($providers as $provider) {
                $orders[] = [(string) $provider];
            }
        }

        foreach ($orders as $order) {
            $this->cache->forget(
                $this->cacheKey(
                    $canonical,
                    Normalizer::identifier($playerId),
                    $zoneId === null ? null : Normalizer::identifier($zoneId),
                    $order,
                ),
            );
        }
    }

    /**
     * @param list<string> $providerOrder
     */
    private function cacheKey(string $game, string $playerId, ?string $zoneId, array $providerOrder): string
    {
        $scope = $providerOrder === [] ? 'none' : implode(',', array_map('strval', $providerOrder));

        return 'game-account-lookup:' . hash('sha256', implode('|', [$game, $playerId, $zoneId ?? '', $scope]));
    }
}
