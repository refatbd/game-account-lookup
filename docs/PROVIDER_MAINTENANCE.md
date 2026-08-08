# Provider Maintenance

Third-party validation flows are unstable integration points. Treat each
provider as a replaceable adapter and keep application code dependent only on
the normalized package contract.

## Routine verification

For every active game/provider pair, periodically verify:

1. one permitted valid player ID;
2. one invalid player ID;
3. required zone/server behavior;
4. nickname extraction path;
5. HTTP and JSON response shape;
6. regional restrictions and rate-limit behavior;
7. cache and fallback behavior.

Record the verification date and region in a pull request or issue. Never add
real customer identifiers or authentication material to fixtures.

## Codashop regional runtime discovery

Prefer `codashop_dynamic` when product metadata or regional routing changes over
time. A compact definition uses a shared page slug and ordered storefronts:

```php
'codashop_dynamic' => [
    'pageSlug' => 'example-game',
    'storefronts' => [
        ['name' => 'bangladesh-en', 'localePath' => 'en-bd'],
        ['name' => 'malaysia-en', 'localePath' => 'en-my'],
    ],
    'nicknamePaths' => [
        'confirmationFields.username',
        'confirmationFields.roles.0.role',
    ],
],
```

The adapter opens each storefront page, retains its session cookies, discovers
current metadata, and performs account confirmation. A response such as
`Topup region blocked for player` becomes `PROVIDER_RESTRICTED`, and the next
profile is tried. It must not be treated as proof that the UID is invalid.

Use explicit `profiles` instead of `storefronts` when regions require different
page URLs, form fields or stable overrides. Full configuration and testing
instructions are in
[`CODASHOP_REGIONAL_ROUTING.md`](CODASHOP_REGIONAL_ROUTING.md).

## Stable versus short-lived values

Reasonable registry values include:

- public product page slug;
- locale/storefront path;
- stable provider game code;
- expected nickname JSON paths;
- stable zone/server mappings.

For a public repository, do not commit:

- session cookies;
- authorization credentials;
- page-lock or order-token JWTs;
- customer/account data;
- short-lived dynamic SKU tokens.

Garena uses a runtime-managed, domain-scoped cookie session and does not bundle
a short-lived DataDome cookie or client ID. Midasbuy's request-encryption values
remain centralized in the credential provider; override them through environment
variables when the official integration rotates them.

When a current page requires dynamic values, discover them at runtime or mark
the definition for maintenance rather than committing a value that will expire.

## Response fixtures

Store only sanitized fixtures. Include success, invalid account, region
restricted, rate limited and malformed-response examples when applicable.
Provider tests should verify both parsing and classification, especially that a
regional restriction is not normalized as `INVALID_PLAYER`.

## Maintenance states

Set a definition to `metadata-refresh-required`, `external-provider-required` or
another non-active status when no bundled route is safe and verified. The live
template returns `MAINTENANCE_REQUIRED`; Demo mode remains available.

Before restoring `active`, add the maintained provider configuration and
regression tests. See [`TROUBLESHOOTING.md`](TROUBLESHOOTING.md) for common
transport, Cloudflare and game-specific examples.

## Catalog-first maintenance workflow

The default registry is now controlled by the dated provider catalog. Use this
workflow when a product disappears, enters maintenance, changes slug, or moves
between voucher and direct-ID checkout:

```bash
php tools/audit-provider-catalog.php
php tools/audit-provider-catalog.php --json > provider-audit.json
php tools/audit-provider-catalog.php --live
```

Then:

1. update only the affected entry in `resources/provider-catalog.php`;
2. retain a current official product/catalog URL and `verifiedAt` date;
3. use `available`, `maintenance`, `not-listed`, or `voucher-or-external`;
4. update parser hints in `resources/games.php` only when input/response rules changed;
5. add sanitized response fixtures and regression tests;
6. run `composer docs:games` and the complete quality suite.

A current catalog listing is evidence that a product exists, not proof that its
nickname-confirmation response is unchanged. Validate the account-check flow
with permitted test data before releasing it as active.
