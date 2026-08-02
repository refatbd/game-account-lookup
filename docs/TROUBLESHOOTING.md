# Troubleshooting

This package depends on third-party account-validation flows. A provider failure
is not automatically a failure of your PHP, Nginx or Cloudflare origin.

## The browser shows a Cloudflare 502 page

The v0.3.0 test template returned HTTP `502` when no provider succeeded. Sites
with Cloudflare custom error handling could replace the package JSON with a
Cloudflare `origin_bad_gateway` document.

From v0.4.0, a completed lookup request returns HTTP `200` and carries the
application result in:

```json
{
  "ok": false,
  "code": "ALL_PROVIDERS_FAILED",
  "transport_status": 200,
  "upstream_failure": true,
  "attempts": []
}
```

Validation errors such as an unknown game or missing zone can still use normal
`4xx` status codes. Consumers must inspect `ok` and `code` for lookup outcomes.

## `Topup region blocked for player`

This Codashop message normally means the selected storefront cannot serve that
account region. It does not prove that the UID is invalid.

From v0.5.0, the `codashop_dynamic` adapter:

1. opens the configured regional product page;
2. retains that storefront's cookies;
3. discovers the current product and price-point metadata;
4. performs account confirmation;
5. classifies a region block as `PROVIDER_RESTRICTED`;
6. tries the next configured storefront automatically.

Inspect `meta.codashop_profile`, `meta.profiles_checked` and
`meta.profile_attempts` to see which routes were attempted. See
[`CODASHOP_REGIONAL_ROUTING.md`](CODASHOP_REGIONAL_ROUTING.md) to add or reorder
storefronts for another game.

## Free Fire fails on one storefront

Free Fire automatic mode now tries runtime Codashop profiles for Malaysia,
Philippines and Indonesia before the legacy static Indonesia definition. GoPay
Games remains a separate fallback.

Use **Bypass cache** while testing. A typical useful route looks like:

```text
malaysia-en:PROVIDER_RESTRICTED -> philippines-en:PROVIDER_RESTRICTED -> indonesia-id:SUCCESS
```

If every Codashop profile fails, inspect each profile attempt separately. Do not
change a regional rejection to `INVALID_PLAYER`; add the correct permitted
storefront or update its page slug/metadata parser.

## FC Mobile fails

FC Mobile now prefers runtime Codashop discovery for Bangladesh, Malaysia and
Hong Kong, then falls back to GoPay Games. Use **Bypass cache** and inspect the
Provider diagnostics table.

Useful fields include:

- provider and Codashop profile;
- profile route and normalized result code;
- upstream HTTP status and request duration;
- upstream host and retry-after value;
- Cloudflare ray ID returned by the upstream provider;
- sanitized transport error or response preview.

Typical causes are a changed page slug or product structure, regional blocking,
rate limiting, DNS/TLS failure, provider downtime, or an account that does not
belong to any configured storefront.

## Farlight 84 fails

Farlight 84 is active from v0.5.0 through runtime Codashop metadata discovery.
It tries Malaysia and Hong Kong storefront profiles and does not commit a
short-lived SKU/order token.

If both profiles fail, inspect `profile_attempts`, verify that the current
product pages are still available, update the storefront list when necessary,
and add a sanitized fixture before changing parser logic.

Other games can still legitimately use the registry statuses
`metadata-refresh-required` or `external-provider-required`. Those definitions
return `MAINTENANCE_REQUIRED` in live mode and remain testable in Demo mode.

## `PHP cURL extension is missing or unavailable`

Install or enable `ext-curl` for the PHP runtime serving the template. Confirm
that the web-server PHP version and CLI PHP version are the same:

```bash
php -m | grep -i curl
php --ini
```

Restart PHP-FPM/Apache after enabling the extension.

## Diagnostics are missing

Failure diagnostics are intentionally small and sanitized. Full upstream
responses are not exposed by default. Set `GAME_LOOKUP_TEMPLATE_DEBUG=true`
only in a private development environment and review output before sharing it.
Never publish cookies, authorization headers, dynamic SKU tokens or customer
identifiers.
