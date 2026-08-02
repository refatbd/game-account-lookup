# Architecture

## Core rule

Application code talks only to `GameAccountLookup`. It should not know which
provider succeeded or how that provider formats its response.

## Main components

### `GameAccountLookup`

The orchestrator validates input, resolves aliases, checks cache, runs providers
in order, normalizes failures, and caches successful results.

### `GameRegistry`

A data-driven registry stores game labels, aliases, zone requirements, server
maps, status, and provider-specific configuration.

### `HttpClientInterface`

Providers depend on a small HTTP contract, so response parsing can be tested with
fixtures without performing live network calls.

### `ProviderInterface`

Each provider has a stable key, declares whether it supports a game definition,
and returns one `LookupResult`.

### `LookupResult`

A provider-neutral DTO. It is JSON serializable and preserves provider attempts
without exposing raw responses unless debug mode is enabled.

### `CacheInterface`

A small cache abstraction that avoids coupling the core to Laravel, PSR cache,
Redis, or a specific framework.

## Request sequence

```text
Input validation
      |
Alias -> canonical game
      |
Zone/server validation
      |
Successful-result cache
      |
Provider 1 -> normalize result
      | failure
Provider 2 -> normalize result
      | success
Cache result -> return
```

## Design boundaries

- Provider HTTP logic belongs in `src/Providers`.
- Game/product metadata belongs in `resources/games.php`.
- Framework-specific code belongs in `src/Laravel`.
- Applications may register providers and games at runtime.
- Short-lived credentials and tokens do not belong in source control.
