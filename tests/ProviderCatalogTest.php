<?php

declare(strict_types=1);

namespace Refatbd\GameAccountLookup\Tests;

use PHPUnit\Framework\TestCase;
use Refatbd\GameAccountLookup\Http\HttpResponse;
use Refatbd\GameAccountLookup\Providers\GopayGamesProvider;
use Refatbd\GameAccountLookup\Registry\GameRegistry;
use Refatbd\GameAccountLookup\ResultCode;
use Refatbd\GameAccountLookup\Support\GopayMetadataResolver;
use Refatbd\GameAccountLookup\Tests\Fakes\FakeHttpClient;

final class ProviderCatalogTest extends TestCase
{
    public function testCatalogCoversEveryDefaultDefinition(): void
    {
        $registry = new GameRegistry();
        self::assertCount(53, $registry->all());

        foreach ($registry->all() as $game) {
            self::assertArrayHasKey('codashop', $game['providerAvailability']);
            self::assertArrayHasKey('gopaygames', $game['providerAvailability']);
            self::assertNotNull($game['providerAuditVerifiedAt']);
        }
    }

    public function testAllCurrentCodashopDefinitionsKeepDynamicRoutingAfterDirectProviders(): void
    {
        $available = 0;
        foreach ((new GameRegistry())->all() as $game) {
            if (($game['providerAvailability']['codashop']['status'] ?? null) !== 'available') {
                continue;
            }
            $available++;
            $expectedFirst = isset($game['providers']['garena'])
                ? 'garena'
                : (isset($game['providers']['midasbuy'])
                ? 'midasbuy'
                : (isset($game['providers']['gopaygames'])
                ? 'gopaygames'
                : (isset($game['providers']['codashop']) ? 'codashop' : 'codashop_dynamic')));
            self::assertSame($expectedFirst, array_key_first($game['providers']));
            self::assertTrue($game['providers']['codashop_dynamic']['enabled']);
            self::assertNotEmpty($game['providers']['codashop_dynamic']['pageSlugs']);
            self::assertNotEmpty($game['providers']['codashop_dynamic']['storefronts']);
        }
        self::assertSame(44, $available);
    }

    public function testUnavailableProductsAreNotAdvertisedAsActive(): void
    {
        $registry = new GameRegistry();
        foreach (['aethergazer', 'asphalt9', 'badlanders', 'barbarq', 'eosred', 'supermechachampions', 'warplanetonline'] as $code) {
            $game = $registry->get($code);
            self::assertSame('provider-unavailable', $game['status']);
            self::assertFalse($game['providers']['codashop']['enabled']);
        }
    }

    public function testRegionalCodashopAuditClassifiesDirectAndVoucherRoutes(): void
    {
        $registry = new GameRegistry();

        foreach (['au2mobile', 'freefiremax', 'honkaiimpact3', 'magicchessgogo', 'metalslugawakening'] as $code) {
            $game = $registry->get($code);
            self::assertSame('available', $game['providerAvailability']['codashop']['status']);
            self::assertTrue($game['providers']['codashop_dynamic']['enabled']);
        }

        $freeFireMax = $registry->get('freefiremax');
        self::assertSame(['id-id', 'my-mm'], $freeFireMax['providers']['codashop_dynamic']['storefronts']);

        $ragnarok = $registry->get('ragnarokm');
        self::assertSame(
            ['ragnarok-m-eternal-love-big-cat-coin'],
            $ragnarok['providers']['codashop_dynamic']['pageSlugs'],
        );

        $honorOfKings = $registry->get('honorofkings');
        self::assertSame('voucher-or-external', $honorOfKings['providerAvailability']['codashop']['status']);
        self::assertArrayNotHasKey('codashop_dynamic', $honorOfKings['providers']);
    }

    public function testCurrentGopayDirectAndNoVerifyRoutesAreClassifiedSeparately(): void
    {
        $registry = new GameRegistry();

        foreach (['freefiremax', 'honkaiimpact3', 'metalslugawakening'] as $code) {
            $game = $registry->get($code);
            self::assertSame('available', $game['providerAvailability']['gopaygames']['status']);
            self::assertTrue($game['providers']['gopaygames']['enabled']);
        }

        foreach (['footballmaster2', 'genshinimpact', 'muorigin2', 'onmyojiarena', 'pointblank', 'valorant'] as $code) {
            $game = $registry->get($code);
            self::assertSame('voucher-or-external', $game['providerAvailability']['gopaygames']['status']);
            self::assertFalse($game['providers']['gopaygames']['enabled'] ?? false);
        }
    }

    public function testGoPayResolverAndMaintenancePreflight(): void
    {
        $resolved = (new GopayMetadataResolver())->resolve(
            '<script id="__NEXT_DATA__" type="application/json">{"props":{"pageProps":{"product":{"gameCode":"POINT_BLANK"}}}}</script>',
            ['POINTBLANK'],
        );
        self::assertSame('POINT_BLANK', $resolved['code']);
        self::assertSame('storefront-runtime', $resolved['source']);

        $rsc = (new GopayMetadataResolver())->resolve(
            '<script>self.__next_f.push([1,"{\\"product\\":{\\"slug\\":\\"honkai-impact-3\\",\\"code\\":\\"HONKAI_IMPACT\\"}}"])</script>',
        );
        self::assertSame('HONKAI_IMPACT', $rsc['code']);
        self::assertSame('storefront-runtime', $rsc['source']);

        $noVerify = (new GopayMetadataResolver())->resolve(
            '<script>self.__next_f.push([1,"{\\"product\\":{\\"slug\\":\\"example\\",\\"code\\":\\"NOVERIFY\\"}}"])</script>',
        );
        self::assertNull($noVerify['code']);

        $http = new FakeHttpClient(new HttpResponse(
            200,
            '<html>Layanan ini lagi dalam perbaikan</html>',
            ['content-type' => ['text/html']],
        ));
        $result = (new GopayGamesProvider($http))->lookup([
            'code' => 'genshinimpact',
            'providers' => [
                'gopaygames' => [
                    'productPage' => 'https://gopay.co.id/games/genshin-impact',
                    'code' => 'GENSHIN_IMPACT',
                ],
            ],
        ], '800000001', 'os_asia');

        self::assertSame(ResultCode::PROVIDER_MAINTENANCE, $result->code);
        self::assertCount(1, $http->requests);
    }
}
