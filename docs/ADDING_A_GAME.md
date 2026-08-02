# Adding a game

## 1. Confirm the source

Prefer, in order:

1. An official publisher API.
2. An authorized reseller or top-up API.
3. A documented public validation flow whose use is permitted.

Do not add stolen credentials, bypasses, private mobile-app secrets, or
authentication material captured from another user.

## 2. Capture sanitized evidence

Record:

- Provider name and endpoint.
- HTTP method and content type.
- Required player and zone fields.
- Static product metadata.
- Success and invalid-account response shapes.
- Rate-limit and regional error responses.
- Date of verification.

Remove cookies, device IDs, access tokens, names, and real customer data.

## 3. Add the registry definition

Add one entry to `resources/games.php`. Use a lowercase canonical code and add
common aliases. Set `requiresZone` accurately.

Automatic provider order is normalized for hosting safety: `garena`, `gopaygames`,
`codashop`, other PHP providers, `codashop_dynamic`, then `*_browser` providers.
An explicit per-request `providerOrder` is still followed exactly.

## 4. Avoid fragile metadata

Do not commit a JWT or SKU token when it is session-bound or short-lived.
Instead, add a runtime resolver similar to `CodashopDynamicProvider`, or disable
the definition with `status: metadata-refresh-required`.

## 5. Add tests

Core tests should use a fake provider and sanitized fixtures. Live provider
tests must be opt-in and should never run on every pull request.

## 6. Update documentation

Update the game count or status in `README.md`, then add an entry to
`CHANGELOG.md`.
