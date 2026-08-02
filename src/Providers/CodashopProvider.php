<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;

/**
 * Classic Codashop adapter using preconfigured product metadata.
 *
 * New integrations should prefer codashop_dynamic. This adapter remains useful
 * as a low-overhead fallback and now supports multiple static regional profiles.
 */
final class CodashopProvider implements ProviderInterface
{
    private const ENDPOINT = 'https://order-sg.codashop.com/initPayment.action';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
    ) {
    }

    public function key(): string
    {
        return 'codashop';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config) && ($config['enabled'] ?? true) === true;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = $game['providers'][$this->key()] ?? [];

        if (!is_array($config) || ($config['enabled'] ?? true) !== true) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Codashop is not configured for this game.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $profiles = $this->profiles($config);
        $attempts = [];
        $last = null;

        foreach ($profiles as $index => $profile) {
            $profile = array_replace($this->commonProfileConfig($config), $profile);
            $profileName = (string) ($profile['name'] ?? ('profile-' . ($index + 1)));
            $result = $this->lookupProfile($game, $profile, $playerId, $zoneId, $profileName);
            $attempts[] = [
                'profile' => $profileName,
                'ok' => $result->ok,
                'code' => $result->code,
                'message' => $result->message,
                'nickname' => $result->nickname,
                'meta' => $result->meta,
            ];
            $last = $result;

            if ($result->ok) {
                return $result->withMeta(array_merge($result->meta, [
                    'codashop_profile' => $profileName,
                    'profile_attempts' => $attempts,
                ]));
            }
        }

        if ($last !== null) {
            return $last->withMeta(array_merge($last->meta, [
                'profile_attempts' => $attempts,
                'profiles_checked' => count($attempts),
            ]));
        }

        return LookupResult::failure(
            ResultCode::PROVIDER_NOT_CONFIGURED,
            'No usable classic Codashop profile is configured.',
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
        );
    }

    /**
     * @param array<string, mixed> $game
     * @param array<string, mixed> $config
     */
    private function lookupProfile(array $game, array $config, string $playerId, ?string $zoneId, string $profileName): LookupResult
    {
        if (($config['vppId'] ?? '') === '' || ($config['price'] ?? '') === '') {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Classic Codashop product metadata is incomplete for this profile.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                ['codashop_profile' => $profileName],
            );
        }

        $resolvedZone = $this->resolveZone($game, $config, $zoneId);
        $form = [
            'voucherPricePoint.id' => (string) ($config['vppId'] ?? ''),
            'voucherPricePoint.price' => (string) ($config['price'] ?? ''),
            'voucherPricePoint.variablePrice' => (string) ($config['variablePrice'] ?? 0),
            'user.userId' => $playerId,
            'voucherTypeName' => (string) ($config['voucherTypeName'] ?? strtoupper((string) ($game['code'] ?? ''))),
            'shopLang' => (string) ($config['shopLang'] ?? 'id_ID'),
        ];

        if ($resolvedZone !== null) {
            $form['user.zoneId'] = $resolvedZone;
        }

        foreach (['voucherTypeId', 'lvtId', 'gvtId', 'pcId', 'dynamicSkuToken', 'pricePointDynamicSkuToken', 'pricingEngineToken', 'userVariablePrice'] as $field) {
            if (array_key_exists($field, $config) && $config[$field] !== null && $config[$field] !== '') {
                $form[$field] = (string) $config[$field];
            }
        }

        foreach ((array) ($config['form'] ?? []) as $field => $value) {
            if (is_string($field) && (is_scalar($value) || $value === null)) {
                $form[$field] = $value === null ? '' : (string) $value;
            }
        }

        $response = $this->http->postForm(
            (string) ($config['endpoint'] ?? self::ENDPOINT),
            $form,
            $this->headers($config),
        );

        if ($response->status === 429) {
            return LookupResult::failure(
                ResultCode::RATE_LIMITED,
                'Codashop rate limit reached.',
                $game['code'] ?? null,
                $playerId,
                $resolvedZone,
                $this->key(),
                $this->meta($response, $profileName, true),
            );
        }

        if ($response->failed()) {
            return LookupResult::failure(
                ResultCode::NETWORK_ERROR,
                $response->error ?? sprintf('Codashop returned HTTP %d.', $response->status),
                $game['code'] ?? null,
                $playerId,
                $resolvedZone,
                $this->key(),
                $this->meta($response, $profileName, true),
            );
        }

        $data = $response->json();

        if ($data === null) {
            return LookupResult::failure(
                ResultCode::INVALID_RESPONSE,
                'Codashop returned an empty or non-JSON response.',
                $game['code'] ?? null,
                $playerId,
                $resolvedZone,
                $this->key(),
                $this->meta($response, $profileName, true),
            );
        }

        $paths = $config['nicknamePaths'] ?? [
            'confirmationFields.username',
            'confirmationFields.roles.0.role',
            'confirmationFields.apiResult',
            'confirmationFields.nickname',
            'data.username',
            'data.nickname',
        ];
        $nickname = Normalizer::nickname(Arr::firstFilled($data, is_array($paths) ? $paths : []));

        if ($nickname !== null) {
            $serverPath = (string) ($config['serverPath'] ?? '');
            $server = $serverPath !== '' ? Normalizer::nickname(Arr::get($data, $serverPath)) : null;

            return LookupResult::success(
                (string) ($game['code'] ?? ''),
                $playerId,
                $nickname,
                $this->key(),
                $resolvedZone,
                $server,
                $this->debug ? $this->meta($response, $profileName) : ['codashop_profile' => $profileName],
            );
        }

        $message = trim((string) Arr::firstFilled($data, ['errorMsg', 'errorMessage', 'message', 'msg']));
        if ($message === '') {
            $message = (string) ($config['invalidMessage'] ?? 'Invalid player ID or zone.');
        }

        $code = $this->isRegionBlocked($data, $message)
            ? ResultCode::PROVIDER_RESTRICTED
            : ResultCode::INVALID_PLAYER;
        $meta = $this->meta($response, $profileName);
        if ($this->debug) {
            $meta['response'] = $data;
        }

        return LookupResult::failure(
            $code,
            $message,
            $game['code'] ?? null,
            $playerId,
            $resolvedZone,
            $this->key(),
            $meta,
        );
    }

    /** @param array<string, mixed> $config @return list<array<string, mixed>> */
    private function profiles(array $config): array
    {
        $profiles = $config['profiles'] ?? null;
        if (is_array($profiles) && $profiles !== []) {
            return array_values(array_filter($profiles, 'is_array'));
        }

        return [$config];
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function commonProfileConfig(array $config): array
    {
        unset($config['profiles']);

        return $config;
    }

    /**
     * @param array<string, mixed> $game
     * @param array<string, mixed> $config
     */
    private function resolveZone(array $game, array $config, ?string $zoneId): ?string
    {
        $mode = $config['zone'] ?? null;

        if ($mode === null || $mode === false) {
            return null;
        }

        if ($mode !== 'user') {
            return (string) $mode;
        }

        if ($zoneId === null || $zoneId === '') {
            return null;
        }

        $filter = $config['zoneFilter'] ?? null;
        if ($filter === 'digits') {
            $zoneId = (string) preg_replace('/\D+/', '', $zoneId);
        }

        $map = $game['servers'] ?? [];
        $normalized = Normalizer::gameCode($zoneId);

        if (is_array($map) && array_key_exists($normalized, $map)) {
            return (string) $map[$normalized];
        }

        return $zoneId;
    }

    /** @param array<string, mixed> $data */
    private function isRegionBlocked(array $data, string $message): bool
    {
        $code = strtolower((string) ($data['errorCode'] ?? $data['code'] ?? ''));
        $haystack = strtolower($message . ' ' . (string) ($data['errorMsg'] ?? ''));

        if (in_array($code, ['-200', 'region_blocked', 'country_blocked', 'provider_restricted'], true)) {
            return true;
        }

        foreach ([
            'topup region blocked',
            'top-up region blocked',
            'region blocked',
            'not available in your region',
            'choose another region',
            'country is not supported',
            'origin or region',
            'wrong storefront',
        ] as $needle) {
            if (str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, mixed> $config @return array<string, string> */
    private function headers(array $config): array
    {
        $pageUrl = (string) ($config['pageUrl'] ?? $config['referer'] ?? 'https://www.codashop.com/');
        $locale = (string) ($config['locale'] ?? 'id-ID');
        $language = strtolower(substr($locale, 0, 2));

        return [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => $locale . ',' . $language . ';q=0.9,en;q=0.8',
            'Origin' => 'https://www.codashop.com',
            'Referer' => $pageUrl,
            'X-Requested-With' => 'XMLHttpRequest',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ];
    }

    /** @return array<string, mixed> */
    private function meta(HttpResponse $response, string $profileName, bool $includePreview = false): array
    {
        return array_merge(
            ProviderDiagnostics::fromResponse($response, $includePreview),
            ['codashop_profile' => $profileName, 'metadata_source' => 'static-registry'],
        );
    }
}
