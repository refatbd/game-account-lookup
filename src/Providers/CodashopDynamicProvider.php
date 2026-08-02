<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Providers;

use Refatbd\GameAccountLookup\Contracts\HttpClientInterface;
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\Arr;
use Refatbd\GameAccountLookup\Support\CodashopMetadataResolver;
use Refatbd\GameAccountLookup\Support\Normalizer;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;

/**
 * Region-aware Codashop storefront provider.
 *
 * The adapter opens the real regional product page, retains its cookies,
 * discovers current price-point/order metadata, and performs account
 * confirmation. A region-blocked storefront is treated as a routing result,
 * not proof that the player ID is invalid, so the next configured storefront
 * is tried automatically.
 */
final class CodashopDynamicProvider implements ProviderInterface
{
    private const BASE_URL = 'https://www.codashop.com';
    private const ORDER_TOKEN_ENDPOINT = 'https://shopapi.codashop.com/productPage/createOrderToken';
    private const INIT_ENDPOINT = 'https://order-sg.codashop.com/initPayment.action';

    public function __construct(
        private readonly HttpClientInterface $http,
        private readonly bool $debug = false,
        private readonly CodashopMetadataResolver $resolver = new CodashopMetadataResolver(),
    ) {
    }

    public function key(): string
    {
        return 'codashop_dynamic';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;
        if (!is_array($config) || ($config['enabled'] ?? true) !== true) {
            return false;
        }

        foreach ($this->profiles($config) as $profile) {
            if (isset($profile['pageUrl']) && is_string($profile['pageUrl']) && $profile['pageUrl'] !== '') {
                return true;
            }
        }

        return false;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = $game['providers'][$this->key()] ?? [];

        if (!is_array($config) || !$this->supports($game)) {
            return LookupResult::failure(
                ResultCode::PROVIDER_NOT_CONFIGURED,
                'Dynamic Codashop storefront configuration is incomplete.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
            );
        }

        $attempts = [];
        $last = null;

        foreach ($this->profiles($config) as $index => $profile) {
            $profile = array_replace($this->commonProfileConfig($config), $profile);
            $name = (string) ($profile['name'] ?? ('profile-' . ($index + 1)));

            if (method_exists($this->http, 'clearCookies')) {
                $this->http->clearCookies();
            }

            $result = $this->lookupProfile($game, $profile, $playerId, $zoneId, $name);
            $attempts[] = [
                'profile' => $name,
                'page_url' => $profile['pageUrl'] ?? null,
                'ok' => $result->ok,
                'code' => $result->code,
                'message' => $result->message,
                'nickname' => $result->nickname,
                'meta' => $result->meta,
            ];
            $last = $result;

            if ($result->ok) {
                return $result->withMeta(array_merge($result->meta, [
                    'codashop_profile' => $name,
                    'profile_attempts' => $attempts,
                    'profiles_checked' => count($attempts),
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
            'No usable Codashop storefront profile is configured.',
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
        );
    }

    /**
     * @param array<string, mixed> $game
     * @param array<string, mixed> $profile
     */
    private function lookupProfile(array $game, array $profile, string $playerId, ?string $zoneId, string $profileName): LookupResult
    {
        $pageUrl = (string) ($profile['pageUrl'] ?? '');
        $locale = (string) ($profile['locale'] ?? $this->localeFromPage($pageUrl));
        $headers = $this->headers($pageUrl, $locale);
        $pageHeaders = array_merge($headers, [
            'Accept' => 'text/html,application/xhtml+xml,application/json;q=0.9,*/*;q=0.8',
            'Sec-Fetch-Dest' => 'document',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Site' => 'none',
            'Upgrade-Insecure-Requests' => '1',
        ]);
        $page = $this->http->get($pageUrl, $pageHeaders);

        if ($page->failed()) {
            return $this->networkFailure($game, $playerId, $zoneId, $page, $profileName, 'product-page', $pageUrl);
        }

        $metadata = $this->resolver->resolve($page->body, $profile);
        $metadata['productPath'] ??= (string) (parse_url($pageUrl, PHP_URL_PATH) ?: '');
        $metadata['shopLang'] ??= $this->shopLangFromPage($pageUrl);
        $metadata['voucherTypeName'] ??= (string) ($profile['voucherTypeName'] ?? strtoupper((string) ($game['code'] ?? '')));
        $metadata['initEndpoint'] = $this->absoluteUrl(
            (string) ($metadata['initEndpoint'] ?? self::INIT_ENDPOINT),
            self::INIT_ENDPOINT,
        );
        $metadata['orderTokenEndpoint'] = $this->absoluteUrl(
            (string) ($metadata['orderTokenEndpoint'] ?? self::ORDER_TOKEN_ENDPOINT),
            self::ORDER_TOKEN_ENDPOINT,
        );

        $tokens = [];
        $orderMeta = [];
        $canCreateToken = $this->filled($metadata, ['pageLockToken', 'productPath', 'skuId', 'paymentChannelId']);

        if ($canCreateToken) {
            $order = $this->http->postJson(
                (string) $metadata['orderTokenEndpoint'],
                [
                    'pageLockToken' => (string) $metadata['pageLockToken'],
                    'productPath' => (string) $metadata['productPath'],
                    'skuId' => $metadata['skuId'],
                    'paymentChannelId' => $metadata['paymentChannelId'],
                    'whitelabelId' => $metadata['whitelabelId'] ?? 0,
                ],
                $headers,
            );
            $orderMeta = $this->meta($order, $profileName, $pageUrl, 'order-token', $order->failed());

            if ($order->successful()) {
                $orderData = $order->json() ?? [];
                $tokens = [
                    'dynamicSkuToken' => Arr::firstFilled($orderData, [
                        'dynamicSkuToken',
                        'data.dynamicSkuToken',
                        'orderToken.dynamicSkuToken',
                        'result.dynamicSkuToken',
                    ]),
                    'pricePointDynamicSkuToken' => Arr::firstFilled($orderData, [
                        'pricePointToken',
                        'pricePointDynamicSkuToken',
                        'data.pricePointToken',
                        'data.pricePointDynamicSkuToken',
                        'result.pricePointDynamicSkuToken',
                    ]),
                    'pricingEngineToken' => Arr::firstFilled($orderData, [
                        'pricingEngineToken',
                        'data.pricingEngineToken',
                        'result.pricingEngineToken',
                    ]),
                ];

                foreach ($tokens as $key => $value) {
                    if ($value === null || $value === '') {
                        unset($tokens[$key]);
                    }
                }
            } elseif (($profile['requireOrderToken'] ?? false) === true) {
                return $this->networkFailure($game, $playerId, $zoneId, $order, $profileName, 'order-token', $pageUrl);
            }
        }

        if (!$this->filled($metadata, ['vppId', 'price', 'voucherTypeName'])) {
            $meta = array_merge(
                ProviderDiagnostics::fromResponse($page),
                [
                    'codashop_profile' => $profileName,
                    'page_url' => $pageUrl,
                    'missing_metadata' => $this->missing($metadata, ['vppId', 'price', 'voucherTypeName']),
                    'metadata_discovery' => 'failed',
                    'order_token_attempted' => $canCreateToken,
                ],
            );

            if ($this->debug) {
                $meta['discovered_keys'] = $metadata['discovered_keys'] ?? [];
                $meta['json_documents'] = $metadata['json_documents'] ?? 0;
                $meta['price_point_candidate_score'] = $metadata['price_point_candidate_score'] ?? null;
                $meta['order_token_meta'] = $orderMeta;
            }

            return LookupResult::failure(
                ResultCode::INVALID_RESPONSE,
                'Codashop product metadata could not be discovered from this storefront profile.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $meta,
            );
        }

        $form = [
            'voucherPricePoint.id' => (string) $metadata['vppId'],
            'voucherPricePoint.price' => (string) $metadata['price'],
            'voucherPricePoint.variablePrice' => (string) ($metadata['variablePrice'] ?? 0),
            'user.userId' => $playerId,
            'voucherTypeName' => (string) $metadata['voucherTypeName'],
            'shopLang' => (string) $metadata['shopLang'],
        ];

        if ($zoneId !== null && $zoneId !== '') {
            $form['user.zoneId'] = $this->resolveZone($game, $profile, $zoneId);
        } elseif (array_key_exists('zone', $profile) && $profile['zone'] !== null && $profile['zone'] !== false && $profile['zone'] !== 'user') {
            $form['user.zoneId'] = (string) $profile['zone'];
        }

        foreach (['voucherTypeId', 'lvtId', 'gvtId', 'paymentChannelId'] as $field) {
            if (($metadata[$field] ?? null) !== null && $metadata[$field] !== '') {
                $target = $field === 'paymentChannelId' ? 'pcId' : $field;
                $form[$target] = (string) $metadata[$field];
            }
        }

        foreach ($tokens as $field => $value) {
            $form[$field] = (string) $value;
        }

        // Some storefronts expose additional stable form values in the profile.
        foreach ((array) ($profile['form'] ?? []) as $field => $value) {
            if (is_string($field) && (is_scalar($value) || $value === null)) {
                $form[$field] = $value === null ? '' : (string) $value;
            }
        }

        $initHeaders = array_merge($headers, [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'X-Requested-With' => 'XMLHttpRequest',
        ]);
        $init = $this->http->postForm((string) $metadata['initEndpoint'], $form, $initHeaders);

        if ($init->status === 429) {
            return LookupResult::failure(
                ResultCode::RATE_LIMITED,
                'Codashop rate limit reached for the selected storefront.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $this->meta($init, $profileName, $pageUrl, 'init-payment'),
            );
        }

        if ($init->failed()) {
            return $this->networkFailure($game, $playerId, $zoneId, $init, $profileName, 'init-payment', $pageUrl);
        }

        $data = $init->json();
        if ($data === null) {
            return LookupResult::failure(
                ResultCode::INVALID_RESPONSE,
                'Codashop returned a non-JSON account-validation response.',
                $game['code'] ?? null,
                $playerId,
                $zoneId,
                $this->key(),
                $this->meta($init, $profileName, $pageUrl, 'init-payment', true),
            );
        }

        $paths = $profile['nicknamePaths'] ?? [
            'confirmationFields.username',
            'confirmationFields.roles.0.role',
            'confirmationFields.apiResult',
            'confirmationFields.nickname',
            'data.username',
            'data.nickname',
        ];
        $nickname = Normalizer::nickname(Arr::firstFilled($data, is_array($paths) ? $paths : []));

        if ($nickname !== null) {
            $serverPath = (string) ($profile['serverPath'] ?? '');
            $server = $serverPath !== '' ? Normalizer::nickname(Arr::get($data, $serverPath)) : null;
            $meta = $this->meta($init, $profileName, $pageUrl, 'init-payment');
            $meta['metadata_source'] = 'storefront-runtime';
            $meta['shop_lang'] = (string) $metadata['shopLang'];
            $meta['product_path'] = (string) $metadata['productPath'];
            $meta['order_token_used'] = $tokens !== [];

            return LookupResult::success(
                (string) ($game['code'] ?? ''),
                $playerId,
                $nickname,
                $this->key(),
                $zoneId,
                $server,
                $meta,
            );
        }

        $message = trim((string) Arr::firstFilled($data, ['errorMsg', 'errorMessage', 'message', 'msg']));
        if ($message === '') {
            $message = (string) ($profile['invalidMessage'] ?? 'Invalid player ID or zone for this storefront.');
        }

        $browserVerificationRequired = $this->requiresBrowserVerification($data, $message);
        if ($browserVerificationRequired) {
            $message = 'Codashop requires an interactive browser verification for this storefront.';
        }

        $code = ($browserVerificationRequired || $this->isRegionBlocked($data, $message))
            ? ResultCode::PROVIDER_RESTRICTED
            : ResultCode::INVALID_PLAYER;

        $meta = $this->meta($init, $profileName, $pageUrl, 'init-payment');
        $meta['metadata_source'] = 'storefront-runtime';
        $meta['shop_lang'] = (string) $metadata['shopLang'];
        $meta['product_path'] = (string) $metadata['productPath'];
        if ($browserVerificationRequired) {
            $meta['browser_verification_required'] = true;
        }
        if ($this->debug) {
            $meta['response'] = $data;
            $meta['order_token_meta'] = $orderMeta;
        }

        return LookupResult::failure(
            $code,
            $message,
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
            $meta,
        );
    }

    /**
     * @param array<string, mixed> $config
     * @return list<array<string, mixed>>
     */
    private function profiles(array $config): array
    {
        $profiles = $config['profiles'] ?? null;
        if (is_array($profiles) && $profiles !== []) {
            return array_values(array_filter($profiles, 'is_array'));
        }

        $storefronts = $config['storefronts'] ?? null;
        $slugs = array_values(array_unique(array_filter(array_map(
            static fn (mixed $slug): string => trim((string) $slug, '/'),
            (array) ($config['pageSlugs'] ?? []),
        ))));
        $legacySlug = trim((string) ($config['pageSlug'] ?? ''), '/');
        if ($legacySlug !== '' && !in_array($legacySlug, $slugs, true)) {
            array_unshift($slugs, $legacySlug);
        }

        if (is_array($storefronts) && $storefronts !== [] && $slugs !== []) {
            $expanded = [];
            $maximum = max(1, (int) ($config['maxProfiles'] ?? 10));

            foreach ($storefronts as $storefront) {
                $base = is_string($storefront)
                    ? ['localePath' => $storefront]
                    : (is_array($storefront) ? $storefront : []);
                if ($base === []) {
                    continue;
                }

                $localePath = trim((string) ($base['localePath'] ?? $base['storefront'] ?? ''), '/');
                if ($localePath === '') {
                    continue;
                }

                $profileSlugs = [];
                $explicitSlug = trim((string) ($base['pageSlug'] ?? ''), '/');
                if ($explicitSlug !== '') {
                    $profileSlugs[] = $explicitSlug;
                }
                foreach ((array) ($base['pageSlugs'] ?? []) as $profileSlug) {
                    $profileSlug = trim((string) $profileSlug, '/');
                    if ($profileSlug !== '' && !in_array($profileSlug, $profileSlugs, true)) {
                        $profileSlugs[] = $profileSlug;
                    }
                }
                foreach ($slugs as $slug) {
                    if (!in_array($slug, $profileSlugs, true)) {
                        $profileSlugs[] = $slug;
                    }
                }

                foreach ($profileSlugs as $slugIndex => $slug) {
                    if (count($expanded) >= $maximum) {
                        break 2;
                    }

                    $profile = $base;
                    unset($profile['pageSlugs']);
                    $profile['pageSlug'] = $slug;
                    $profile['name'] = (string) ($base['name'] ?? $localePath)
                        . (count($profileSlugs) > 1 ? ':' . $slug : '');
                    $profile['pageUrl'] = (string) ($base['pageUrl'] ?? (self::BASE_URL . '/' . $localePath . '/' . $slug));
                    $profile['locale'] ??= $this->localeFromStorefront($localePath);
                    $profile['shopLang'] ??= $this->shopLangFromStorefront($localePath);
                    $expanded[] = $profile;
                }
            }

            return $expanded;
        }

        return [$config];
    }

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function commonProfileConfig(array $config): array
    {
        unset($config['profiles'], $config['storefronts'], $config['pageSlugs'], $config['maxProfiles'], $config['productUrl']);

        return $config;
    }

    /** @param array<string, mixed> $values @param list<string> $keys */
    private function filled(array $values, array $keys): bool
    {
        return $this->missing($values, $keys) === [];
    }

    /** @param array<string, mixed> $values @param list<string> $keys @return list<string> */
    private function missing(array $values, array $keys): array
    {
        return array_values(array_filter($keys, static fn (string $key): bool =>
            !array_key_exists($key, $values) || $values[$key] === null || $values[$key] === ''
        ));
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

    /** @param array<string, mixed> $data */
    private function requiresBrowserVerification(array $data, string $message): bool
    {
        if ((string) ($data['RESULT_CODE'] ?? '') === '214') {
            return true;
        }

        return (string) ($data['errorCode'] ?? '') === '-500'
            && str_contains(strtolower($message), 'user account validation failed');
    }

    /**
     * @param array<string, mixed> $game
     * @param array<string, mixed> $profile
     */
    private function resolveZone(array $game, array $profile, string $zoneId): string
    {
        $filter = $profile['zoneFilter'] ?? null;
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

    /**
     * @param array<string, mixed> $game
     */
    private function networkFailure(
        array $game,
        string $playerId,
        ?string $zoneId,
        HttpResponse $response,
        string $profile,
        string $stage,
        string $pageUrl,
    ): LookupResult {
        return LookupResult::failure(
            ResultCode::NETWORK_ERROR,
            $response->error ?? sprintf('Codashop returned HTTP %d during %s.', $response->status, $stage),
            $game['code'] ?? null,
            $playerId,
            $zoneId,
            $this->key(),
            $this->meta($response, $profile, $pageUrl, $stage, true),
        );
    }

    /** @return array<string, mixed> */
    private function meta(
        HttpResponse $response,
        string $profile,
        string $pageUrl,
        string $stage,
        bool $includePreview = false,
    ): array {
        return array_merge(
            ProviderDiagnostics::fromResponse($response, $includePreview),
            [
                'codashop_profile' => $profile,
                'page_url' => $pageUrl,
                'stage' => $stage,
            ],
        );
    }

    /** @return array<string, string> */
    private function headers(string $pageUrl, string $locale): array
    {
        $language = strtolower(substr($locale, 0, 2));

        return [
            'Accept' => 'application/json, text/plain, */*',
            'Accept-Language' => $locale . ',' . $language . ';q=0.9,en;q=0.8',
            'Origin' => self::BASE_URL,
            'Referer' => $pageUrl,
            'Sec-Fetch-Dest' => 'empty',
            'Sec-Fetch-Mode' => 'cors',
            'Sec-Fetch-Site' => 'same-site',
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/131.0.0.0 Safari/537.36',
        ];
    }

    private function absoluteUrl(string $url, string $fallback): string
    {
        $url = trim(str_replace('\\/', '/', $url));
        if ($url === '') {
            return $fallback;
        }
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }
        if (str_starts_with($url, '//')) {
            return 'https:' . $url;
        }
        if (str_starts_with($url, '/')) {
            $host = parse_url($fallback, PHP_URL_HOST);
            return 'https://' . ($host ?: 'www.codashop.com') . $url;
        }

        return $fallback;
    }

    private function shopLangFromPage(string $pageUrl): string
    {
        $path = trim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
        $storefront = explode('/', $path)[0] ?? '';

        return $this->shopLangFromStorefront($storefront);
    }

    private function localeFromPage(string $pageUrl): string
    {
        $path = trim((string) parse_url($pageUrl, PHP_URL_PATH), '/');
        $storefront = explode('/', $path)[0] ?? '';

        return $this->localeFromStorefront($storefront);
    }

    private function shopLangFromStorefront(string $storefront): string
    {
        if (preg_match('/^([a-z]{2})-([a-z]{2})$/i', trim($storefront), $match)) {
            return strtolower($match[1]) . '_' . strtoupper($match[2]);
        }

        return 'en_MY';
    }

    private function localeFromStorefront(string $storefront): string
    {
        if (preg_match('/^([a-z]{2})-([a-z]{2})$/i', trim($storefront), $match)) {
            return strtolower($match[1]) . '-' . strtoupper($match[2]);
        }

        return 'en-MY';
    }
}
