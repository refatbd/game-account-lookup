## Summary

Describe the problem and the focused change.

## Change type

- [ ] Core/API
- [ ] Provider adapter/parser
- [ ] Game definition or alias
- [ ] Laravel/CLI/template
- [ ] Documentation/tests only

## Verification

- [ ] `php tools/generate-supported-games.php`
- [ ] `php tools/lint.php`
- [ ] `php tests/run.php`
- [ ] `php tools/check-generated-docs.php`
- [ ] `php tests/template-smoke.php`
- [ ] Sanitized fixtures/tests cover response-shape changes
- [ ] No cookie, token, private UID, short-lived JWT or provider credential was committed
- [ ] `CHANGELOG.md` was updated when user-visible behavior changed

## Provider changes

State the provider, game codes, region/date tested and whether the change relies on a public/authorized flow. Do not paste sensitive request headers.
