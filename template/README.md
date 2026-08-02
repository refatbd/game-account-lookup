# Local Web Test Template

This self-contained PHP interface lets maintainers test the package before a GitHub or Packagist release. It mirrors the practical tester style used by the earlier FreeFireInfoSite project: game/UID inputs, API health status, readable result cards, provider diagnostics, raw JSON, copy support, demo mode, per-request cache bypass and local recent history.

## Start it

From the repository root:

```bash
php -S 127.0.0.1:8080 -t template
```

Open `http://127.0.0.1:8080`.

Windows users can run `template/start.bat`; Linux/macOS users can run `./template/start.sh`.

Composer installation is optional for the template while working inside this repository. `template/bootstrap.php` falls back to the local `src/` tree when `vendor/autoload.php` does not exist.

## Modes

- **Live mode:** calls the bundled provider adapters and shows normalized output.
- **Demo mode:** generates a deterministic local nickname without calling any external service. It tests the UI, validation, JSON contract, provider-attempt rendering and browser history.
- **Automatic fallback:** follows registry order and stops after the first success.
- **All providers:** calls every configured provider and shows every response.
- **Single provider:** calls only the explicitly selected provider with no fallback.
- **Bypass cache:** skips both cache read and write for the current request only.

Automatic live lookup invokes the PHP-only Garena provider first for Free Fire,
then GoPay/Codashop, dynamic Codashop, and bundled browser-assisted providers
last. Browser
automation requires Node.js 22+, Chrome or Edge, PHP `proc_open`, and writable
`storage/`. When those are unavailable (as on many shared hosts), it reports a
normal provider failure rather than a fatal error. The browser is visible, uses
a persistent local profile, may pause for interactive verification, and never
confirms a purchase. Executable discovery can be overridden with
`GAME_LOOKUP_NODE_PATH` and `GAME_LOOKUP_CHROME_PATH`.
- **Maintenance handling:** blocks broken live definitions and keeps Demo mode available.
- **Diagnostics:** shows upstream HTTP status, timing, host, Codashop regional profile route and sanitized failure details.

## Endpoints

| Endpoint | Method | Purpose |
|---|---|---|
| `/api/health.php` | GET | PHP/extensions/package health |
| `/api/games.php` | GET | Public registry metadata |
| `/api/check.php` | GET/POST | UID/zone lookup |

Example:

```bash
curl -X POST http://127.0.0.1:8080/api/check.php \
  -d "game=freefire" \
  -d "player_id=4422076728" \
  -d "provider=__all__" \
  -d "bypass_cache=1"
```

`provider` accepts:

| Value | Behavior |
|---|---|
| empty | Automatic fallback |
| `__all__` | Execute every configured provider |
| provider key | Execute only that provider |

When `provider=__all__`, the response contains `summary`, `provider_results`
and `attempts`. Each `provider_results` item is the full normalized response
from that provider, including nickname, code, message, cache state and sanitized diagnostics.


## Lookup HTTP status policy

The template treats provider failures as application results rather than origin
failures. Once validation passes, `/api/check.php` returns HTTP `200` and the
JSON fields `ok` and `code` describe success or failure. This prevents
Cloudflare/custom 5xx handling from replacing provider diagnostics with a
generic `502` page.

For `codashop_dynamic`, inspect `meta.codashop_profile` and `meta.profile_attempts` to see which storefront rejected or accepted the account. A region block is retried on the next configured profile.

Non-active games return `MAINTENANCE_REQUIRED` with their registry status and
notes. Enable Demo mode to test those definitions without a live provider.

## Environment options

| Variable | Default | Meaning |
|---|---:|---|
| `GAME_LOOKUP_TEMPLATE_DEBUG` | `false` | Include package debug metadata |
| `GAME_LOOKUP_TEMPLATE_VERIFY_TLS` | `true` | Verify upstream TLS certificates |
| `GAME_LOOKUP_TEMPLATE_ALLOW_DEMO` | `true` | Allow local demo responses |
| `GAME_LOOKUP_TEMPLATE_DEMO_DEFAULT` | `false` | Check demo mode by default |
| `GAME_LOOKUP_TEMPLATE_TIMEOUT` | `12` | Provider request timeout |
| `GAME_LOOKUP_GARENA_COOKIE` | empty | Optional Shop2Game cookie string when Garena requires DataDome verification |
| `GAME_LOOKUP_GARENA_DATADOME_CLIENT_ID` | empty | Optional matching DataDome client ID; never expose it to frontend JSON |
| `GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY` | empty | Required 64-hex-character AES key for direct Midasbuy lookup |
| `GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV` | `1234567890123456` | Matching 16-byte Midasbuy AES IV |
| `GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION` | `1.0.1` | Matching Midasbuy client-token version |
| `GAME_LOOKUP_MIDASBUY_CTOKEN` | empty | Matching rotating Midasbuy client token |
| `GAME_LOOKUP_TEMPLATE_CONNECT_TIMEOUT` | `5` | Connection timeout |
| `GAME_LOOKUP_TEMPLATE_CACHE_TTL` | `300` | Successful lookup cache lifetime |
| `GAME_LOOKUP_TEMPLATE_RATE_LIMIT` | `60` | Requests per local window |
| `GAME_LOOKUP_TEMPLATE_RATE_WINDOW` | `60` | Window length in seconds |

The `storage/` directory contains only local cache and rate-limit files and is ignored by Git.

## Production warning

This template is a development tool, not a production application. A real public service should use persistent cache, application authentication, trusted proxy handling, stronger rate limiting, monitoring, provider terms review and production-grade error logging.

## Provider audit information

The selected-game panel shows the dated Codashop/GoPay availability states returned by `GameRegistry::list()`. A provider marked `maintenance`, `not-listed`, or `voucher-or-external` is not included in live provider selection. Refresh the catalog with `php tools/audit-provider-catalog.php` before publishing a new release.
