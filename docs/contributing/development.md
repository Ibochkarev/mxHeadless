# Development

Guide for contributing to mxHeadless.

## Requirements

- PHP 8.1+
- Composer 2
- MODX Revolution 3.2.3+ (for integration testing)
- Git

## Repository layout

```
core/components/mxheadless/   # Component source (PSR-4: MxHeadless\)
docs/                       # Documentation and OpenAPI
tests/                      # PHPUnit tests (no full MODX required for unit/security)
_build/                     # Transport package builder
```

## Documentation

When you change public API behavior, update `docs/`, `docs/ru/`, and `docs/openapi.yaml`.

Read [docs/WRITING.md](../WRITING.md) for modx-docs + humanizer workflow. Do not re-run `scripts/generate_docs.py` on expanded pages.

## Local setup

```bash
git clone https://github.com/modx-pro/mxHeadless.git
cd mxHeadless
composer install
```

Autoload maps `MxHeadless\` to `core/components/mxheadless/src/` and `MxHeadless\Tests\` to `tests/`.

## Running tests

Unit and security tests use lightweight stubs for `modX` and `xPDOQuery` in `tests/stubs/`. No MODX installation is required.

```bash
composer test
# or
./vendor/bin/phpunit -c phpunit.xml.dist
```

### Test suites

| Path | Focus |
|------|-------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Registration, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | HTTP query → ObjectQuery |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist + bound parameters |
| `tests/Security/InjectionTest.php` | Filter/sort injection blocked |
| `tests/Security/ArbitraryClassTest.php` | Unregistered objects rejected |

### Static analysis

```bash
composer phpstan
composer phpcs
composer ci
```

`composer ci` runs PHPUnit, PHPStan, and PHPCS in one pass (same gates as GitHub Actions).

## Integration testing with MODX

1. Symlink `core/components/mxheadless` into a local MODX 3 install
2. Run `composer install` inside the component
3. Install or enable the mxHeadless plugin
4. Hit `/api/v1/health` and run manual or browser tests

Document new system settings in `docs/installation/install.md`.

## Adding a registered object

1. Define `ObjectDefinition` in your Extra's `OnMxHeadlessRegister` listener
2. Add integration tests or manual cURL checks
3. Update `docs/extensions/` if shipping a first-party integration guide
4. Extend `GET /api/v1/schema` expectations in tests if core bootstrap changes

## Middleware and routes

Core routes live in `Routing/RoutesRegistrar.php`. New core endpoints require:

1. Route registration
2. `RouteDispatcher` handler
3. Service class
4. OpenAPI update in `docs/openapi.yaml`
5. Authorization scope in `Authorizer`

## Coding standards

- `declare(strict_types=1);` in every PHP file
- PSR-12 formatting (phpcs)
- Imports at file top (no inline imports)
- Exhaustive `match` with `default => throw` for enums where applicable
- Typed query objects between HTTP and xPDO (never pass raw arrays to xPDO)

## Documentation

- User docs: `docs/` (English) and `docs/ru/` (Russian)
- Architecture decisions: `docs/decisions/`
- Keep `docs/openapi.yaml` in sync with routes

Run prose through project tone guidelines: direct, no filler, complete sentences.

## Pull requests

- Conventional commits: `feat:`, `fix:`, `docs:`, `test:`, `refactor:`
- Reference GitLab/GitHub issue numbers where applicable
- Include test plan in MR description
- Security-sensitive changes need explicit review

## Release

```bash
cd _build
php build.php
```

Upload the generated transport package via MODX Package Manager. Tag releases SemVer aligned with API version (`/api/v1` → package 1.x).

Breaking API changes require a new major API path (`/api/v2`) and semver major bump.

## Related

- [Architecture](../architecture.md)
- [Security](../security.md)
- [Extension API](../extensions/overview.md)
