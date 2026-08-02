<?php

declare(strict_types=1);

require __DIR__ . '/bootstrap.php';

use Refatbd\GameAccountLookup\Cache\ArrayCache;
use Refatbd\GameAccountLookup\DTO\LookupResult;
use Refatbd\GameAccountLookup\GameAccountLookup;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\Providers\CodashopProvider;
use Refatbd\GameAccountLookup\Providers\CodashopDynamicProvider;
use Refatbd\GameAccountLookup\Providers\GopayGamesProvider;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Tests\Fakes\FakeHttpClient;
use Refatbd\GameAccountLookup\Tests\Fakes\FakeProvider;
use Refatbd\GameAccountLookup\Support\ProviderDiagnostics;
use Refatbd\GameAccountLookup\Support\CodashopMetadataResolver;
use Refatbd\GameAccountLookup\Support\GopayMetadataResolver;

$tests = [];

$tests['combined registry'] = static function (): void {
    $registry = new GameRegistry();
    assert(count($registry->all()) >= 45);
    assert($registry->resolve('FF') === 'freefire');
    assert($registry->resolve('PUBGM') === 'pubgmobile');
    $genshin = array_values(array_filter(
        $registry->list(),
        static fn (array $game): bool => $game['code'] === 'genshinimpact',
    ))[0] ?? null;
    assert($genshin !== null);
    assert($genshin['servers'] === ['os_usa', 'os_euro', 'os_asia', 'os_cht']);
};

$tests['zone validation'] = static function (): void {
    $result = (new GameAccountLookup())->check('mobilelegends', '123');
    assert($result->ok === false);
    assert($result->code === ResultCode::ZONE_REQUIRED);
};

$tests['fallback'] = static function (): void {
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
    assert($result->ok === true);
    assert($result->nickname === 'PlayerTwo');
    assert($first->calls === 1);
    assert($second->calls === 1);
};


$tests['gopay parser'] = static function (): void {
    $http = new FakeHttpClient(new HttpResponse(
        200,
        '{"message":"Success","data":{"username":"TestPlayer"}}',
    ));
    $provider = new GopayGamesProvider($http);
    $result = $provider->lookup([
        'code' => 'pubgmobile',
        'providers' => ['gopaygames' => ['code' => 'PUBG_ID']],
    ], '12345');

    assert($result->ok === true);
    assert($result->nickname === 'TestPlayer');
};

$tests['codashop parser'] = static function (): void {
    $http = new FakeHttpClient(new HttpResponse(
        200,
        '{"success":true,"confirmationFields":{"roles":[{"role":"FFPlayer"}]}}',
    ));
    $provider = new CodashopProvider($http);
    $result = $provider->lookup([
        'code' => 'freefire',
        'providers' => [
            'codashop' => [
                'vppId' => 1,
                'price' => '1000',
                'voucherTypeName' => 'FREEFIRE',
                'nicknamePaths' => ['confirmationFields.roles.0.role'],
            ],
        ],
    ], '4422076728');

    assert($result->ok === true);
    assert($result->nickname === 'FFPlayer');
};


$tests['documentation and template'] = static function (): void {
    $root = dirname(__DIR__);
    $registry = new GameRegistry();
    $readme = (string) file_get_contents($root . '/README.md');

    assert(count($registry->list()) === count($registry->all()));
    assert(str_contains($readme, '| Free Fire | `freefire` |'));
    assert(str_contains($readme, '| PUBG Mobile | `pubgmobile` |'));
    assert(is_file($root . '/docs/DEVELOPER_GUIDE.md'));
    assert(is_file($root . '/docs/API_REFERENCE.md'));
    assert(is_file($root . '/template/index.php'));
    assert(is_file($root . '/template/api/check.php'));
};

$tests['cache'] = static function (): void {
    $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
        LookupResult::success($game['code'], $id, 'CachedPlayer', 'fake', $zone)
    );

    $lookup = (new GameAccountLookup(new GameRegistry([]), new ArrayCache(), 60))
        ->registerProvider($provider)
        ->registerGame('demo', [
            'label' => 'Demo',
            'providers' => ['fake' => []],
        ]);

    assert($lookup->check('demo', '100')->ok === true);
    assert($lookup->check('demo', '100')->cached === true);
    assert($provider->calls === 1);
};

$tests['cache bypass'] = static function (): void {
    $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
        LookupResult::success($game['code'], $id, 'FreshPlayer', 'fake', $zone)
    );

    $lookup = (new GameAccountLookup(new GameRegistry([]), new ArrayCache(), 60))
        ->registerProvider($provider)
        ->registerGame('demo', [
            'label' => 'Demo',
            'providers' => ['fake' => []],
        ]);

    assert($lookup->check('demo', '100')->ok === true);
    assert($lookup->check('demo', '100', bypassCache: true)->cached === false);
    assert($lookup->check('demo', '100')->cached === true);
    assert($provider->calls === 2);
};

$tests['provider cache isolation'] = static function (): void {
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

    assert($lookup->check('demo', '100')->nickname === 'FirstPlayer');
    assert($lookup->check('demo', '100', providerOrder: ['second'])->nickname === 'SecondPlayer');
    assert($lookup->check('demo', '100', providerOrder: ['second'])->cached === true);
    assert($first->calls === 1);
    assert($second->calls === 1);
};


$tests['maintenance state blocks live provider'] = static function (): void {
    $provider = new FakeProvider('fake', static fn (array $game, string $id, ?string $zone): LookupResult =>
        LookupResult::success($game['code'], $id, 'ShouldNotRun', 'fake', $zone)
    );

    $lookup = (new GameAccountLookup(new GameRegistry([])))
        ->registerProvider($provider)
        ->registerGame('maintenance-demo', [
            'label' => 'Maintenance Demo',
            'status' => 'metadata-refresh-required',
            'notes' => 'Refresh the provider product metadata.',
            'providers' => ['fake' => []],
        ]);

    $result = $lookup->check('maintenance-demo', '100');
    assert($result->ok === false);
    assert($result->code === ResultCode::MAINTENANCE_REQUIRED);
    assert(($result->meta['game_status'] ?? null) === 'metadata-refresh-required');
    assert($provider->calls === 0);
};

$tests['provider diagnostics are retained'] = static function (): void {
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
    assert($result->code === ResultCode::ALL_PROVIDERS_FAILED);
    assert(($result->attempts[0]['meta']['http_status'] ?? null) === 403);
    assert(($result->attempts[0]['meta']['duration_ms'] ?? null) === 91);
};

$tests['diagnostic preview redacts tokens'] = static function (): void {
    $response = new HttpResponse(
        502,
        '<html>token=abcdefghijklmnopqrstuvwxyz1234567890 {\"dynamicSkuToken\":\"sensitive-dynamic-token-value\"}</html>',
        ['content-type' => ['text/html'], 'cf-ray' => ['sample-ray']],
        null,
        125,
        'https://provider.example.test/check',
    );
    $meta = ProviderDiagnostics::fromResponse($response, true);
    assert(($meta['http_status'] ?? null) === 502);
    assert(($meta['duration_ms'] ?? null) === 125);
    assert(($meta['upstream_host'] ?? null) === 'provider.example.test');
    assert(!str_contains((string) ($meta['response_preview'] ?? ''), 'abcdefghijklmnopqrstuvwxyz1234567890'));
    assert(!str_contains((string) ($meta['response_preview'] ?? ''), 'sensitive-dynamic-token-value'));
};

$tests['template avoids gateway status for provider failures'] = static function (): void {
    $source = (string) file_get_contents(dirname(__DIR__) . '/template/api/check.php');
    assert(!str_contains($source, 'default => 502'));
    assert(!str_contains($source, '$successCount > 0 ? 200 : 502'));
    assert(str_contains($source, "'transport_status'"));
};


$tests['codashop metadata resolver keeps coherent price point'] = static function (): void {
    $html = <<<'HTML'
<!doctype html><html><body>
<script id="__NEXT_DATA__" type="application/json">
{
  "props": {
    "pageProps": {
      "product": {
        "voucherTypeName": "FREEFIRE",
        "skuId": "sku-my-100",
        "paymentChannelId": 51,
        "offers": [
          {"voucherPricePoint": {"id": 991001, "price": "4.20", "variablePrice": 0}},
          {"voucherPricePoint": {"id": 991002, "price": "7.90", "variablePrice": 0}}
        ]
      }
    }
  }
}
</script>
</body></html>
HTML;

    $metadata = (new CodashopMetadataResolver())->resolve($html);
    assert((string) ($metadata['vppId'] ?? '') === '991001');
    assert((string) ($metadata['price'] ?? '') === '4.20');
    assert(($metadata['voucherTypeName'] ?? null) === 'FREEFIRE');
    assert(($metadata['skuId'] ?? null) === 'sku-my-100');
};

$tests['codashop metadata resolver hydrates Nuxt payload references'] = static function (): void {
    $payload = [
        ['ShallowReactive', 1],
        ['state' => 2],
        ['Reactive', 3],
        ['productPage' => 4],
        [
            'pageLockToken' => 5,
            'productPath' => 6,
            'paymentChannels' => 7,
            'productInfo' => 8,
            'skus' => 9,
        ],
        'short-lived-page-lock',
        '/my/free-fire',
        [10],
        ['voucherTypeName' => 11, 'voucherTypeId' => 12, 'gvtId' => 13, 'productUrl' => 30],
        [14],
        ['id' => 15, 'displayName' => 16],
        'FREEFIRE',
        6583,
        3933,
        ['displayId' => 17, 'pricePoints' => 18],
        242,
        'MAE',
        'FFMYWL',
        [19, 27],
        ['id' => 20, 'paymentChannelId' => 21, 'price' => 22, 'isEnabled' => 23],
        4,
        52,
        2.70,
        true,
        ['id' => 25, 'displayName' => 26],
        52,
        'Celcom',
        ['id' => 28, 'paymentChannelId' => 29, 'price' => 22, 'isEnabled' => 23],
        1,
        242,
        '/my/free-fire',
    ];
    $html = '<script type="application/json" data-nuxt-data="nuxt-app" id="__NUXT_DATA__">'
        . json_encode($payload, JSON_THROW_ON_ERROR)
        . '</script>';

    $metadata = (new CodashopMetadataResolver())->resolve($html);
    assert(($metadata['pageLockToken'] ?? null) === 'short-lived-page-lock');
    assert(($metadata['productPath'] ?? null) === '/my/free-fire');
    assert(($metadata['skuId'] ?? null) === 'FFMYWL');
    assert((string) ($metadata['paymentChannelId'] ?? '') === '242');
    assert((string) ($metadata['vppId'] ?? '') === '1');
    assert((string) ($metadata['price'] ?? '') === '2.7');
    assert(($metadata['voucherTypeName'] ?? null) === 'FREEFIRE');
};

$tests['dynamic codashop retries regional storefronts'] = static function (): void {
    $malaysiaPage = <<<'HTML'
<script type="application/json">{"product":{"voucherTypeName":"FREEFIRE","offers":[{"voucherPricePoint":{"id":111,"price":"4.20"}}]}}</script>
HTML;
    $indonesiaPage = <<<'HTML'
<script type="application/json">{"product":{"voucherTypeName":"FREEFIRE","offers":[{"voucherPricePoint":{"id":222,"price":"1000"}}]}}</script>
HTML;

    $http = new FakeHttpClient(
        new HttpResponse(200, $malaysiaPage, ['content-type' => ['text/html']], null, 20, 'https://www.codashop.com/en-my/free-fire'),
        new HttpResponse(200, '{"errorMsg":"Topup region blocked for player"}', ['content-type' => ['application/json']], null, 30, 'https://order-sg.codashop.com/initPayment.action'),
        new HttpResponse(200, $indonesiaPage, ['content-type' => ['text/html']], null, 21, 'https://www.codashop.com/id-id/free-fire'),
        new HttpResponse(200, '{"confirmationFields":{"roles":[{"role":"RegionalPlayer"}]}}', ['content-type' => ['application/json']], null, 31, 'https://order-sg.codashop.com/initPayment.action'),
    );

    $provider = new CodashopDynamicProvider($http, true);
    $result = $provider->lookup([
        'code' => 'freefire',
        'providers' => [
            'codashop_dynamic' => [
                'pageSlug' => 'free-fire',
                'voucherTypeName' => 'FREEFIRE',
                'storefronts' => ['en-my', 'id-id'],
                'nicknamePaths' => ['confirmationFields.roles.0.role'],
            ],
        ],
    ], '4422076728');

    assert($result->ok === true);
    assert($result->nickname === 'RegionalPlayer');
    assert(($result->meta['codashop_profile'] ?? null) === 'id-id');
    assert(($result->meta['profiles_checked'] ?? null) === 2);
    assert(count($result->meta['profile_attempts'] ?? []) === 2);
    assert(($result->meta['profile_attempts'][0]['code'] ?? null) === ResultCode::PROVIDER_RESTRICTED);
    assert($http->cookieResets === 2);
    assert(count($http->requests) === 4);
    assert(($http->requests[1]['form']['voucherPricePoint.id'] ?? null) === '111');
    assert(($http->requests[3]['form']['voucherPricePoint.id'] ?? null) === '222');
    assert(($http->requests[1]['form']['shopLang'] ?? null) === 'en_MY');
    assert(($http->requests[3]['form']['shopLang'] ?? null) === 'id_ID');
};

$tests['classic codashop classifies regional block'] = static function (): void {
    $http = new FakeHttpClient(new HttpResponse(
        200,
        '{"errorMsg":"Topup region blocked for player"}',
        ['content-type' => ['application/json']],
    ));
    $provider = new CodashopProvider($http);
    $result = $provider->lookup([
        'code' => 'freefire',
        'providers' => [
            'codashop' => [
                'vppId' => 1,
                'price' => '1.00',
                'voucherTypeName' => 'FREEFIRE',
            ],
        ],
    ], '4422076728');

    assert($result->ok === false);
    assert($result->code === ResultCode::PROVIDER_RESTRICTED);
};

$tests['problem games prefer hosting safe provider order'] = static function (): void {
    $registry = new GameRegistry();

    $freeFire = $registry->get('freefire');
    $fcMobile = $registry->get('fcmobile');
    $farlight = $registry->get('farlight84');

    assert(array_keys($freeFire['providers']) === ['garena', 'gopaygames', 'codashop', 'codashop_dynamic', 'codashop_browser']);
    assert(array_key_first($fcMobile['providers']) === 'gopaygames');
    assert(array_key_first($farlight['providers']) === 'codashop');
    assert(($farlight['status'] ?? null) === 'active');
    assert(($freeFire['providers']['codashop_dynamic']['storefronts'][0] ?? null) === 'en-my');
    assert(($fcMobile['providers']['codashop_dynamic']['storefronts'][0] ?? null) === 'en-bd');
};



$tests['provider catalog covers all definitions'] = static function (): void {
    $registry = new GameRegistry();
    assert(count($registry->all()) === 53);
    foreach ($registry->all() as $game) {
        assert(isset($game['providerAvailability']['codashop']));
        assert(isset($game['providerAvailability']['gopaygames']));
        assert(($game['providerAuditVerifiedAt'] ?? null) !== null);
    }
};

$tests['all audited codashop titles use regional discovery'] = static function (): void {
    $registry = new GameRegistry();
    $available = 0;
    foreach ($registry->all() as $game) {
        if (($game['providerAvailability']['codashop']['status'] ?? null) !== 'available') {
            continue;
        }
        $available++;
        assert(isset($game['providers']['codashop_dynamic']));
        assert(($game['providers']['codashop_dynamic']['enabled'] ?? false) === true);
        assert(!empty($game['providers']['codashop_dynamic']['pageSlugs']));
        assert(!empty($game['providers']['codashop_dynamic']['storefronts']));
        $expectedFirst = isset($game['providers']['garena'])
            ? 'garena'
            : (isset($game['providers']['midasbuy'])
            ? 'midasbuy'
            : (isset($game['providers']['gopaygames'])
            ? 'gopaygames'
            : (isset($game['providers']['codashop']) ? 'codashop' : 'codashop_dynamic')));
        assert(array_key_first($game['providers']) === $expectedFirst);
    }
    assert($available === 44);
};

$tests['removed storefront products are not active'] = static function (): void {
    $registry = new GameRegistry();
    foreach (['aethergazer', 'asphalt9', 'badlanders', 'barbarq', 'eosred', 'supermechachampions', 'warplanetonline'] as $code) {
        $game = $registry->get($code);
        assert(($game['status'] ?? null) === 'provider-unavailable');
        assert(($game['providers']['codashop']['enabled'] ?? true) === false);
        assert(($game['providerAvailability']['codashop']['status'] ?? null) === 'not-listed');
    }
};

$tests['regional codashop audit separates direct and voucher routes'] = static function (): void {
    $registry = new GameRegistry();
    foreach (['au2mobile', 'freefiremax', 'honkaiimpact3', 'magicchessgogo', 'metalslugawakening'] as $code) {
        $game = $registry->get($code);
        assert(($game['providerAvailability']['codashop']['status'] ?? null) === 'available');
        assert(($game['providers']['codashop_dynamic']['enabled'] ?? false) === true);
    }

    $freeFireMax = $registry->get('freefiremax');
    assert(($freeFireMax['providers']['codashop_dynamic']['storefronts'] ?? null) === ['id-id', 'my-mm']);

    $ragnarok = $registry->get('ragnarokm');
    assert(($ragnarok['providers']['codashop_dynamic']['pageSlugs'] ?? null) === ['ragnarok-m-eternal-love-big-cat-coin']);

    $honorOfKings = $registry->get('honorofkings');
    assert(($honorOfKings['providerAvailability']['codashop']['status'] ?? null) === 'voucher-or-external');
    assert(!isset($honorOfKings['providers']['codashop_dynamic']));
};

$tests['refreshed codashop metadata reactivates games'] = static function (): void {
    $registry = new GameRegistry();
    foreach (['loveanddeepspace', 'pixelgun3d'] as $code) {
        $game = $registry->get($code);
        assert(($game['status'] ?? null) === 'active');
        assert(($game['providers']['codashop_dynamic']['enabled'] ?? false) === true);
    }
};

$tests['gopay audit states are applied'] = static function (): void {
    $registry = new GameRegistry();
    $available = 0;
    $maintenance = 0;
    foreach ($registry->all() as $game) {
        $state = $game['providerAvailability']['gopaygames']['status'] ?? null;
        if ($state === 'available') {
            $available++;
            assert(($game['providers']['gopaygames']['enabled'] ?? false) === true);
            assert(!empty($game['providers']['gopaygames']['productPage']));
        } elseif ($state === 'maintenance') {
            $maintenance++;
            assert(($game['providers']['gopaygames']['enabled'] ?? true) === false);
        }
    }
    assert($available === 14);
    assert($maintenance === 0);
};

$tests['gopay metadata resolver reads storefront code'] = static function (): void {
    $html = <<<'HTML'
<script id="__NEXT_DATA__" type="application/json">{"props":{"pageProps":{"product":{"gameCode":"FOOTBALL_MASTER_2"}}}}</script>
HTML;
    $resolved = (new GopayMetadataResolver())->resolve($html, ['FOOTBALL_MASTER']);
    assert($resolved['maintenance'] === false);
    assert($resolved['code'] === 'FOOTBALL_MASTER_2');
    assert($resolved['source'] === 'storefront-runtime');
};

$tests['gopay storefront maintenance is explicit'] = static function (): void {
    $http = new FakeHttpClient(new HttpResponse(
        200,
        '<html><body>Layanan ini lagi dalam perbaikan</body></html>',
        ['content-type' => ['text/html']],
        null,
        20,
        'https://gopay.co.id/games/genshin-impact',
    ));
    $provider = new GopayGamesProvider($http, true);
    $result = $provider->lookup([
        'code' => 'genshinimpact',
        'providers' => [
            'gopaygames' => [
                'enabled' => true,
                'productPage' => 'https://gopay.co.id/games/genshin-impact',
                'code' => 'GENSHIN_IMPACT',
            ],
        ],
    ], '800000001', 'os_asia');
    assert($result->ok === false);
    assert($result->code === ResultCode::PROVIDER_MAINTENANCE);
    assert(count($http->requests) === 1);
};

$tests['gopay dynamic code performs account lookup'] = static function (): void {
    $page = new HttpResponse(
        200,
        '<script id="__NEXT_DATA__" type="application/json">{"props":{"pageProps":{"product":{"productCode":"POINT_BLANK"}}}}</script>',
        ['content-type' => ['text/html']],
        null,
        20,
        'https://gopay.co.id/games/point-blank',
    );
    $response = new HttpResponse(
        200,
        '{"status":true,"data":{"username":"PointBlankPlayer"}}',
        ['content-type' => ['application/json']],
        null,
        25,
        'https://gopay.co.id/games/v1/order/user-account',
    );
    $http = new FakeHttpClient($page, $response);
    $provider = new GopayGamesProvider($http, true);
    $result = $provider->lookup([
        'code' => 'pointblank',
        'providers' => [
            'gopaygames' => [
                'productPage' => 'https://gopay.co.id/games/point-blank',
                'codeCandidates' => ['POINTBLANK'],
            ],
        ],
    ], '10001');
    assert($result->ok === true);
    assert($result->nickname === 'PointBlankPlayer');
    assert(($http->requests[1]['payload']['code'] ?? null) === 'POINT_BLANK');
    assert(($result->meta['gopay_code_source'] ?? null) === 'storefront-runtime');
};

$tests['gopay resolver reads current RSC codes and ignores noverify'] = static function (): void {
    $resolver = new GopayMetadataResolver();
    $rsc = $resolver->resolve(
        '<script>self.__next_f.push([1,"{\\"product\\":{\\"slug\\":\\"honkai-impact-3\\",\\"code\\":\\"HONKAI_IMPACT\\"}}"])</script>',
    );
    assert($rsc['code'] === 'HONKAI_IMPACT');
    assert($rsc['source'] === 'storefront-runtime');

    $noVerify = $resolver->resolve(
        '<script>self.__next_f.push([1,"{\\"product\\":{\\"slug\\":\\"example\\",\\"code\\":\\"NOVERIFY\\"}}"])</script>',
    );
    assert($noVerify['code'] === null);
};

$tests['codashop alternate page slugs are tried'] = static function (): void {
    $missing = new HttpResponse(404, '<html>not found</html>', ['content-type' => ['text/html']], null, 5, 'https://www.codashop.com/en-my/primary-slug');
    $page = new HttpResponse(200, '<script type="application/json">{"product":{"voucherTypeName":"TEST_GAME","offers":[{"voucherPricePoint":{"id":55,"price":"1.00"}}]}}</script>', ['content-type' => ['text/html']], null, 6, 'https://www.codashop.com/en-my/alternate-slug');
    $success = new HttpResponse(200, '{"confirmationFields":{"username":"AltSlugPlayer"}}', ['content-type' => ['application/json']], null, 7, 'https://order-sg.codashop.com/initPayment.action');
    $http = new FakeHttpClient($missing, $page, $success);
    $provider = new CodashopDynamicProvider($http, true);
    $result = $provider->lookup([
        'code' => 'testgame',
        'providers' => [
            'codashop_dynamic' => [
                'pageSlugs' => ['primary-slug', 'alternate-slug'],
                'storefronts' => ['en-my'],
                'nicknamePaths' => ['confirmationFields.username'],
            ],
        ],
    ], '100');
    assert($result->ok === true);
    assert($result->nickname === 'AltSlugPlayer');
    assert(count($http->requests) === 3);
    assert(str_ends_with((string) $http->requests[1]['url'], '/alternate-slug'));
};

$failures = 0;
foreach ($tests as $name => $test) {
    try {
        $test();
        echo "[PASS] {$name}\n";
    } catch (Throwable $exception) {
        $failures++;
        fwrite(STDERR, "[FAIL] {$name}: {$exception->getMessage()}\n");
    }
}

echo sprintf("\n%d test(s), %d failure(s)\n", count($tests), $failures);
exit($failures === 0 ? 0 : 1);
