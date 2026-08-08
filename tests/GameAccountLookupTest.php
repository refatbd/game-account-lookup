<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests;

use PHPUnit\Framework\TestCase;
use Refatbd\GameAccountLookup\Cache\ArrayCache;
use Refatbd\GameAccountLookup\Credentials\BundledCredentialProvider;
use Refatbd\GameAccountLookup\Credentials\ChainCredentialProvider;
use Refatbd\GameAccountLookup\Credentials\CredentialProviderInterface;
use Refatbd\GameAccountLookup\Credentials\ProviderCredential;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\GameAccountLookup;
use Refatbd\GameAccountLookup\Http\HttpClient;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\Providers\CodashopDynamicProvider;
use Refatbd\GameAccountLookup\Providers\CodashopBrowserProvider;
use Refatbd\GameAccountLookup\Providers\CodashopProvider;
use Refatbd\GameAccountLookup\Providers\GopayGamesProvider;
use Refatbd\GameAccountLookup\Providers\GarenaProvider;
use Refatbd\GameAccountLookup\Providers\MidasbuyBrowserProvider;
use Refatbd\GameAccountLookup\Providers\MidasbuyProvider;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Tests\Fakes\FakeHttpClient;
use Refatbd\GameAccountLookup\Tests\Fakes\FakeProvider;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;
use Refatbd\GameAccountLookup\Support\CodashopMetadataResolver;

final class GameAccountLookupTest extends TestCase
{
    public function testDefaultRegistryContainsCombinedGameSet(): void
    {
        self::assertGreaterThanOrEqual(45, count((new GameRegistry())->all()));
        self::assertSame('freefire', (new GameRegistry())->resolve('FF'));
        self::assertSame('pubgmobile', (new GameRegistry())->resolve('pubgm'));
    }

    public function testBundledCredentialsExcludeShortLivedGarenaSession(): void
    {
        $pubg = (new GameRegistry())->get('pubgmobile');
        $config = (array) ($pubg['providers']['midasbuy'] ?? []);

        self::assertArrayNotHasKey('encryptionKey', $config);
        self::assertArrayNotHasKey('ctoken', $config);
        self::assertNull((new BundledCredentialProvider())->forProvider('garena'));
        self::assertNotNull((new BundledCredentialProvider())->forProvider('midasbuy'));
    }

    public function testCredentialChainUsesFirstCompleteProvider(): void
    {
        $first = new class implements CredentialProviderInterface {
            public function forProvider(string $provider): ?ProviderCredential
            {
                return new ProviderCredential($provider, ['marker' => 'first']);
            }
        };
        $second = new class implements CredentialProviderInterface {
            public function forProvider(string $provider): ?ProviderCredential
            {
                return new ProviderCredential($provider, ['marker' => 'second']);
            }
        };

        $credential = (new ChainCredentialProvider([$first, $second]))->forProvider('midasbuy');

        self::assertSame('first', $credential?->value('marker'));
    }

    public function testDefaultProviderOrderKeepsBrowserAutomationLast(): void
    {
        $registry = new GameRegistry([
            'demo' => [
                'providers' => [
                    'codashop_browser' => [],
                    'codashop_dynamic' => [],
                    'custom_php' => [],
                    'codashop' => [],
                    'gopaygames' => [],
                    'midasbuy' => [],
                    'midasbuy_browser' => [],
                ],
            ],
        ]);

        self::assertSame(
            ['midasbuy', 'gopaygames', 'codashop', 'custom_php', 'codashop_dynamic', 'codashop_browser', 'midasbuy_browser'],
            array_keys((array) $registry->get('demo')['providers']),
        );
    }

    public function testZoneRequirementIsValidatedBeforeProviderCall(): void
    {
        $result = (new GameAccountLookup())->check('mobilelegends', '123');

        self::assertFalse($result->ok);
        self::assertSame(ResultCode::ZONE_REQUIRED, $result->code);
    }

    public function testProviderFallbackStopsOnFirstSuccess(): void
    {
        $first = new FakeProvider('first', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::failure(ResultCode::INVALID_PLAYER, 'No match.', $game['code'], $id, $zone, 'first')
        );
        $second = new FakeProvider('second', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'PlayerTwo', 'second', $zone)
        );

        $lookup = (new GameAccountLookup(new GameRegistry([])))
            ->registerProvider($first)
            ->registerProvider($second)
            ->registerGame('demo', [
                'label' => 'Demo',
                'providers' => ['first' => [], 'second' => []],
            ]);

        $result = $lookup->check('demo', '99');

        self::assertTrue($result->ok);
        self::assertSame('PlayerTwo', $result->nickname);
        self::assertSame(1, $first->calls);
        self::assertSame(1, $second->calls);
        self::assertCount(2, $result->attempts);
    }


    public function testGopayResponseIsNormalized(): void
    {
        $http = new FakeHttpClient(new HttpResponse(
            200,
            '{"message":"Success","data":{"username":"TestPlayer"}}',
        ));
        $provider = new GopayGamesProvider($http);
        $game = [
            'code' => 'pubgmobile',
            'providers' => [
                'gopaygames' => ['code' => 'PUBG_ID'],
            ],
        ];

        $result = $provider->lookup($game, '12345');

        self::assertTrue($result->ok);
        self::assertSame('TestPlayer', $result->nickname);
        self::assertSame('gopaygames', $result->provider);
    }

    public function testGarenaResponseIncludesCountry(): void
    {
        $http = new FakeHttpClient(
            new HttpResponse(200, '<html>Shop2Game</html>'),
            new HttpResponse(
                200,
                '{"nickname":"Test Survivor","region":"BD"}',
                ['content-type' => ['application/json']],
            ),
        );
        $provider = new GarenaProvider($http);
        $game = [
            'code' => 'freefire',
            'providers' => ['garena' => [
                'appId' => 100067,
                'userAgent' => "Configured Agent\r\nInjected",
            ]],
        ];

        $result = $provider->lookup($game, '4422076728');

        self::assertTrue($result->ok);
        self::assertSame('garena', $result->provider);
        self::assertSame('Test Survivor', $result->nickname);
        self::assertSame('BD', $result->country);
        self::assertSame('BD', $result->toArray()['country']);
        self::assertSame('GET', $http->requests[0]['method']);
        self::assertSame('https://shop2game.com/api/auth/player_id_login', $http->requests[1]['url']);
        self::assertSame('Configured AgentInjected', $http->requests[1]['headers']['User-Agent']);
    }

    public function testGarenaChallengeFallsBackWithoutFatalError(): void
    {
        $garenaHttp = new FakeHttpClient(
            new HttpResponse(200, '<html>Shop2Game</html>'),
            new HttpResponse(
                403,
                '{"url":"https://geo.captcha-delivery.com/interstitial/example"}',
                ['content-type' => ['application/json']],
            ),
            new HttpResponse(
                403,
                '{"url":"https://geo.captcha-delivery.com/interstitial/example"}',
                ['content-type' => ['application/json']],
            ),
        );
        $fallback = new FakeProvider('fallback', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'Fallback Player', 'fallback', $zone)
        );
        $lookup = (new GameAccountLookup(new GameRegistry([])))
            ->registerProvider(new GarenaProvider($garenaHttp))
            ->registerProvider($fallback)
            ->registerGame('freefire', [
                'providers' => ['fallback' => [], 'garena' => []],
            ]);

        $result = $lookup->check('freefire', '4422076728');

        self::assertTrue($result->ok);
        self::assertSame('Fallback Player', $result->nickname);
        self::assertSame(['garena', 'fallback'], array_column($result->attempts, 'provider'));
        self::assertSame(ResultCode::PROVIDER_RESTRICTED, $result->attempts[0]['code']);
        self::assertTrue($result->attempts[0]['meta']['session_retried']);
    }

    public function testGarenaWarmSessionSkipsPreflightForANewUid(): void
    {
        $http = new FakeHttpClient(new HttpResponse(
            200,
            '{"nickname":"Warm Player","region":"BD"}',
            ['content-type' => ['application/json']],
        ));
        $http->warmSession = true;

        $result = (new GarenaProvider($http))->lookup([
            'code' => 'freefire',
            'providers' => ['garena' => ['appId' => 100067]],
        ], '4641089868');

        self::assertTrue($result->ok);
        self::assertCount(1, $http->requests);
        self::assertSame('POST_JSON', $http->requests[0]['method']);
        self::assertTrue($result->meta['preflight_skipped']);
        self::assertFalse($result->meta['session_retried']);
    }

    public function testGarenaStaleWarmSessionRestoresColdFlow(): void
    {
        $challenge = new HttpResponse(
            403,
            '{"url":"https://geo.captcha-delivery.com/interstitial/example"}',
            ['content-type' => ['application/json']],
        );
        $http = new FakeHttpClient(
            $challenge,
            new HttpResponse(200, '<html>Shop2Game</html>'),
            $challenge,
            new HttpResponse(200, '{"nickname":"Recovered Player","region":"BD"}'),
        );
        $http->warmSession = true;

        $result = (new GarenaProvider($http))->lookup([
            'code' => 'freefire',
            'providers' => ['garena' => ['appId' => 100067]],
        ], '4641089868');

        self::assertTrue($result->ok);
        self::assertSame(['POST_JSON', 'GET', 'POST_JSON', 'POST_JSON'], array_column($http->requests, 'method'));
        self::assertTrue($result->meta['preflight_skipped']);
        self::assertTrue($result->meta['session_retried']);
        self::assertTrue($http->warmSession);
    }

    public function testHttpClientPersistsDomainScopedWarmSession(): void
    {
        $cache = new ArrayCache();
        $firstTransport = static fn (string $method, string $url): HttpResponse => new HttpResponse(
            200,
            '{}',
            ['set-cookie' => ['session_id=ready; Path=/']],
            null,
            20,
            $url,
        );
        $first = new HttpClient(sessionCache: $cache, transport: $firstTransport);
        $first->get('https://shop2game.com/login');
        $first->markSessionWarm('https://shop2game.com/login');
        $first->clearCookies();
        self::assertFalse($first->hasWarmSession('https://shop2game.com/login'));

        $seenHeaders = [];
        $secondTransport = static function (string $method, string $url, array $headers) use (&$seenHeaders): HttpResponse {
            $seenHeaders = $headers;

            return new HttpResponse(200, '{}', [], null, 20, $url);
        };
        $second = new HttpClient(sessionCache: $cache, transport: $secondTransport);

        self::assertTrue($second->hasWarmSession('https://shop2game.com/login'));
        $second->get('https://shop2game.com/api');
        self::assertSame('session_id=ready', $seenHeaders['Cookie']);
        self::assertFalse($second->hasWarmSession('https://example.com'));
    }

    public function testCodashopRoleResponseIsNormalized(): void
    {
        $http = new FakeHttpClient(new HttpResponse(
            200,
            '{"success":true,"confirmationFields":{"roles":[{"role":"FFPlayer"}]}}',
        ));
        $provider = new CodashopProvider($http);
        $game = [
            'code' => 'freefire',
            'providers' => [
                'codashop' => [
                    'vppId' => 1,
                    'price' => '1000',
                    'voucherTypeName' => 'FREEFIRE',
                    'nicknamePaths' => ['confirmationFields.roles.0.role'],
                ],
            ],
        ];

        $result = $provider->lookup($game, '4422076728');

        self::assertTrue($result->ok);
        self::assertSame('FFPlayer', $result->nickname);
        self::assertSame('codashop', $result->provider);
    }

    public function testSuccessfulResultIsCached(): void
    {
        $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'CachedPlayer', 'fake', $zone)
        );

        $lookup = (new GameAccountLookup(new GameRegistry([]), new ArrayCache(), 60))
            ->registerProvider($provider)
            ->registerGame('demo', [
                'label' => 'Demo',
                'providers' => ['fake' => []],
            ]);

        $first = $lookup->check('demo', '100');
        $second = $lookup->check('demo', '100');

        self::assertTrue($first->ok);
        self::assertTrue($second->cached);
        self::assertSame(ResultCode::CACHED, $second->code);
        self::assertSame(1, $provider->calls);
    }

    public function testCacheCanBeBypassedForOneRequest(): void
    {
        $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'FreshPlayer', 'fake', $zone)
        );

        $lookup = (new GameAccountLookup(new GameRegistry([]), new ArrayCache(), 60))
            ->registerProvider($provider)
            ->registerGame('demo', [
                'label' => 'Demo',
                'providers' => ['fake' => []],
            ]);

        $lookup->check('demo', '100');
        $fresh = $lookup->check('demo', '100', bypassCache: true);
        $cached = $lookup->check('demo', '100');

        self::assertFalse($fresh->cached);
        self::assertTrue($cached->cached);
        self::assertSame(2, $provider->calls);
    }

    public function testSingleProviderCacheIsIsolatedFromAutomaticFallbackCache(): void
    {
        $first = new FakeProvider('first', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'FirstPlayer', 'first', $zone)
        );
        $second = new FakeProvider('second', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'SecondPlayer', 'second', $zone)
        );

        $lookup = (new GameAccountLookup(new GameRegistry([]), new ArrayCache(), 60))
            ->registerProvider($first)
            ->registerProvider($second)
            ->registerGame('demo', [
                'label' => 'Demo',
                'providers' => ['first' => [], 'second' => []],
            ]);

        $automatic = $lookup->check('demo', '100');
        $strict = $lookup->check('demo', '100', providerOrder: ['second']);
        $strictCached = $lookup->check('demo', '100', providerOrder: ['second']);

        self::assertSame('FirstPlayer', $automatic->nickname);
        self::assertSame('SecondPlayer', $strict->nickname);
        self::assertTrue($strictCached->cached);
        self::assertSame(1, $first->calls);
        self::assertSame(1, $second->calls);
    }

    public function testMaintenanceStateStopsBeforeProviderCall(): void
    {
        $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::success($game['code'], $id, 'ShouldNotRun', 'fake', $zone)
        );

        $lookup = (new GameAccountLookup(new GameRegistry([])))
            ->registerProvider($provider)
            ->registerGame('maintenance-demo', [
                'label' => 'Maintenance Demo',
                'status' => 'metadata-refresh-required',
                'notes' => 'Refresh provider metadata.',
                'providers' => ['fake' => []],
            ]);

        $result = $lookup->check('maintenance-demo', '100');

        self::assertFalse($result->ok);
        self::assertSame(ResultCode::MAINTENANCE_REQUIRED, $result->code);
        self::assertSame('metadata-refresh-required', $result->meta['game_status']);
        self::assertSame(0, $provider->calls);
    }

    public function testProviderDiagnosticsRemainInAttempts(): void
    {
        $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
            LookupResult::failure(
                ResultCode::NETWORK_ERROR,
                'Upstream returned HTTP 403.',
                $game['code'],
                $id,
                $zone,
                'fake',
                ['http_status' => 403, 'duration_ms' => 91],
            )
        );

        $lookup = (new GameAccountLookup(new GameRegistry([])))
            ->registerProvider($provider)
            ->registerGame('diagnostic-demo', [
                'label' => 'Diagnostic Demo',
                'providers' => ['fake' => []],
            ]);

        $result = $lookup->check('diagnostic-demo', '100');

        self::assertSame(ResultCode::ALL_PROVIDERS_FAILED, $result->code);
        self::assertSame(403, $result->attempts[0]['meta']['http_status']);
        self::assertSame(91, $result->attempts[0]['meta']['duration_ms']);
    }

    public function testProviderDiagnosticPreviewIsSanitized(): void
    {
        $response = new HttpResponse(
            502,
            '<html>token=abcdefghijklmnopqrstuvwxyz1234567890 {\"dynamicSkuToken\":\"sensitive-dynamic-token-value\"}</html>',
            ['content-type' => ['text/html']],
            null,
            125,
            'https://provider.example.test/check',
        );

        $meta = ProviderDiagnostics::fromResponse($response, true);

        self::assertSame(502, $meta['http_status']);
        self::assertSame(125, $meta['duration_ms']);
        self::assertSame('provider.example.test', $meta['upstream_host']);
        self::assertStringNotContainsString('abcdefghijklmnopqrstuvwxyz1234567890', $meta['response_preview']);
        self::assertStringNotContainsString('sensitive-dynamic-token-value', $meta['response_preview']);
    }

    public function testCodashopResolverHydratesNuxtReferencePayload(): void
    {
        $payload = [
            ['ShallowReactive', 1],
            ['productPage' => 2],
            [
                'pageLockToken' => 3,
                'productInfo' => 4,
                'paymentChannels' => 5,
                'skus' => 6,
            ],
            'page-lock',
            ['voucherTypeName' => 7, 'productUrl' => 8],
            [9],
            [12],
            'FREEFIRE',
            '/my/free-fire',
            ['id' => 10, 'displayName' => 11],
            242,
            'MAE',
            ['displayId' => 13, 'pricePoints' => 14],
            'FFMYWL',
            [15],
            ['id' => 16, 'paymentChannelId' => 17, 'price' => 18],
            1,
            242,
            2.70,
        ];
        $html = '<script type="application/json" id="__NUXT_DATA__">'
            . json_encode($payload, JSON_THROW_ON_ERROR)
            . '</script>';

        $metadata = (new CodashopMetadataResolver())->resolve($html);

        self::assertSame('page-lock', $metadata['pageLockToken']);
        self::assertSame('/my/free-fire', $metadata['productPath']);
        self::assertSame('FFMYWL', $metadata['skuId']);
        self::assertSame(242, $metadata['paymentChannelId']);
        self::assertSame(1, $metadata['vppId']);
        self::assertSame(2.70, $metadata['price']);
    }

    public function testCodashopBrowserVerificationIsNotReportedAsInvalidPlayer(): void
    {
        $page = '<script type="application/json">'
            . '{"pageLockToken":"lock","productPath":"/my/free-fire","skuId":"FFMYWL",'
            . '"paymentChannelId":242,"voucherTypeName":"FREEFIRE",'
            . '"voucherPricePoint":{"id":1,"price":"2.70"}}'
            . '</script>';
        $http = new FakeHttpClient(
            new HttpResponse(200, $page),
            new HttpResponse(200, '{"data":{"pricingEngineToken":"pricing"}}'),
            new HttpResponse(200, '{"RESULT_CODE":214}'),
        );
        $provider = new CodashopDynamicProvider($http, true);
        $result = $provider->lookup([
            'code' => 'freefire',
            'providers' => [
                'codashop_dynamic' => [
                    'profiles' => [[
                        'name' => 'malaysia-en',
                        'pageUrl' => 'https://www.codashop.com/en-my/free-fire',
                        'locale' => 'en-MY',
                    ]],
                ],
            ],
        ], '4422076728');

        self::assertSame(ResultCode::PROVIDER_RESTRICTED, $result->code);
        self::assertTrue($result->meta['browser_verification_required']);
        self::assertStringContainsString('interactive browser verification', $result->message);
    }

    public function testCodashopBrowserProviderReturnsRealHelperResultWithoutPurchasing(): void
    {
        $provider = new CodashopBrowserProvider(dirname(__DIR__), true);
        $result = $provider->lookup([
            'code' => 'freefire',
            'providers' => [
                'codashop_browser' => [
                    'node' => PHP_BINARY,
                    'script' => __DIR__ . '/Fixtures/browser-helper-success.php',
                    'timeout' => 30,
                ],
            ],
        ], '4422076728');

        self::assertTrue($result->ok);
        self::assertSame('Real Browser Player', $result->nickname);
        self::assertSame('codashop_browser', $result->provider);
        self::assertFalse($result->meta['purchase_submitted']);
        self::assertTrue($result->meta['browser_assisted']);
    }

    public function testMidasbuyBrowserProviderReturnsCountryFromOfficialValidation(): void
    {
        $provider = new MidasbuyBrowserProvider(dirname(__DIR__), true);
        $result = $provider->lookup([
            'code' => 'pubgmobile',
            'providers' => [
                'midasbuy_browser' => [
                    'node' => PHP_BINARY,
                    'script' => __DIR__ . '/Fixtures/browser-helper-success.php',
                    'timeout' => 30,
                ],
            ],
        ], '51204454144');

        self::assertTrue($result->ok);
        self::assertSame('Real Browser Player', $result->nickname);
        self::assertSame('BD', $result->country);
        self::assertSame('midasbuy_browser', $result->provider);
        self::assertTrue($result->meta['browser_assisted']);
    }

    public function testMidasbuyDirectProviderUsesHttpWithoutBrowser(): void
    {
        $http = new FakeHttpClient(
            new HttpResponse(200, '<html>Midasbuy</html>'),
            new HttpResponse(200, json_encode([
                'ret' => 0,
                'info' => [
                    'zoneid' => '1',
                    'openid' => '62145991708902696',
                    'charac_name' => 'FACTOR%E4%B9%8412',
                    'active_country' => 'bd',
                    'is_ban' => false,
                ],
            ], JSON_UNESCAPED_UNICODE) ?: '{}'),
        );
        $credentials = new class implements CredentialProviderInterface {
            public function forProvider(string $provider): ?ProviderCredential
            {
                return $provider === 'midasbuy'
                    ? new ProviderCredential('midasbuy', [
                        'encryptionKey' => '000102030405060708090a0b0c0d0e0f101112131415161718191a1b1c1d1e1f',
                        'encryptionIv' => '1234567890123456',
                        'ctokenVersion' => '1.0.1',
                        'ctoken' => 'test-ctoken',
                    ])
                    : null;
            }
        };
        $provider = new MidasbuyProvider($http, true, $credentials);
        $result = $provider->lookup([
            'code' => 'pubgmobile',
            'providers' => [
                'midasbuy' => [
                    'endpoint' => 'https://www.midasbuy.com/interface/getCharac',
                    'appId' => '1450015065',
                    'zoneId' => '1',
                    'userAgent' => "Configured Midas Agent\r\nInjected",
                ],
            ],
        ], '51204454144');

        self::assertTrue($result->ok);
        self::assertSame('FACTOR乄12', $result->nickname);
        self::assertSame('BD', $result->country);
        self::assertSame('midasbuy', $result->provider);
        self::assertSame('GET', $http->requests[0]['method']);
        self::assertSame('POST_JSON', $http->requests[1]['method']);
        self::assertSame('Configured Midas AgentInjected', $http->requests[1]['headers']['User-Agent']);
        self::assertSame('xdeETkZNH7JMYhHUfcL9pxCrsvi9ElW02HgY9679Yp27D6GHqO5etYhCJYeAXBJ+cGBofHQJ4eHnqYsIg2oRT0G1uphcieQyjrqV8ootMnw=', $http->requests[1]['payload']['encrypt_msg']);
        self::assertTrue($result->meta['direct_http']);
        self::assertFalse($result->meta['browser_assisted']);
        self::assertTrue($result->meta['managed_session']);
    }

}
