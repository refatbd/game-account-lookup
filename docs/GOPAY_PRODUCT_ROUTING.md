# GoPay Product Routing

The GoPay Games adapter supports current product-page preflight and runtime code
discovery. This reduces dependence on copied internal product codes while
keeping stable codes as fallback.

## Configuration

```php
'gopaygames' => [
    'enabled' => true,
    'availabilityStatus' => 'available',
    'verifiedAt' => '2026-08-01',
    'productPage' => 'https://gopay.co.id/games/honkai-impact-3',
    'code' => 'HONKAI_IMPACT',
],
```

Known codes may use `code`; uncertain renamed products can use
`codeCandidates`. The page resolver checks `__NEXT_DATA__`, embedded JSON and
common RSC/serialized code keys. Storefront metadata wins over fallbacks.

Current GoPay RSC pages may expose `code: "NOVERIFY"`. That value means the
storefront accepts manually entered top-up data but does not provide the direct
nickname-validation contract used by this package. The resolver intentionally
ignores `NOVERIFY`, and the catalog must classify such products as
`voucher-or-external`, not `available`.

## Request sequence

```text
GET official GoPay product page
    ├─ maintenance notice → PROVIDER_MAINTENANCE
    ├─ code discovered → use discovered code
    └─ page unavailable + stable code → continue with warning

POST /games/v1/order/user-account
    ├─ nickname → SUCCESS
    ├─ unknown user/player → INVALID_PLAYER
    ├─ unknown product/code → try next candidate
    ├─ 429 → RATE_LIMITED
    └─ transport/5xx → NETWORK_ERROR
```

The adapter does not treat every HTTP 404 as a network outage. A structured
`Unknown User` response is an account result, while an unknown product/code can
advance to another configured code candidate.

## Maintenance state

When the official product page publicly reports maintenance, set the catalog
entry to:

```php
'status' => 'maintenance',
```

`ProviderCatalog` disables that route so automatic fallback can use another
current provider. Do not leave a maintenance route enabled merely because the
page URL still exists.

## Adding a current GoPay title

1. Verify its official GoPay product page.
2. Confirm the RSC product metadata exposes a validation code other than
   `NOVERIFY`; an account input by itself is not sufficient.
3. Exercise the account endpoint with permitted valid and invalid test IDs.
4. Add `productPage`, a known `code` or conservative `codeCandidates`, and a
   verification date to `resources/provider-catalog.php`.
5. Add success, invalid-player, maintenance and changed-code fixtures.
6. Verify zone/server requirements in `resources/games.php`.
7. Regenerate documentation.

Never commit session cookies, private headers, customer IDs or purchase data.
