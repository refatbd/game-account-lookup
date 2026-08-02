<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\GopayMetadataResolver;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;

final class GopayGamesProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://gopay.co.id/games/v1/order/user-account';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
        private readonly GopayMetadataResolver $resolver = new GopayMetadataResolver(),
    ) {
    }

    public function key(): string
    {
        return 'gopaygames';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;
        if (!is_array($config) || ($config['enabled'] ?? true) !== true) {
            return false;
        }

        return !empty($config['code'])
            || !empty($config['codeCandidates'])
            || !empty($config['productPage']);
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = $game['providers'][$this->key()] ?? [];
        if (!$this->supports($game)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'GoPay Games is not configured or is currently disabled for this game.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                ['availability_status' => $config['availabilityStatus'] ?? null],
            );
        }

        $resolvedZone = $this->resolveZone($game, $zoneId);
        $fallbackCodes = array_values(array_unique(array_filter([
            isset($config['code']) ? (string) $config['code'] : null,
            ...array_map('strval', (array) ($config['codeCandidates'] ?? [])),
        ])));
        $preflightMeta = [];
        $codes = $fallbackCodes;

        $productPage = trim((string) ($config['productPage'] ?? ''));
        if ($productPage !== '') {
            $page = $this->http->get($productPage, [
                'Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en;q=0.8',
                'Referer' => 'https://gopay.co.id/games',
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
            ]);
            $preflightMeta = array_merge(ProviderDiagnostics::fromResponse($page, $page->failed()), [
                'gopay_product_page' => $productPage,
                'stage' => 'product-page',
            ]);

            if ($page->successful()) {
                $metadata = $this->resolver->resolve($page->body, $fallbackCodes);
                $preflightMeta['gopay_code_source'] = $metadata['source'];
                $preflightMeta['gopay_code_candidates'] = $metadata['candidates'];
                if ($metadata['maintenance']) {
                    return LookupResult::failure(
                        ResultCode::PROVIDER_MAINTENANCE,
                        'The official GoPay Games product page reports that this service is under maintenance.',
                        $game['code'] ?? null,
                        $playerId,
                        $resolvedZone,
                        $this->key(),
                        $preflightMeta,
                    );
                }
                if ($metadata['candidates'] !== []) {
                    $codes = $metadata['candidates'];
                }
            } elseif ($codes === []) {
                return LookupResult::failure(
                    ResultCode::NETWORK_ERROR,
                    $page->error ?? sprintf('GoPay Games product page returned HTTP %d.', $page->status),
                    $game['code'] ?? null,
                    $playerId,
                    $resolvedZone,
                    $this->key(),
                    $preflightMeta,
                );
            } else {
                $preflightMeta['preflight_warning'] = 'Product page could not be loaded; configured code fallback was used.';
            }
        }

        if ($codes === []) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'GoPay Games product code could not be discovered.',
                $game['code'] ?? null,
                $playerId,
                $resolvedZone,
                $this->key(),
                $preflightMeta,
            );
        }

        $codeAttempts = [];
        $last = null;
        foreach ($codes as $code) {
            $response = $this->request((string) $code, $playerId, $resolvedZone, $config, $productPage);
            $result = $this->parseResponse($game, $playerId, $resolvedZone, (string) $code, $response, $config, $preflightMeta);
            $codeAttempts[] = [
                'code' => (string) $code,
                'ok' => $result->ok,
                'result_code' => $result->code,
                'message' => $result->message,
                'meta' => $result->meta,
            ];
            $last = $result;

            if ($result->ok) {
                return $result->withMeta(array_merge($result->meta, [
                    'gopay_code' => (string) $code,
                    'code_attempts' => $codeAttempts,
                ]));
            }

            // Try another candidate only when the server says the product/code
            // is unknown. An unknown player is an account result, not evidence
            // that another product code should be used.
            if (!$this->isProductCodeError($response, $result->message)) {
                break;
            }
        }

        return ($last ?? LookupResult::failure(
            ResultCode::INVALID_RESPONSE,
            'GoPay Games did not return a usable result.',
            $game['code'] ?? null,
            $playerId,
            $resolvedZone,
            $this->key(),
        ))->withMeta(array_merge($last?->meta ?? [], [
            'code_attempts' => $codeAttempts,
        ]));
    }

    /** @param array<string, mixed> $config */
    private function request(string $code, string $playerId, ?string $zoneId, array $config, string $productPage): HttpResponse
    {
        return $this->http->postJson(
            (string) ($config['endpoint'] ?? self::ENDPOINT),
            [
                'code' => $code,
                'data' => [
                    'userId' => $playerId,
                    'zoneId' => (string) ($zoneId ?? ''),
                ],
            ],
            [
                'Accept' => 'application/json, text/plain, */*',
                'Origin' => 'https://gopay.co.id',
                'Referer' => $productPage !== '' ? $productPage : 'https://gopay.co.id/games',
                'User-Agent' => 'Mozilla/5.0 (compatible; GameAccountLookup/1.0; +https://github.com/refatbd)',
            ],
        );
    }

    /** @param array<string, mixed> $game @param array<string, mixed> $config @param array<string, mixed> $preflightMeta */
    private function parseResponse(
        array $game,
        string $playerId,
        ?string $zoneId,
        string $code,
        HttpResponse $response,
        array $config,
        array $preflightMeta,
    ): LookupResult {
        $meta = array_merge($preflightMeta, ProviderDiagnostics::fromResponse($response, $response->failed()), [
            'gopay_code' => $code,
            'stage' => 'account-validation',
        ]);

        if (!$response->failed()) {
            unset($meta['response_preview'], $meta['transport_error']);
        }

        if ($response->status === 429) {
            return LookupResult::failure(ResultCode::RATE_LIMITED, 'GoPay Games rate limit reached.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        $data = $response->json();
        $message = is_array($data)
            ? (string) Arr::firstFilled($data, ['message', 'error', 'data.message', 'errorMessage', 'msg'])
            : '';

        if ($response->failed()) {
            if (is_array($data) && $this->isPlayerError($message)) {
                return LookupResult::failure(ResultCode::INVALID_PLAYER, $message !== '' ? $message : 'Unknown player.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
            }
            return LookupResult::failure(
                ResultCode::NETWORK_ERROR,
                $response->error ?? ($message !== '' ? $message : sprintf('GoPay Games returned HTTP %d.', $response->status)),
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $meta,
            );
        }

        if ($data === null) {
            return LookupResult::failure(ResultCode::INVALID_RESPONSE, 'GoPay Games returned an empty or non-JSON response.', $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
        }

        $paths = $config['nicknamePaths'] ?? [
            'data.username', 'data.userAccount', 'data.nickname', 'data.name',
            'username', 'userAccount',
        ];
        $nickname = Normalizer::nickname(Arr::firstFilled($data, is_array($paths) ? $paths : []));
        $success = strtolower((string) ($data['message'] ?? '')) === 'success'
            || ($data['success'] ?? false) === true
            || ($data['status'] ?? false) === true
            || $nickname !== null;

        if ($success && $nickname !== null) {
            if ($this->debug) {
                $meta['response'] = $data;
            }
            return LookupResult::success((string) ($game['code'] ?? ''), $playerId, $nickname, $this->key(), $zoneId, null, $meta);
        }

        if ($message === '') {
            $message = 'Invalid player ID or zone.';
        }
        if ($this->debug) {
            $meta['response'] = $data;
        }

        return LookupResult::failure(ResultCode::INVALID_PLAYER, $message, $game['code'] ?? null, $playerId, $zoneId, $this->key(), $meta);
    }

    private function isPlayerError(string $message): bool
    {
        $message = strtolower($message);
        foreach (['unknown user', 'unknown player', 'invalid user', 'invalid player', 'user not found', 'player not found'] as $needle) {
            if (str_contains($message, $needle)) {
                return true;
            }
        }
        return false;
    }

    private function isProductCodeError(HttpResponse $response, string $message): bool
    {
        $haystack = strtolower($message . ' ' . $response->body);
        foreach (['unknown product', 'unknown game', 'invalid code', 'product code', 'game code', 'product not found'] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }
        return false;
    }

    /** @param array<string, mixed> $game */
    private function resolveZone(array $game, ?string $zoneId): ?string
    {
        if ($zoneId === null || $zoneId === '') {
            return $zoneId;
        }
        $map = $game['servers'] ?? [];
        $normalized = Normalizer::gameCode($zoneId);
        return is_array($map) && array_key_exists($normalized, $map) ? (string) $map[$normalized] : $zoneId;
    }
}
