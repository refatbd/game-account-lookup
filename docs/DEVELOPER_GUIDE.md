# Developer Guide

This guide is for developers using `refatbd/game-account-lookup` as the base of a checker website, API, plugin or internal service.

## Design goals

1. Keep the core independent of Laravel or any UI.
2. Keep provider-specific requests and response parsing inside provider adapters.
3. Keep game metadata in the registry, not scattered through controllers.
4. Return one normalized `LookupResult` contract from every provider.
5. Cache only successful results by default.
6. Make unstable third-party integrations replaceable without breaking application code.
7. Keep automatic, single-provider and provider-comparison requests behaviorally distinct.
8. Treat storefront region restrictions as routing results, not invalid-player proof.

## Request lifecycle

```text
check(game, playerId, zoneId)
        |
        +-- resolve alias and load registry definition
        +-- normalize and validate input
        +-- check successful-result cache
        +-- select configured provider order
        +-- provider supports definition?
        +-- request + parse response
        +-- success: cache and return
        +-- failure: record attempt and try fallback
        +-- no success: normalized ALL_PROVIDERS_FAILED
```

## Cache control and provider execution

The fifth `check()` argument bypasses cache for one request:

```php
$fresh = $lookup->check(
    'freefire',
    '4422076728',
    bypassCache: true,
);
```

This skips both cache read and cache write. Existing cached values remain
untouched. Provider-specific requests use provider-scoped cache keys:

```php
$codashop = $lookup->check(
    'freefire',
    '4422076728',
    providerOrder: ['codashop'],
);
```

To compare every provider, iterate the registry provider list and call each one
strictly. The included template implements this pattern and returns a
`provider_results` array containing every normalized response.

## Main public objects

| Object | Responsibility |
|---|---|
| `GameAccountLookup` | Orchestration, validation, fallback and cache |
| `GameRegistry` | Canonical game codes, labels, aliases, zone rules and provider metadata |
| `ProviderInterface` | Stable adapter contract for one provider family |
| `LookupResult` | Normalized immutable success/failure response |
| `CacheInterface` | Framework-neutral successful-result cache |
| `HttpClientInterface` | Testable transport abstraction used by bundled adapters |

## Build an application service

Create one shared `GameAccountLookup` instance per application container/request lifecycle rather than constructing it repeatedly inside templates.

```php
$lookup = GameAccountLookup::make([
    'timeout' => 12,
    'connect_timeout' => 5,
    'verify_tls' => true,
    'debug' => false,
    'cache' => $persistentCache,
    'cache_ttl' => 600,
    'session_cache' => $persistentCache,
    'session_ttl' => 1800,
]);
```

Keep `debug` disabled in public production responses. Raw upstream metadata can contain implementation details that clients should not depend on.

## Game definition schema

| Key | Type | Required | Meaning |
|---|---|:---:|---|
| `code` | string | Yes | Canonical normalized identifier |
| `label` | string | Yes | Human-readable game name |
| `aliases` | string[] | No | Accepted alternative names |
| `requiresZone` | bool | Yes | Whether `zoneId` must be supplied |
| `status` | string | Yes | `active`, `metadata-refresh-required` or `external-provider-required` |
| `providers` | array | Yes | Provider-keyed configuration |
| `servers` | array | No | Human alias to provider server value map |
| `notes` | string | No | Maintainer-facing context |

Provider configuration belongs under its own key:

```php
'providers' => [
    'official_api' => [
        'endpoint' => 'https://api.example.test/player',
        'game_id' => 'example',
    ],
],
```

Public distributions must not add credentials to `resources/games.php`. This
single-owner private build deliberately includes the current direct-provider
session set; never change the repository visibility to public without removing
it. Environment variables remain available as overrides.


## Region-aware Codashop configuration

Use the `codashop_dynamic` provider for games whose current account-confirmation
metadata is embedded in regional product pages. It maintains cookie continuity
within each regional profile, discovers current product metadata and retries the
next profile after `PROVIDER_RESTRICTED`.

```php
'providers' => [
    'codashop_dynamic' => [
        'pageSlug' => 'example-game',
        'storefronts' => [
            ['name' => 'region-one', 'localePath' => 'en-aa'],
            ['name' => 'region-two', 'localePath' => 'en-bb'],
        ],
        'nicknamePaths' => ['confirmationFields.username'],
    ],
],
```

See [`CODASHOP_REGIONAL_ROUTING.md`](CODASHOP_REGIONAL_ROUTING.md) for profile
overrides, metadata discovery, diagnostics and regression-test requirements.

## Implement a provider

A provider must expose a stable key, declare whether it supports a game definition and return a `LookupResult`.

```php
final class OfficialApiProvider implements ProviderInterface
{
    public function __construct(
        private HttpClientInterface $http,
        private string $token,
    ) {}

    public function key(): string
    {
        return 'official_api';
    }

    public function supports(array $game): bool
    {
        $config = $game['providers'][$this->key()] ?? null;

        return is_array($config) && ($config['enabled'] ?? true) === true;
    }

    public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult
    {
        $config = $game['providers'][$this->key()];
        // Execute the authorized request and validate its exact response shape.

        return LookupResult::success(
            $game['code'],
            $playerId,
            $nickname,
            $this->key(),
            $zoneId,
            $server,
        );
    }
}
```

Provider rules:

- Set a finite connect and request timeout.
- Verify TLS; do not solve failures by disabling certificate checks in production.
- Validate status code, JSON decoding and required nickname fields separately.
- Return normalized error codes instead of leaking upstream messages.
- Do not treat every provider failure as an invalid player ID.
- Avoid automatic infinite retries.
- Sanitize debug metadata and fixtures.

## Provider fallback

Automatic provider order is normalized to prefer Garena, GoPay/classic Codashop and
other PHP providers before dynamic and browser-assisted providers. A request
that supplies `providerOrder` is followed exactly.

```php
$result = $lookup->check(
    'freefire',
    '4422076728',
    providerOrder: ['gopaygames', 'codashop'],
);
```

Use fallback for availability, not to bypass provider restrictions. Stop after the first normalized success.

## Cache integration

Implement `CacheInterface` for Redis, Memcached, a database or another PSR/framework cache.

```php
interface CacheInterface
{
    public function get(string $key): mixed;
    public function put(string $key, mixed $value, int $ttlSeconds): void;
    public function forget(string $key): void;
}
```

The core stores only successful `LookupResult` values. Do not cache permanent failure assumptions because an upstream outage and an invalid ID can look similar.

## Build a public API safely

A production controller should:

1. Accept only known game codes/aliases.
2. Set conservative player/zone length limits.
3. Use POST when request logs should not retain identifiers in query strings.
4. Apply per-IP and per-account throttles.
5. Cache successful lookups.
6. Return the normalized contract, not raw provider JSON.
7. Avoid direct browser-to-provider calls.
8. Log provider health separately from user-facing validation.

The included `template/` is for local development and demonstrates the flow, but it is not a complete public production gateway.

## Registry changes

After adding or changing a game:

```bash
php tools/generate-supported-games.php
php tools/lint.php
php tests/run.php
```

Then verify:

- alias resolution;
- zone-required validation;
- enabled provider order;
- valid and invalid sanitized fixtures;
- nickname path extraction;
- supported-game table changes;
- no secrets or short-lived tokens in Git.

## Backward compatibility

Treat these as public API once released:

- namespace and class names;
- method signatures;
- `LookupResult` field names;
- result/error code strings;
- registry canonical game codes;
- configuration keys.

Use semantic versioning. Removing or renaming any of the above requires a major release. Adding a game, alias, provider or optional result metadata is normally minor-version work.

## Recommended project layers

```text
app/UI or Controller
        |
app/PlayerLookupService
        |
refatbd/game-account-lookup
        |
provider adapters + cache
```

Keep pricing, order creation, authentication and customer data outside this package. This library should resolve game-account identity only.

## Dated provider catalog

Default definitions are assembled from two layers:

```text
resources/games.php
    Stable game codes, aliases, zone rules, parser hints and legacy fallback

resources/provider-catalog.php
    Dated provider availability, official product pages, page slugs,
    storefront order, GoPay page/code hints and maintenance state

ProviderCatalog::apply()
    Enables current routes, disables stale routes and builds provider order
```

Do not reactivate a stale provider by editing only `resources/games.php`. Update
`resources/provider-catalog.php`, record an official source URL and verification
date, add/refresh fixtures, and regenerate documentation.

The public `GameRegistry::list()` result includes:

```php
'provider_audit_verified_at' => '2026-07-31',
'provider_availability' => [
    'codashop' => [
        'status' => 'available',
        'verifiedAt' => '2026-07-31',
        'productUrl' => 'https://www.codashop.com/...',
    ],
    'gopaygames' => [
        'status' => 'maintenance',
        'productPage' => 'https://gopay.co.id/games/...',
    ],
],
```

Provider states are `available`, `maintenance`, `not-listed`, and
`voucher-or-external`. See [`PROVIDER_AVAILABILITY.md`](PROVIDER_AVAILABILITY.md).

## GoPay product-page preflight

The GoPay adapter can use a known stable code, runtime-discovered code, or both:

```php
'gopaygames' => [
    'productPage' => 'https://gopay.co.id/games/example-game',
    'code' => 'KNOWN_CODE',
    'codeCandidates' => ['ALTERNATE_CODE'],
],
```

Before account validation it can load the public product page, detect an
explicit maintenance notice and extract product/game codes from Next.js or RSC
metadata. Storefront-discovered codes take priority over configured fallback
codes. A product-page failure does not discard a known stable fallback code.

See [`GOPAY_PRODUCT_ROUTING.md`](GOPAY_PRODUCT_ROUTING.md).
