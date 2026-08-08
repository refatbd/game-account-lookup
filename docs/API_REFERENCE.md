# API Reference

Namespace: `Refatbd\GameAccountLookup`

## `GameAccountLookup`

### `GameAccountLookup::make(array $options = []): self`

Creates an instance with the bundled providers.

| Option | Type | Default | Meaning |
|---|---|---:|---|
| `timeout` | int | `12` | Overall HTTP timeout in seconds |
| `connect_timeout` | int | `5` | Connection timeout in seconds |
| `verify_tls` | bool | `true` | Verify provider TLS certificates |
| `debug` | bool | `false` | Include sanitized provider metadata where supported |
| `cache` | `CacheInterface` | `NullCache` | Successful-result cache |
| `cache_ttl` | int | `300` | Cache lifetime in seconds |
| `session_cache` | `CacheInterface` | Same as `cache` | Persistent provider HTTP-session cache |
| `session_ttl` | int | `1800` | Provider session lifetime in seconds |
| `logger` | callable|null | `null` | Transport log callback |

### `check(string $game, string|int $playerId, string|int|null $zoneId = null, ?array $providerOrder = null, bool $bypassCache = false): LookupResult`

Resolves an alias, validates input, optionally checks cache and tries providers
in order. `bypassCache: true` skips both cache reads and cache writes for that
request. It does not disable provider-session continuity, which is transport
state rather than a cached player result.

```php
$result = $lookup->check('pubgm', '123456789');
$result = $lookup->check('mlbb', '123456789', '1234');
$result = $lookup->check('freefire', '4422076728', bypassCache: true);
$result = $lookup->check('freefire', '4422076728', providerOrder: ['codashop_dynamic']);
```

Cache entries are scoped by exact provider order. Automatic fallback and a
strict single-provider request therefore never share a cached response.

### `registerProvider(ProviderInterface $provider): self`

Adds or replaces a provider by its `key()`.

### `registerGame(string $code, array $definition): self`

Adds or replaces a runtime game definition.

### `registry(): GameRegistry`

Returns the active registry.

### `forget(string $game, string|int $playerId, string|int|null $zoneId = null): void`

Removes a successful lookup from the configured cache.

## `GameRegistry`

| Method | Returns | Purpose |
|---|---|---|
| `get(string $input)` | array|null | Definition resolved by code, label or alias |
| `resolve(string $input)` | string|null | Canonical code |
| `register(string $code, array $definition)` | self | Add or replace definition |
| `alias(string $alias, string $canonical)` | self | Add alias |
| `all()` | array | Raw canonical definitions |
| `list()` | list | Public/sanitized metadata list |
| `search(string $query)` | array | Code-to-label fuzzy matches |

`list()` returns:

```php
[
    'code' => 'freefire',
    'label' => 'Free Fire',
    'aliases' => ['ff', 'garena'],
    'requires_zone' => false,
    'status' => 'active',
    'providers' => ['gopaygames', 'codashop', 'codashop_dynamic', 'codashop_browser'],
    'servers' => [],
    'notes' => null,
    'provider_audit_verified_at' => '2026-07-31',
    'provider_availability' => [/* provider state and official evidence */],
]
```

## `LookupResult`

Immutable properties:

| JSON field | PHP property | Type | Meaning |
|---|---|---|---|
| `ok` | `$ok` | bool | Overall result |
| `code` | `$code` | string | Stable package result code |
| `message` | `$message` | string | Human-readable normalized message |
| `game` | `$game` | string|null | Canonical game code |
| `player_id` | `$playerId` | string|null | Normalized player ID |
| `zone_id` | `$zoneId` | string|null | Normalized zone/server input |
| `nickname` | `$nickname` | string|null | Resolved in-game name |
| `provider` | `$provider` | string|null | Final provider key |
| `server` | `$server` | string|null | Provider-returned or normalized server |
| `cached` | `$cached` | bool | Whether success came from cache |
| `meta` | `$meta` | array | Optional sanitized provider diagnostics or maintenance metadata |
| `attempts` | `$attempts` | array | Normalized provider attempt summaries |

Methods: `toArray()`, `jsonSerialize()`, `withAttempts()`, `withMeta()` and `asCached()`.

Provider attempts may contain a sanitized `meta` object with `http_status`,
`duration_ms`, `content_type`, `upstream_host`, `retry_after`, `ray_id`,
`transport_error` and `response_preview`. Region-aware Codashop responses may also include `codashop_profile`, `profiles_checked` and sanitized `profile_attempts`. Full headers, cookies and authentication values are never included by the bundled diagnostics helper.

## Result codes

| Code | Meaning |
|---|---|
| `SUCCESS` | Account resolved by a provider |
| `CACHED` | Successful result returned from cache |
| `INVALID_PLAYER` | Missing or invalid player input |
| `GAME_NOT_FOUND` | Unknown game/alias |
| `ZONE_REQUIRED` | Required zone/server missing |
| `ZONE_INVALID` | Invalid zone/server value |
| `PROVIDER_NOT_CONFIGURED` | Provider missing, disabled or unsupported |
| `MAINTENANCE_REQUIRED` | Game definition is retained but live lookup is intentionally unavailable |
| `PROVIDER_RESTRICTED` | Provider refused the request or region |
| `PROVIDER_MAINTENANCE` | The selected provider's public product page reports maintenance |
| `RATE_LIMITED` | Provider/application rate limit reached |
| `NETWORK_ERROR` | DNS, connection, TLS or timeout failure |
| `INVALID_RESPONSE` | Non-JSON or unexpected provider response |
| `ALL_PROVIDERS_FAILED` | No configured provider returned success |

## Contracts

### `ProviderInterface`

```php
public function key(): string;
public function supports(array $game): bool;
public function lookup(array $game, string $playerId, ?string $zoneId = null): LookupResult;
```

### `CacheInterface`

```php
public function get(string $key): mixed;
public function put(string $key, mixed $value, int $ttlSeconds): void;
public function forget(string $key): void;
```

### `HttpClientInterface`

Used by provider adapters so response parsing can be tested without network calls. See [`src/Contracts/HttpClientInterface.php`](../src/Contracts/HttpClientInterface.php).

### `SessionAwareHttpClientInterface`

Extends `HttpClientInterface` with warm-session detection, marking, and
invalidation. The bundled client implements it with domain-scoped cookies and
the optional `session_cache`; providers without persistent-session needs can
continue depending only on `HttpClientInterface`.

## Laravel configuration

Published file: `config/game-account-lookup.php`

| Environment variable | Default |
|---|---:|
| `GAME_LOOKUP_TIMEOUT` | `12` |
| `GAME_LOOKUP_CONNECT_TIMEOUT` | `5` |
| `GAME_LOOKUP_VERIFY_TLS` | `true` |
| `GAME_LOOKUP_DEBUG` | `false` |
| `GAME_LOOKUP_CACHE` | `true` |
| `GAME_LOOKUP_CACHE_TTL` | `300` |
| `GAME_LOOKUP_CACHE_STORE` | framework default |
| `GAME_LOOKUP_SESSION_TTL` | `1800` |

Facade class: `Refatbd\GameAccountLookup\Laravel\Facades\GameAccountLookup`.

## CLI

```text
vendor/bin/game-lookup list
vendor/bin/game-lookup GAME PLAYER_ID [ZONE_ID] [--debug]
```

The CLI returns formatted JSON for lookup commands and a registry list for `list`.
`--debug` is removed from the positional arguments before the optional zone is
parsed, so it may appear before or after the lookup arguments.

## Browser-assisted providers

`GameAccountLookup::make()` registers `codashop_browser` and
`midasbuy_browser`. They are enabled only for games whose registry definitions
contain the matching provider key. They require Node.js 22+, PHP `proc_open`,
Chrome or Edge, and writable profile storage. Override executable discovery with
`GAME_LOOKUP_NODE_PATH` and `GAME_LOOKUP_CHROME_PATH`. Free Fire uses debugging
port `9223`; PUBG Mobile uses `9224`.

Because these providers open a visible persistent browser, server deployments
should pass an explicit PHP-only `providerOrder` when browser automation is not
appropriate.

## Provider audit metadata

Default `GameRegistry::list()` entries additionally expose:

| Field | Type | Meaning |
|---|---|---|
| `provider_audit_verified_at` | string|null | Most recent audit date used by the default definition |
| `provider_availability` | array | Codashop/GoPay audit state, evidence URL, date and optional note |

These fields describe the bundled default catalog. Runtime games registered by
a consuming application are not automatically audited.

`PROVIDER_MAINTENANCE` means the provider's current public product page exists
but explicitly reports service maintenance. It differs from
`MAINTENANCE_REQUIRED`, which blocks the entire game because no enabled route
is available.
