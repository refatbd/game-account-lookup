# Testing Guide

## Quality commands

```bash
composer docs:games
composer lint
composer test
composer test:standalone
composer quality
```

## Test layers

### Registry tests

Verify canonical codes, labels, aliases, zone rules, enabled providers, statuses and server maps.

### Orchestration tests

Use fake providers to verify:

- invalid input is rejected before provider calls;
- missing zone returns `ZONE_REQUIRED`;
- providers run in the configured order;
- fallback stops after success;
- all failures normalize to `ALL_PROVIDERS_FAILED`;
- non-active definitions return `MAINTENANCE_REQUIRED` before provider calls;
- provider attempt diagnostics survive orchestration;
- successful values are cached;
- `forget()` invalidates the correct key.

### Provider parser tests

Use `FakeHttpClient` and sanitized fixtures. Cover:

- successful nickname extraction;
- valid JSON with an error payload;
- HTTP non-2xx response;
- empty/non-JSON body;
- missing nickname path;
- optional server extraction;
- dynamic token/SKU parsing where relevant.

Do not require public network access in normal CI.

### Documentation tests

`tools/generate-supported-games.php` creates the README and `docs/SUPPORTED_GAMES.md` tables from the registry. CI should fail when generated docs are stale.

### Template smoke tests

Verify required template files exist, bootstrap works without Composer, registry API metadata is serializable, the file cache can round-trip a `LookupResult`, and provider failures are not mapped to HTTP 502.

## Optional live integration tests

Live tests must be opt-in and should use permitted test identifiers. Never commit personal identifiers, cookies or provider tokens. Suggested environment gate:

```bash
GAME_LOOKUP_LIVE_TESTS=1 vendor/bin/phpunit --group live
```

For each provider/game pair record:

- date and region;
- valid sanitized account behavior;
- invalid account behavior;
- expected nickname path;
- status/response shape, without sensitive headers;
- provider rate-limit observations.

## Release checklist

1. Run all offline quality commands.
2. Start the web template and test Demo mode.
3. Test selected permitted live providers from the target deployment region.
4. Confirm no secrets with repository secret scanning.
5. Review generated supported-game table and statuses.
6. Update `CHANGELOG.md`.
7. Tag a semantic version only after CI passes.
