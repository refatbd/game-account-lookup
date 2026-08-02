# Codashop Regional Routing

Codashop account confirmation is storefront-specific. A valid player ID can be
accepted on one regional storefront and rejected by another with a message such
as `Topup region blocked for player`. That response is a routing signal; it must
not be normalized as `INVALID_PLAYER` until all suitable storefronts have been
tried.

## Request lifecycle

```text
regional product page
        |
        +-- retain storefront cookies
        +-- discover current product/price-point metadata
        +-- create order token when required
        +-- call account confirmation endpoint
        +-- success: return nickname
        +-- region restricted: try the next storefront
        +-- other failure: record diagnostics and continue/fail
```

The bundled `codashop_dynamic` adapter clears cookies before each profile, then
keeps cookies between that profile's page, token and confirmation requests.
Short-lived cookies or tokens are never stored in the registry.

## Compact regional configuration

Use a common page slug and list only the storefronts that are relevant to the
game:

```php
'codashop_dynamic' => [
    'pageSlug' => 'free-fire',
    'voucherTypeName' => 'FREEFIRE',
    'storefronts' => [
        [
            'name' => 'malaysia-en',
            'localePath' => 'en-my',
        ],
        [
            'name' => 'philippines-en',
            'localePath' => 'en-ph',
            'pageSlug' => 'garena-free-fire-ph',
        ],
        [
            'name' => 'indonesia-id',
            'localePath' => 'id-id',
        ],
    ],
    'nicknamePaths' => [
        'confirmationFields.roles.0.role',
        'confirmationFields.username',
        'confirmationFields.nickname',
    ],
],
```

The adapter builds each page URL as:

```text
https://www.codashop.com/{localePath}/{pageSlug}
```

A storefront can override `pageSlug`, `pageUrl`, language, stable form fields,
or a known endpoint when a game needs a special route.

## Explicit profile configuration

Use `profiles` when each route needs substantially different metadata:

```php
'codashop_dynamic' => [
    'profiles' => [
        [
            'name' => 'region-a',
            'pageUrl' => 'https://www.codashop.com/en-aa/example-game',
            'voucherTypeName' => 'EXAMPLE_GAME',
            'nicknamePaths' => ['confirmationFields.username'],
        ],
        [
            'name' => 'region-b',
            'pageUrl' => 'https://www.codashop.com/en-bb/example-game',
            'form' => [
                'country' => 'BB',
            ],
        ],
    ],
],
```

Common provider values are merged into every profile. Profile values take
precedence.

## Runtime metadata discovery

`CodashopMetadataResolver` reads current server-rendered JSON, Next.js hydration
state, React Server Component chunks and inline product objects. It looks for:

- page-lock and order-token metadata;
- product path and SKU ID;
- payment channel and white-label IDs;
- one coherent voucher price-point object;
- voucher type, price and variable-price fields;
- current confirmation/token endpoints.

Price-point fields are selected from one coherent product object to avoid
combining an ID from one package with a price from another package.

Stable overrides may be supplied in a profile, but do not commit session
cookies, authorization headers, customer data, page-lock JWTs or short-lived
order tokens.

## Result diagnostics

Results may include:

```json
{
  "codashop_profile": "malaysia-en",
  "profiles_checked": 2,
  "profile_attempts": [
    {
      "profile": "malaysia-en",
      "code": "PROVIDER_RESTRICTED"
    },
    {
      "profile": "indonesia-id",
      "code": "SUCCESS"
    }
  ]
}
```

The web tester displays the selected profile and a compact route such as:

```text
malaysia-en:PROVIDER_RESTRICTED -> indonesia-id:SUCCESS
```

## Add another game

1. Confirm the official/current product page for each intended storefront.
2. Add `pageSlug`, ordered `storefronts` and expected nickname paths.
3. Put the most likely customer region first to reduce latency.
4. Test one permitted valid ID, one invalid ID and one region-restricted case.
5. Confirm cookies are retained only inside a profile and reset before the next.
6. Add sanitized HTML/JSON fixtures and parser/provider regression tests.
7. Run `composer docs:games`, the standalone tests and the template smoke test.

A registry entry is not a permanent uptime promise. Upstream storefront markup,
anti-abuse rules and product availability may change. Use this adapter
server-side, rate-limit it, cache successful lookups, and follow the provider's
terms.
