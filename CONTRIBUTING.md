# Contributing

Contributions are welcome.

## Development

```bash
git clone https://github.com/refatbd/game-account-lookup.git
cd game-account-lookup
composer install
composer lint
composer test
```

## Pull requests

- Keep the framework-independent core free of Laravel dependencies.
- Add or update tests.
- Do not add customer data or unrelated credentials. The bundled Garena and
  Midasbuy session set is a deliberate private-repository exception and must be
  rotated only by the owner.
- Explain how a provider change was verified and when.
- Preserve backward compatibility in patch and minor releases.
- Run syntax checks on PHP 8.1.

By submitting a contribution, you agree that it may be distributed under the
MIT License.

## Provider availability changes

A provider availability pull request must update `resources/provider-catalog.php`, include a current official product/catalog URL and verification date, explain whether the flow is direct-ID, maintenance, not-listed, or voucher/external, and add sanitized fixtures when enabling a route. Run `php tools/audit-provider-catalog.php`, regenerate documentation, and do not include real customer identifiers or short-lived tokens.
