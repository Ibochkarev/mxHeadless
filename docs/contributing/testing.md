# Testing

mxHeadless uses PHPUnit for unit and security tests. Static analysis runs in CI via Composer scripts.

## Quick run

```bash
composer install
composer test
```

Equivalent:

```bash
./vendor/bin/phpunit -c phpunit.xml.dist
```

No full MODX install is required for the default suites. Tests use lightweight stubs in `tests/stubs/`.

## Suites

| Path | Focus |
|------|-------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Registration, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | HTTP query → ObjectQuery |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist + bound parameters |
| `tests/Security/InjectionTest.php` | Filter/sort injection blocked |
| `tests/Security/ArbitraryClassTest.php` | Unregistered objects rejected |

Add a unit test when you change query parsing, registry rules, or serializers. Add a security test when you touch filters, sorts, or object registration.

## Static analysis

```bash
composer phpstan
composer phpcs
```

Fix PHPCS before opening an MR. PHPStan level is configured in the repo root.

## Integration testing

For end-to-end checks against a real MODX site:

1. Symlink `core/components/mxheadless` into a MODX 3 install.
2. Run `composer install` inside the component directory.
3. Enable the plugin and call `/api/v1/health`.
4. Exercise changed routes with curl or your frontend.

Document new settings in [installation/install.md](../installation/install.md) and [settings](../configuration/settings.md).

## OpenAPI sync

When routes change, update `docs/openapi.yaml` and the matching API doc page. Run manual diff against `GET /api/v1/meta/openapi` on a dev site if unsure.

## Related

- [Development](development.md)
- [Security review](security.md)
- [Releases](releases.md)
