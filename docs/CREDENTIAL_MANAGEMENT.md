# Credential management

This private build requires rotating Garena and Midasbuy values for immediate
direct lookup. They are intentionally retained in exactly one canonical class:

```text
src/Credentials/BundledCredentialProvider.php
```

The design follows the credential-provider pattern used by
`refatbd/free-fire-php-monorepo`:

```text
Provider adapter
      |
      v
ChainCredentialProvider
      |
      +-- EnvironmentCredentialProvider (first)
      +-- BundledCredentialProvider (fallback)
```

This preserves install-and-run behavior while allowing a server to rotate the
complete credential group without editing source.

## Resolution order

1. A complete environment group overrides the bundled group.
2. If the environment group is missing or incomplete, the bundled group is
   used.
3. If neither provider returns a usable group, the adapter reports a normal
   provider configuration failure and automatic fallback continues.

Environment groups are intentionally all-or-nothing. Mixing a token, cookie,
key, or IV from different sessions is a common cause of upstream rejection.

## Environment override groups

Garena:

```dotenv
GAME_LOOKUP_GARENA_COOKIE="COMPLETE_COOKIE_STRING"
GAME_LOOKUP_GARENA_DATADOME_CLIENT_ID="MATCHING_CLIENT_ID"
```

Midasbuy:

```dotenv
GAME_LOOKUP_MIDASBUY_ENCRYPTION_KEY="64_HEX_CHARACTER_KEY"
GAME_LOOKUP_MIDASBUY_ENCRYPTION_IV="16_BYTE_IV_VALUE"
GAME_LOOKUP_MIDASBUY_CTOKEN_VERSION="TOKEN_VERSION"
GAME_LOOKUP_MIDASBUY_CTOKEN="MATCHING_CTOKEN"
```

All variables in a group must be present before that environment provider wins.

## Bundled credential rules

- Keep live bundled values only in `BundledCredentialProvider.php`.
- Do not duplicate them in `resources/games.php`, provider adapters, tests,
  documentation, Laravel config, `.env.example`, frontend code, or fixtures.
- Never include credential values in API responses or diagnostic metadata.
- Redact tokens, cookies, keys, and authorization material before logging.
- Keep this credential-bearing repository private.
- Rotate a complete provider group together and run direct-provider tests with
  cache bypass afterward.

## Rotation workflow

1. Capture a verified working Garena or Midasbuy request using its dedicated
   capture guide.
2. Confirm every value belongs to the same browser/session set.
3. Replace only the matching constant group in
   `BundledCredentialProvider.php`, or set the complete environment override.
4. Restart PHP or clear OPcache.
5. Test the strict provider with `bypassCache: true` and a known-valid ID.
6. Test a second valid ID to confirm dynamic lookup.
7. Run `composer quality`.

Provider-specific details:

- [Garena session capture](GARENA_SESSION_CAPTURE.md)
- [Midasbuy direct session capture](MIDASBUY_DIRECT_SETUP.md)
