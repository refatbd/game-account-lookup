# Game Account Lookup

[![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Tests](https://github.com/refatbd/game-account-lookup/actions/workflows/tests.yml/badge.svg)](https://github.com/refatbd/game-account-lookup/actions)
[![Maintainer](https://img.shields.io/badge/maintainer-RefatBD-blue)](https://github.com/refatbd)

A framework-independent PHP base package for building **game player-ID / UID
to nickname checkers**. It combines data-driven game registration, provider
fallback, dynamic provider tokens, caching, Laravel integration, and a clean
extension API.

> **Maintainer / Developer:** [RefatBD](https://github.com/refatbd)

## Why this repository exists

Most public game-name checker scripts are tied to one website, one game, or one
temporary upstream endpoint. This package separates the reusable parts:

```text
Your app or API
      |
      v
GameAccountLookup
      |
      +-- Game registry and aliases
      +-- Zone/server normalization
      +-- Provider fallback
      +-- Cache
      |
      +-- Garena Shop2Game direct adapter
      +-- Midasbuy xMidas direct adapter
      +-- GoPay Games adapter
      +-- Codashop classic adapter
      +-- Codashop dynamic-token adapter
      +-- Optional local browser-assisted adapters
      +-- Your own provider adapters
```

It is intended as a **base library**. Developers can use it in Laravel, plain
PHP, WordPress, Symfony, or a custom API and add their own official or
authorized providers without changing the core.

## Important notice

This is an unofficial developer tool. The bundled provider adapters interact
with public account-validation flows exposed by third-party top-up services;
they are not official game-publisher APIs. Those flows, payload fields, product
IDs, regional restrictions, and anti-abuse rules can change without notice.

Use this package server-side, cache successful lookups, add rate limits, follow
the provider's terms, and prefer official/authorized APIs whenever available.
Do not use it to evade access controls or overload third-party services.

## Features

- PHP 8.1+ with no framework requirement
- 53 data-driven game definitions with a dated Codashop/GoPay availability catalog
- Direct Garena Shop2Game lookup for Free Fire, including normalized country/region output
- Configurable PHP/OpenSSL Midasbuy `xMidas` payload generation for any valid PUBG Mobile Player ID
- GoPay Games page-preflight/code discovery, Codashop classic and region-aware runtime-metadata adapters, plus optional Dancing Idol-compatible adapters
- Optional browser-assisted Codashop and Midasbuy validation for storefronts that require an interactive Chrome/Edge session
- Automatic fallback between configured providers and Codashop regional storefront profiles
- Strict single-provider execution and all-provider comparison mode
- Per-request cache bypass without clearing existing cached results
- Provider-scoped cache keys that prevent cross-provider cache contamination
- UID-only and UID + zone/server games
- Game aliases such as `ff`, `mlbb`, `pubgm`, `codm`, `hsr`, and `zzz`
- Runtime game and provider registration
- Pluggable cache contract
- Structured, JSON-serializable results
- Laravel auto-discovery, facade, configuration, and Laravel cache support
- CLI utility
- Standalone offline tests and PHPUnit tests
- GitHub Actions matrix for PHP 8.1–8.4
- Session-cookie continuity and runtime Codashop metadata discovery without committed short-lived JWT/SKU tokens
- Official product-page evidence, provider availability states, and a non-destructive provider audit CLI

## Installation

Core requirements:

- PHP 8.1 or newer
- PHP extensions: cURL, JSON, and OpenSSL
- Composer 2

OpenSSL is used by the direct PUBG Mobile Midasbuy provider. If it is missing,
that provider reports `PROVIDER_NOT_CONFIGURED` and the remaining configured
providers can still run.

After publishing the package on Packagist:

```bash
composer require refatbd/game-account-lookup
```

Before Packagist publication, add the GitHub repository to the consuming
project:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/refatbd/game-account-lookup"
    }
  ],
  "require": {
    "refatbd/game-account-lookup": "dev-main"
  }
}
```

Then run:

```bash
composer update refatbd/game-account-lookup
```

The direct HTTP providers require no JavaScript runtime. The optional
`codashop_browser` and `midasbuy_browser` providers additionally require Node.js
22 or newer, `proc_open`, and Google Chrome or Microsoft Edge. See
[Browser-assisted providers](#browser-assisted-providers) before enabling those
local fallbacks.

Direct PUBG Midasbuy lookup is available through the PHP-only `midasbuy`
provider. This private build includes the current rotating values and also
supports environment overrides. It generates an encrypted payload for each
Player ID. See
[`docs/MIDASBUY_DIRECT_SETUP.md`](docs/MIDASBUY_DIRECT_SETUP.md).

## Run the included web tester

From the repository root:

```bash
composer install
php -S 127.0.0.1:8765 -t template
```

Open `http://127.0.0.1:8765/`. Windows and Linux/macOS launchers are also
available as `template/start.bat` and `template/start.sh`.

The tester supports Live, Demo, automatic fallback, one-provider-only, all
providers, and per-request cache-bypass modes. Its API endpoints are:

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/health.php` | GET | Runtime and extension health |
| `/api/games.php` | GET | Public game/provider registry |
| `/api/check.php` | GET or POST | Account lookup |

The template is a local development tool. See
[`docs/WEB_TESTER.md`](docs/WEB_TESTER.md) before adapting it for public use.

## Plain PHP quick start

```php
<?php

require __DIR__ . '/vendor/autoload.php';

use Refatbd\GameAccountLookup\Cache\ArrayCache;
use Refatbd\GameAccountLookup\GameAccountLookup;

$lookup = GameAccountLookup::make([
    'cache' => new ArrayCache(),
    'cache_ttl' => 300,
]);

$result = $lookup->check('freefire', '4422076728');

echo json_encode(
    $result,
    JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
);
```

`GameAccountLookup::make()` also accepts HTTP timeouts, TLS verification,
debugging, caching and a transport logger. See the complete
[API reference](docs/API_REFERENCE.md).

Zone/server games accept a third argument:

```php
$result = $lookup->check('mobilelegends', 'USER_ID', 'ZONE_ID');
$result = $lookup->check('genshin', 'UID', 'os_asia');
```

Bypass cache for one request without deleting the existing cached result:

```php
$result = $lookup->check(
    'freefire',
    '4422076728',
    bypassCache: true,
);
```

Force one provider only:

```php
$result = $lookup->check(
    'freefire',
    '4422076728',
    providerOrder: ['codashop_dynamic'],
    bypassCache: true,
);
```

## Result format

Success:

```json
{
  "ok": true,
  "code": "SUCCESS",
  "message": "Game account found.",
  "game": "freefire",
  "player_id": "4422076728",
  "zone_id": null,
  "nickname": "PlayerName",
  "provider": "gopaygames",
  "server": null,
  "country": null,
  "cached": false,
  "attempts": [
    {
      "provider": "gopaygames",
      "ok": true,
      "code": "SUCCESS",
      "message": "Game account found."
    }
  ]
}
```

Failure:

```json
{
  "ok": false,
  "code": "ALL_PROVIDERS_FAILED",
  "message": "No configured provider could resolve this account.",
  "game": "pubgmobile",
  "player_id": "123456789",
  "nickname": null,
  "cached": false
}
```

Application code should check `ok` and `code`; it should not depend on a
provider's original message text. Provider attempts can also contain sanitized
diagnostics such as upstream HTTP status, duration, host, retry information and
a short redacted response preview.
Region-aware Codashop attempts additionally expose the selected storefront and a sanitized profile route.


## Troubleshooting provider failures

The local template does not return an origin `502` merely because a third-party
provider failed. Completed lookup requests use HTTP `200`; inspect `ok`, `code`
and `attempts`. This preserves the actual error JSON when the tester is behind
Cloudflare or another reverse proxy.

A Codashop response such as `Topup region blocked for player` is treated as a
regional storefront-routing result, not proof that the UID is invalid. The
runtime adapter keeps the storefront session cookies, discovers current product
metadata, and tries the next configured regional profile. Free Fire, FC Mobile
and Farlight 84 demonstrate this pattern in the bundled registry.

See [`docs/CODASHOP_REGIONAL_ROUTING.md`](docs/CODASHOP_REGIONAL_ROUTING.md)
for the reusable profile format and [`docs/TROUBLESHOOTING.md`](docs/TROUBLESHOOTING.md)
for provider, cURL and Cloudflare diagnostics.

## Laravel usage

Laravel package discovery registers the service provider and facade
automatically.

Publish configuration:

```bash
php artisan vendor:publish --tag=game-account-lookup-config
```

Dependency injection:

```php
use Refatbd\GameAccountLookup\GameAccountLookup;

public function check(GameAccountLookup $lookup)
{
    return response()->json(
        $lookup->check('pubgm', request('player_id'))
    );
}
```

Facade:

```php
use Refatbd\GameAccountLookup\Laravel\Facades\GameAccountLookup;

$result = GameAccountLookup::check('mlbb', 'USER_ID', 'ZONE_ID');
```

Recommended API route:

```php
Route::get('/game-account', PlayerLookupController::class)
    ->middleware('throttle:30,1');
```

See [`examples/laravel-controller.php`](examples/laravel-controller.php).

## CLI

```bash
vendor/bin/game-lookup list
vendor/bin/game-lookup freefire 4422076728
vendor/bin/game-lookup mobilelegends USER_ID ZONE_ID
vendor/bin/game-lookup freefire 4422076728 --debug
vendor/bin/game-lookup genshinimpact UID os_asia --debug
```

Use `--debug` only during development because upstream response metadata may be
included in results or logs. The flag may appear before or after the positional
arguments and is never treated as a zone value.

## Supported games

The default registry combines stable game/input definitions with a provider availability snapshot updated through **2026-08-01**. Current Codashop direct-ID titles use dynamic regional product-page discovery first; verified GoPay products use public product-page preflight before the account endpoint. Stale, maintenance, `NOVERIFY`, voucher-only, or no-longer-listed routes are not silently presented as active.

Codashop availability is country-specific. The audit checks its global country index and sitemap before marking a game unavailable, then keeps only regional pages with a direct Player/User/Role ID form as lookup routes. Shell vouchers, redeem codes, and external checkouts remain classified separately even when the game appears in a regional catalog.

See [Provider Availability Audit](docs/PROVIDER_AVAILABILITY.md) for the full provider-by-provider evidence matrix and refresh workflow.

The complete table below is generated from [`resources/games.php`](resources/games.php). It includes canonical codes, zone requirements, provider coverage, maintenance status and accepted aliases. Run `composer docs:games` after changing the registry.

<!-- supported-games:start -->

> Provider audit snapshot through (**2026-08-01**): **53 game definitions**, **46 active**, **44 Codashop direct-ID listings**, **14 GoPay direct-ID listings**, and **0 GoPay listings under maintenance**.

| Game | Code | Zone Required | Known Server Values | Codashop | GoPay | Other | Status | Aliases |
|---|---|:---:|---|---|---|---|---|---|
| 8 Ball Pool | `8ballpool` | No | — | [Available](https://www.codashop.com/en-bd/8-ball-pool)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `eightballpool`, `8bp` |
| Aether Gazer | `aethergazer` | No | — | Not listed | Not listed | — | `provider-unavailable` | `aether` |
| Arena of Valor | `aov` | No | — | [Available](https://www.codashop.com/id-id/arena-of-valor)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/arena-of-valor)<br><sub>page preflight + account API</sub> | — | `active` | `arenaofvalor` |
| Asphalt 9: Legends | `asphalt9` | Yes | Free-form | Not listed | Not listed | — | `provider-unavailable` | `gamelefta9`, `a9` |
| AU2 Mobile | `au2mobile` | No | — | [Available](https://www.codashop.com/en-kh/au2-mobile)<br><sub>dynamic regional</sub> | Not listed | `dancingidol` (disabled) | `active` | `autwomobile` |
| Auto Chess | `autochess` | No | — | [Available](https://www.codashop.com/id-id/auto-chess)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Azur Lane | `azurlane` | Yes | `1`, `2`, `3`, `4`, `5`, `6` | [Available](https://www.codashop.com/id-id/azur-lane)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `azur` |
| Badlanders | `badlanders` | Yes | `11001`, `21004` | Not listed | Not listed | — | `provider-unavailable` | — |
| BarbarQ | `barbarq` | No | — | Not listed | Not listed | — | `provider-unavailable` | — |
| Basketrio | `basketrio` | Yes | `2`, `3`, `4` | [Available](https://www.codashop.com/id-id/basketrio)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Call of Duty Mobile | `cod` | No | — | [Available](https://www.codashop.com/id-id/call-of-duty-mobile)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/call-of-duty-mobile-id)<br><sub>page preflight + account API</sub> | — | `active` | `codm`, `callofduty`, `callofdutymobile` |
| Captain Tsubasa: Dream Team | `captaintsubasa` | No | — | [Available](https://www.codashop.com/id-id/captain-tsubasa-dream-team)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `tsubasa` |
| Crisis Action | `crisisaction` | Yes | Free-form | [Available](https://www.codashop.com/id-id/crisis-action)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `caherogames` |
| Dragon City | `dragoncity` | No | — | [Available](https://www.codashop.com/id-id/dragon-city)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Dragon Raja | `dragonraja` | No | — | [Available](https://www.codashop.com/id-id/dragon-raja)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `zulongdragonraja` |
| EOS RED | `eosred` | Yes | Free-form | Not listed | Not listed | — | `provider-unavailable` | — |
| Farlight 84 | `farlight84` | No | — | [Available](https://www.codashop.com/en-my/farlight-84)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `farlight` |
| FC Mobile | `fcmobile` | No | — | [Available](https://www.codashop.com/en-bd/ea-sports-fc-mobile)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/ea-sports-fc-mobile)<br><sub>page preflight + account API</sub> | — | `active` | `fcm`, `eafcmobile` |
| Football Master 2 | `footballmaster2` | No | — | [Available](https://www.codashop.com/id-id/football-master-2)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/football-master-2) | — | `active` | `footballmaster` |
| Free Fire | `freefire` | No | — | [Available](https://www.codashop.com/en-my/free-fire)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/free-fire)<br><sub>page preflight + account API</sub> | `garena`, `codashop_browser` | `active` | `ff`, `garena` |
| Free Fire MAX | `freefiremax` | No | — | [Available](https://www.codashop.com/id-id/free-fire-max)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/free-fire-max)<br><sub>page preflight + account API</sub> | — | `active` | `ffmax` |
| Genshin Impact | `genshinimpact` | Yes | `os_usa`, `os_euro`, `os_asia`, `os_cht` | [Available](https://www.codashop.com/id-id/genshin-impact)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/genshin-impact) | — | `active` | `genshin`, `gi` |
| Growtopia | `growtopia` | No | — | [Available](https://www.codashop.com/id-id/growtopia)<br><sub>dynamic regional</sub> | [Voucher/external only](https://gopay.co.id/games/growtopia) | — | `active` | `gt` |
| Hago | `hago` | No | — | [Available](https://www.codashop.com/id-id/hago)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/hago) | — | `active` | — |
| Honkai Impact 3 | `honkaiimpact3` | No | — | [Available](https://www.codashop.com/id-id/honkai-impact-3)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/honkai-impact-3)<br><sub>page preflight + account API</sub> | — | `active` | `hi3`, `hi3rd` |
| Honkai: Star Rail | `honkaistarrail` | Yes | `prod_official_usa`, `prod_official_eur`, `prod_official_asia`, `prod_official_cht` | [Available](https://www.codashop.com/id-id/honkai-star-rail)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/honkai-star-rail) | — | `active` | `hsr`, `starrail` |
| Honor of Kings | `honorofkings` | No | — | [Voucher/external only](https://www.codashop.com/en-us/honor-of-kings) | [Available](https://gopay.co.id/games/honor-of-kings)<br><sub>page preflight + account API</sub> | — | `active` | `hok` |
| Laplace M | `laplacem` | No | — | [Available](https://www.codashop.com/id-id/laplace-m)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `zlongame` |
| League of Legends: Wild Rift | `wildrift` | No | — | [Available](https://www.codashop.com/id-id/league-of-legends-wild-rift)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/league-of-legends-wild-rift)<br><sub>page preflight + account API</sub> | — | `active` | `lolwildrift` |
| LifeAfter | `lifeafter` | Yes | Free-form | [Available](https://www.codashop.com/id-id/lifeafter)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/lifeafter)<br><sub>page preflight + account API</sub> | — | `active` | `neteaselifeafter` |
| Love and Deepspace | `loveanddeepspace` | No | — | [Available](https://www.codashop.com/en-sg/love-and-deepspace)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/love-and-deepspace) | — | `active` | `lad` |
| Magic Chess: Go Go | `magicchessgogo` | Yes | Free-form | [Available](https://www.codashop.com/en-us/magic-chess-go-go)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/magic-chess-go-go)<br><sub>page preflight + account API</sub> | — | `active` | `magicchess`, `mcgg` |
| MARVEL Duel | `marvelduel` | No | — | [Available](https://www.codashop.com/id-id/marvel-duel)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/marvel-duel) | — | `active` | — |
| Metal Slug: Awakening | `metalslugawakening` | No | — | [Available](https://www.codashop.com/en-my/metal-slug-awakening)<br><sub>dynamic regional</sub> | [Available](https://gopay.co.id/games/metal-slug-awakening)<br><sub>page preflight + account API</sub> | — | `active` | `metalslug`, `msa` |
| Mobile Legends: Adventure | `mobilelegendsadventure` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mobile-legends-adventure)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `mla`, `adventure` |
| Mobile Legends: Bang Bang | `mobilelegends` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mobile-legends)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/mobile-legends-bang-bang)<br><sub>page preflight + account API</sub> | — | `active` | `ml`, `mobilelegend`, `mlbb` |
| MU Origin 2 | `muorigin2` | Yes | Free-form | [Available](https://www.codashop.com/id-id/mu-origin-2)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/mu-origin-2) | — | `active` | `ourpalm` |
| ONE PUNCH MAN: The Strongest | `onepunchman` | Yes | Free-form | [Available](https://www.codashop.com/id-id/one-punch-man-the-strongest)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/one-punch-man-the-strongest) | — | `active` | `opm` |
| Onmyoji Arena | `onmyojiarena` | No | — | [Available](https://www.codashop.com/id-id/onmyoji-arena)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/onmyoji-arena) | — | `active` | — |
| Pixel Gun 3D | `pixelgun3d` | No | — | [Available](https://www.codashop.com/id-id/pixel-gun-3d)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/pixel-gun-3d) | — | `active` | `pg3d` |
| Point Blank | `pointblank` | No | — | [Available](https://www.codashop.com/id-id/point-blank)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/point-blank) | — | `active` | `pb` |
| PUBG Mobile | `pubgmobile` | No | — | [Voucher/external only](https://www.codashop.com/en-bd/pubg-mobile) | [Available](https://gopay.co.id/games/pubg-mobile-global)<br><sub>page preflight + account API</sub> | `midasbuy`, `midasbuy_browser` | `active` | `pubg`, `pubgm`, `pubgid` |
| Ragnarok M: Eternal Love | `ragnarokm` | Yes | Free-form | [Available](https://www.codashop.com/id-id/ragnarok-m-eternal-love-big-cat-coin)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | `rom` |
| Ragnarok X: Next Generation | `ragnarokx` | Yes | Free-form | [Available](https://www.codashop.com/id-id/ragnarok-x-next-generation)<br><sub>dynamic regional + legacy fallback</sub> | [Available](https://gopay.co.id/games/ragnarok-x-next-generation)<br><sub>page preflight + account API</sub> | — | `active` | `rox` |
| Sausage Man | `sausageman` | No | — | [Available](https://www.codashop.com/id-id/sausage-man)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| Speed Drifters | `speeddrifters` | No | — | [Available](https://www.codashop.com/id-id/speed-drifters)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/speed-drifters) | — | `active` | — |
| Super Mecha Champions | `supermechachampions` | No | — | Not listed | Not listed | — | `provider-unavailable` | `smc` |
| Super SUS | `supersus` | No | — | [Available](https://www.codashop.com/id-id/super-sus)<br><sub>dynamic regional + legacy fallback</sub> | Not listed | — | `active` | — |
| VALORANT | `valorant` | No | — | [Available](https://www.codashop.com/id-id/valorant)<br><sub>dynamic regional</sub> | [Voucher/external only](https://gopay.co.id/games/valorant) | — | `active` | `val` |
| War Planet Online | `warplanetonline` | No | — | Not listed | Not listed | — | `provider-unavailable` | `wpo` |
| Watcher of Realms | `watcherofrealms` | Yes | Free-form | [Available](https://www.codashop.com/id-id/watcher-of-realms)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/watcher-of-realms) | — | `active` | `wor` |
| Zenless Zone Zero | `zenlesszonezero` | Yes | `prod_gf_us`, `prod_gf_eu`, `prod_gf_jp`, `prod_gf_sg` | [Available](https://www.codashop.com/id-id/zenless-zone-zero)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/zenless-zone-zero) | — | `active` | `zzz` |
| ZEPETO | `zepeto` | No | — | [Available](https://www.codashop.com/id-id/zepeto)<br><sub>dynamic regional + legacy fallback</sub> | [Voucher/external only](https://gopay.co.id/games/zepeto) | — | `active` | `naverzcorporation` |

<!-- supported-games:end -->

A registry entry means the package knows the expected provider configuration.
It does **not** guarantee that the external provider is currently available in
every country or that every product ID remains valid.

List them programmatically:

```php
$games = $lookup->registry()->list();
$matches = $lookup->registry()->search('pubg');
```

## Provider fallback

`GameRegistry` normalizes bundled provider order for shared-hosting
compatibility: direct PHP providers run first, regional discovery follows, and
providers that require a local browser/process always run last.

| Game | Automatic order |
|---|---|
| Free Fire | `garena -> gopaygames -> codashop -> codashop_dynamic -> codashop_browser` |
| PUBG Mobile | `midasbuy -> gopaygames -> midasbuy_browser` |

Other games use their configured direct GoPay/Codashop routes followed by
dynamic Codashop discovery where available. You may override the exact order
for one request:

```php
$result = $lookup->check(
    'freefire',
    '4422076728',
    providerOrder: ['codashop_dynamic', 'gopaygames', 'codashop'],
);
```

The next configured provider is tried when the previous provider fails. A
successful result stops the chain and can be cached. Cache entries are scoped to
the exact provider order, so a strict dynamic-Codashop request cannot return a
GoPay result cached by automatic fallback mode.

The included web template also provides **All providers** mode. It calls each
configured provider independently and shows every normalized response instead
of stopping after the first success. See [`docs/WEB_TESTER.md`](docs/WEB_TESTER.md).

## Browser-assisted providers

Free Fire and PUBG Mobile include optional local browser-assisted routes for
storefront validation that cannot always be completed with an HTTP request:

| Game | Provider | Automatic order | Default port |
|---|---|---|---:|
| Free Fire | `codashop_browser` | After Garena, GoPay, classic Codashop and Codashop dynamic | `9223` |
| PUBG Mobile | `midasbuy_browser` | After direct Midasbuy and GoPay | `9224` |

These providers launch or reuse a visible Chrome/Edge session, keep a persistent
profile under `template/storage/`, and stop after reading the account
confirmation. They do not confirm or submit a purchase. A challenge may require
you to complete verification in the opened browser and retry the lookup.

Requirements:

- Node.js 22 or newer, including global `fetch` and `WebSocket`
- Google Chrome or Microsoft Edge
- PHP `proc_open`
- A writable `template/storage/` directory
- Free localhost debug ports `9223` and `9224`

The helpers auto-detect standard Windows Chrome/Edge installations. Set explicit
paths on Windows, Linux or macOS when needed:

```bash
GAME_LOOKUP_NODE_PATH=/usr/local/bin/node
GAME_LOOKUP_CHROME_PATH=/usr/bin/google-chrome
```

On a server, container or shared host, automatic fallback tries PHP-only
providers before browser automation. If Node.js, `proc_open`, Chrome/Edge or a
writable profile is unavailable, the browser attempt returns a normal provider
failure instead of terminating PHP. You can also explicitly choose PHP-only
providers:

```php
$freeFire = $lookup->check(
    'freefire',
    'PLAYER_ID',
    providerOrder: ['garena', 'gopaygames', 'codashop', 'codashop_dynamic'],
);

$pubg = $lookup->check(
    'pubgmobile',
    'PLAYER_ID',
    providerOrder: ['midasbuy', 'gopaygames'],
);
```

Browser-provider failures may include a screenshot path under
`template/storage/`. Treat those screenshots and persistent profiles as local
diagnostic data and do not publish them.

For the Free Fire Garena provider, see
[`docs/GARENA_SESSION_CAPTURE.md`](docs/GARENA_SESSION_CAPTURE.md) for the
manual verified-session cookie/header capture, deployment, rotation, and
troubleshooting workflow.

## Rotating direct-provider sessions

Free Fire and PUBG Mobile have direct first-priority adapters whose upstream
session values may rotate:

| Provider | Runtime | Rotating values | Maintenance guide |
|---|---|---|---|
| `garena` | PHP/cURL | Shop2Game cookies and matching DataDome client ID | [Garena session capture](docs/GARENA_SESSION_CAPTURE.md) |
| `midasbuy` | PHP/cURL/OpenSSL | AES key/IV, `ctoken`, token version, and possibly request schema | [Midasbuy direct rotation](docs/MIDASBUY_DIRECT_SETUP.md) |

This private build keeps the currently captured Garena and Midasbuy values in
one canonical `BundledCredentialProvider`, so the direct providers can work
immediately while those upstream sessions remain valid. A credential chain
checks complete environment overrides first and bundled values second. Garena
uses
`GAME_LOOKUP_GARENA_COOKIE` and
`GAME_LOOKUP_GARENA_DATADOME_CLIENT_ID`. Midasbuy reads
`GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY`,
`GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV`,
`GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION`, and
`GAME_LOOKUP_MIDASBUY_CTOKEN` for its override group. When values rotate, update
the complete compatible set together and keep this repository private. See
[`docs/CREDENTIAL_MANAGEMENT.md`](docs/CREDENTIAL_MANAGEMENT.md).

An expired or restricted direct session is a provider-level result, not a PHP
fatal error. Automatic mode continues through the remaining providers. Use a
known-valid ID, strict single-provider mode, and `bypassCache: true` when
testing refreshed values. Do not expose cookies, tokens, keys, browser profiles,
or debug responses to client-side code, public logs, or a public repository.

## Add a custom provider

```php
use Refatbd\GameAccountLookup\Contracts\ProviderInterface;
use Refatbd\GameAccountLookup\DTO\LookupResult;

final class OfficialGameProvider implements ProviderInterface
{
    public function key(): string
    {
        return 'official_game';
    }

    public function supports(array $game): bool
    {
        return isset($game['providers'][$this->key()]);
    }

    public function lookup(
        array $game,
        string $playerId,
        ?string $zoneId = null
    ): LookupResult {
        // Call an authorized API here.

        return LookupResult::success(
            $game['code'],
            $playerId,
            'PlayerName',
            $this->key(),
            $zoneId,
        );
    }
}
```

Register the provider and game:

```php
$lookup
    ->registerProvider(new OfficialGameProvider())
    ->registerGame('official-game', [
        'label' => 'Official Game',
        'aliases' => ['og'],
        'requiresZone' => false,
        'providers' => [
            'official_game' => [],
        ],
    ]);
```

For small integrations, use
[`CallbackProvider`](src/Providers/CallbackProvider.php). A complete example is
in [`examples/custom-provider.php`](examples/custom-provider.php).

## Add or update a game

Default definitions are stored in
[`resources/games.php`](resources/games.php). Keep provider-specific data under
the `providers` key:

```php
'examplegame' => [
    'code' => 'examplegame',
    'label' => 'Example Game',
    'aliases' => ['eg'],
    'requiresZone' => true,
    'status' => 'active',
    'providers' => [
        'gopaygames' => [
            'code' => 'EXAMPLE_GAME',
        ],
        'codashop' => [
            'vppId' => 12345,
            'price' => '10000.0000',
            'voucherTypeName' => 'EXAMPLE_GAME',
            'zone' => 'user',
            'nicknamePaths' => [
                'confirmationFields.username',
            ],
        ],
    ],
];
```

Read [`docs/ADDING_A_GAME.md`](docs/ADDING_A_GAME.md) before committing product
IDs or provider metadata.

## Cache and rate limiting

The package caches only successful results. Cache keys include the provider
execution scope. Pass `bypassCache: true` to skip both cache reading and writing
for a single request. `ArrayCache` is process-local and is mainly useful for
examples and tests. Production applications should inject
a persistent implementation backed by Redis, Memcached, a database, or the
Laravel cache system.

Recommended public API protections:

- Validate game, player ID, and zone lengths.
- Apply per-IP and per-account rate limits.
- Cache successful lookups for several minutes or longer.
- Add request timeouts and circuit breakers.
- Do not reveal raw provider responses in production.
- Do not call provider endpoints directly from browser JavaScript.

## Testing

No live provider request is required for the core test suite:

```bash
composer install
composer quality
```

`composer quality` runs syntax validation, standalone regression tests,
PHPUnit, generated-document checks, and the template smoke test. Individual
commands remain available as `composer lint`, `composer test:standalone`,
`composer test`, `composer test:docs`, and `composer test:template`.

Live upstream checks should be kept in a separate opt-in integration test suite
because they are region-sensitive and can trigger provider rate limits.

## Maintenance workflow

Before changing provider metadata or publishing a release:

```bash
php tools/audit-provider-catalog.php
php tools/audit-provider-catalog.php --live
composer docs:games
composer quality
```

The live audit checks public product-page reachability only. It does not submit player IDs or purchases. Review [Provider Availability Audit](docs/PROVIDER_AVAILABILITY.md) and [Provider Maintenance](docs/PROVIDER_MAINTENANCE.md) before enabling a route.

A GoPay storefront listing is not automatically a nickname-validation route.
Products whose live configuration uses `NOVERIFY` are voucher/top-up forms and
are intentionally excluded from `GopayGamesProvider`. Add a game only after its
product page exposes a direct account-validation code and both valid and invalid
test cases have been reviewed.


When a provider stops working:

1. Reproduce with debug logging in a local environment.
2. Inspect the provider's current public validation request.
3. Update only the affected adapter or game definition.
4. Never commit cookies, access tokens, personal data, or short-lived JWTs.
5. Add a sanitized response fixture and regression test.
6. Document the change in `CHANGELOG.md`.
7. Release a semantic version tag.

More detail: [`docs/PROVIDER_MAINTENANCE.md`](docs/PROVIDER_MAINTENANCE.md).

## Project authorship and acknowledgements

**Game Account Lookup is developed and maintained by
[RefatBD](https://github.com/refatbd).** The current repository is a
substantially expanded, framework-independent package—not merely a repackaging
of an earlier nickname-checker script.

Major original development in this repository includes the unified provider
contract and fallback engine, normalized result and diagnostic model, the
53-game registry and dated availability catalog, regional Codashop discovery,
GoPay product preflight, direct Garena and encrypted Midasbuy integrations,
shared-hosting-safe failure handling, provider-scoped caching, Laravel/CLI
integration, the local web tester, audit tooling, generated documentation, and
the complete regression-test suite.

Early research and implementation patterns were informed in part by these
MIT-licensed projects:

- [`triyatna/php-valid-game`](https://github.com/triyatna/php-valid-game)
- [`aditamagf/check-ign`](https://github.com/aditamagf/check-ign)

They are acknowledged as historical references and third-party sources, not as
a description of the current project's scope or authorship. Their required
copyright and license notices are preserved in [`NOTICE.md`](NOTICE.md).

## Security

Please do not publish a vulnerability as a public issue. Follow
[`SECURITY.md`](SECURITY.md).

## License

MIT. See [`LICENSE`](LICENSE).

---

Developed and maintained by **[RefatBD](https://github.com/refatbd)**.
