# Changelog

All notable changes will be documented in this file.

The format follows Keep a Changelog and the project intends to use Semantic
Versioning.

## [1.0.3] - 2026-08-08

### Added

- Framework-neutral persistent HTTP session support backed by the existing
  `CacheInterface`, including Laravel cache integration and a configurable
  `GAME_LOOKUP_SESSION_TTL`.
- `SessionAwareHttpClientInterface` for providers that can safely reuse a
  verified upstream session across unique player lookups.
- Recovery tests for warm, cold, and expired Garena session flows.

### Changed

- Garena skips its login-page preflight when a verified Shop2Game session is
  available, while automatically restoring the complete cold flow if that
  session becomes stale.
- The shared cURL client reuses its handle within a lookup, preserving HTTP
  connections across Garena's preflight, challenge, and retry requests.
- Domain-scoped cookies and warm-session state can now survive separate package
  instances when a persistent cache implementation is supplied.

### Fixed

- Removed the redundant `curl_close()` call that is deprecated in PHP 8.5.
- Kept stale-session recovery and provider fallback intact while reducing
  first-time latency for later unique Free Fire UIDs.

## [1.0.2]

### Added

- Centralized Garena and Midasbuy credentials behind bundled, environment, and
  chained credential providers, with environment-first resolution and a single
  canonical bundled credential class.
- Domain-scoped in-memory cookies in the shared HTTP client.
- Login-page preflight and one rotated-cookie retry for the direct Garena and
  Midasbuy providers.
- Sanitized `GAME_LOOKUP_GARENA_USER_AGENT` and
  `GAME_LOOKUP_MIDASBUY_USER_AGENT` environment overrides with compatible
  fallbacks.

### Changed

- Garena now uses server-issued Shop2Game cookies by default instead of a
  bundled static DataDome cookie/client ID.
- Midasbuy now preserves server-issued cookies around its existing encrypted
  `getCharac` request.

### Security

- Removed committed short-lived Garena cookies and DataDome client ID values.
- Prevented cookies captured for one upstream host from being sent to another
  provider domain.

## [0.7.0] - 2026-08-01

### Added

- Explicit Genshin Impact `en-us` storefront metadata overrides for the current Nuxt 3 flow.
- Browser-assisted Codashop and Midasbuy providers with persistent local Chrome/Edge profiles and no purchase submission.
- Direct GoPay Games routes for Free Fire MAX, Honkai Impact 3, and Metal Slug: Awakening after storefront/account-code verification.
- Direct Codashop routes for AU2 Mobile, Free Fire MAX, Honkai Impact 3, Magic Chess: Go Go, and Metal Slug: Awakening.
- Canonical server values in generated supported-game documentation.

### Changed

- Audited Codashop across its global country index and sitemap instead of assuming one region represents global availability.
- Updated Ragnarok M to the current `ragnarok-m-eternal-love-big-cat-coin` product route and removed obsolete storefront fallbacks.
- Reclassified Honor of Kings Codashop as voucher/external because it delivers redeem codes instead of validating Player IDs.
- Reclassified current GoPay `NOVERIFY` products as voucher/external, including Genshin Impact, Honkai: Star Rail, Zenless Zone Zero, Football Master 2, MU Origin 2, Onmyoji Arena, Point Blank, and VALORANT.
- Updated Call of Duty Mobile, Mobile Legends, and PUBG Mobile to their current GoPay product slugs; PUBG Mobile now uses `PUBGM`.
- Marked Aether Gazer, Asphalt 9, BarbarQ, and EOS RED unavailable after their former regional pages disappeared from the global Codashop catalog.

### Fixed

- Fixed a `TypeError` in `CodashopMetadataResolver` when flattened JSON arrays contain integer keys.
- Hydrated Nuxt/devalue storefront payloads before selecting Codashop metadata.
- Parsed escaped GoPay RSC product codes while excluding `NOVERIFY` voucher-only forms.
- Prevented CLI `--debug` from being interpreted as a zone/server argument.
- Displayed canonical server values such as `os_asia` instead of internal normalized alias keys.

## [0.6.0] - 2026-07-31

### Added

- Dated, machine-readable Codashop and GoPay availability catalog for all 50 game definitions.
- Dynamic regional Codashop product-page discovery for every currently verified direct-ID Codashop title.
- GoPay public product-page preflight and Next.js/RSC product-code discovery.
- Provider states for available, maintenance, not-listed, and voucher/external products.
- `tools/audit-provider-catalog.php` with text, JSON, and public-page live-check modes.
- Complete provider evidence matrix in `docs/PROVIDER_AVAILABILITY.md` and template availability display.

### Changed

- Legacy fixed Codashop price-point metadata is now a last-resort fallback rather than the primary route.
- Love and Deepspace and Pixel Gun 3D are active again through current dynamic Codashop discovery.
- Genshin Impact, Honkai: Star Rail, and Zenless Zone Zero GoPay routes are disabled while their official pages report maintenance; Codashop routes remain available.
- Badlanders, Super Mecha Champions, and War Planet Online are marked `provider-unavailable` because no current direct provider listing was verified.
- PUBG Mobile Codashop is classified as voucher/external; GoPay remains the bundled direct-ID route.
- GoPay HTTP 4xx player errors are normalized separately from transport failures.

## [0.5.0] - 2026-07-31

### Added

- Region-aware Codashop storefront profiles with ordered retry.
- Runtime product-page metadata resolver for JSON, Next.js hydration, React
  Server Component chunks and inline product objects.
- In-memory storefront cookie continuity across page, token and confirmation
  requests, with cookie reset between profiles.
- Codashop profile diagnostics: selected profile, profiles checked and the
  normalized route for every profile attempt.
- Regional routing documentation and maintainer configuration examples.
- Regression fixtures for coherent price-point selection, regional retry and
  region-block classification.

### Changed

- Free Fire now prefers dynamic Malaysia, Philippines and Indonesia Codashop
  profiles, followed by GoPay and the legacy static Indonesia route.
- FC Mobile now prefers dynamic Codashop Bangladesh, Malaysia and Hong Kong
  profiles before GoPay Games.
- Farlight 84 is active through dynamic Codashop Malaysia/Hong Kong discovery
  instead of a metadata-maintenance placeholder.
- The tester displays the Codashop storefront profile and route in provider
  diagnostics.

### Fixed

- `Topup region blocked for player` is classified as `PROVIDER_RESTRICTED` and
  triggers the next storefront instead of being reported as `INVALID_PLAYER`.
- Dynamic metadata keeps voucher ID, price and related fields from one coherent
  price-point object.
- Short-lived storefront cookies and tokens are not committed to the registry.

## [0.4.0] - 2026-07-31

### Added

- Cloudflare-safe provider diagnostics with upstream HTTP status, request
  duration, host, retry information, ray ID and sanitized response preview.
- `MAINTENANCE_REQUIRED` result code for non-active game definitions.
- Clear maintenance-state handling in the web tester, while retaining Demo mode.
- Provider diagnostic columns and maintenance notes in the browser template.
- Troubleshooting documentation for FC Mobile, Farlight 84, cURL and Cloudflare.

### Changed

- Completed provider lookups now use HTTP 200 in the development template even
  when the application result is unsuccessful; clients inspect `ok` and `code`.
- Disabled providers are excluded from template execution choices.
- Provider-attempt records now retain nickname, cache state and sanitized meta.
- FC Mobile documentation now makes its single-provider dependency explicit.

### Fixed

- Cloudflare/custom error pages can no longer replace useful provider failure
  JSON with a generic origin `502` response.
- Maintenance-only games no longer attempt disabled or incomplete providers.

## [0.3.0] - 2026-07-28

### Added

- Temporary per-request cache bypass in the core and web tester.
- All-provider comparison mode with complete normalized responses.
- Strict single-provider execution in the tester.
- Provider response table with nickname, source, result code and message.

### Changed

- Cache keys are now scoped to provider order, preventing a selected provider
  from receiving another provider's cached result.
- Expanded README and developer documentation for cache and provider modes.

## [0.2.0] - 2026-07-28

### Added

- Generated supported-games documentation and registry table tooling.
- Local browser tester, API endpoints, demo mode and browser history.
- Developer guide, API reference, testing guide and GitHub issue templates.

## [0.1.0] - 2026-07-28

### Added

- Framework-independent PHP 8.1+ core.
- Combined data-driven registry with 50 game definitions.
- GoPay Games provider.
- Codashop classic provider.
- Runtime Codashop dynamic-token provider.
- Provider fallback and normalized result codes.
- Cache abstraction and in-memory implementation.
- Runtime custom provider and game registration.
- Laravel auto-discovery, facade, configuration, and cache adapter.
- CLI, examples, tests, GitHub Actions, security and maintenance documentation.

### Security

- Removed stale hard-coded short-lived SKU/JWT tokens from default definitions.
- Kept legacy insecure HTTP-only provider integration disabled by default.
