# Тестирование

mxHeadless использует PHPUnit для unit и security tests. Static analysis в CI через Composer scripts.

## Быстрый запуск

```bash
composer install
composer test
```

Эквивалент:

```bash
./vendor/bin/phpunit -c phpunit.xml.dist
```

Полная установка MODX для default suites не нужна. Tests используют stubs в `tests/stubs/`.

## Suites

| Path | Фокус |
|------|-------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Registration, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | HTTP query → ObjectQuery |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist + bound parameters |
| `tests/Security/InjectionTest.php` | Filter/sort injection blocked |
| `tests/Security/ArbitraryClassTest.php` | Unregistered objects rejected |

Добавляйте unit test при изменении query parsing, registry или serializers. Security test — при filters, sorts или registration objects.

## Static analysis

```bash
composer phpstan
composer phpcs
```

PHPCS исправьте до MR. PHPStan level задан в корне репозитория.

## Integration testing

End-to-end на реальном MODX:

1. Symlink `core/components/mxheadless` в MODX 3.
2. `composer install` внутри component directory.
3. Включите plugin и вызовите `/api/v1/health`.
4. Прогоните изменённые routes через curl или фронт.

Новые settings документируйте в [installation/install.md](../installation/install.md) и [settings](../configuration/settings.md).

## OpenAPI sync

При смене routes обновите `docs/openapi.yaml` и страницу API docs. Сверяйтесь с `GET /api/v1/meta/openapi` на dev-сайте.

## См. также

- [Development](development.md)
- [Security review](security.md)
- [Releases](releases.md)
