# Разработка

Гайд для контрибьюторов mxHeadless.

## Требования

- PHP 8.1+
- Composer 2
- MODX Revolution 3.2.3+ (для интеграционных проверок)
- Git

## Структура репозитория

```
core/components/mxheadless/   # Исходники (PSR-4: MxHeadless\)
docs/                       # Документация и OpenAPI
tests/                      # PHPUnit (unit/security без полного MODX)
_build/                     # Сборка transport-пакета
```

## Документация

При изменении публичного API обновляйте `docs/`, `docs/ru/` и `docs/openapi.yaml`.

См. [docs/WRITING.md](../WRITING.md) (modx-docs + humanizer). Не запускайте `scripts/generate_docs.py` поверх уже развёрнутых страниц.

## Локальный setup

```bash
git clone https://github.com/modx-pro/mxHeadless.git
cd mxHeadless
composer install
```

Autoload: `MxHeadless\` → `core/components/mxheadless/src/`, `MxHeadless\Tests\` → `tests/`.

## Тесты

Unit и security-тесты используют stubs `modX` / `xPDOQuery` в `tests/stubs/`. Полный MODX не нужен.

```bash
composer test
# или
./vendor/bin/phpunit -c phpunit.xml.dist
```

### Наборы

| Путь | Фокус |
|------|-------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Регистрация, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | HTTP query → ObjectQuery |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist + bindings |
| `tests/Security/InjectionTest.php` | Injection в filter/sort |
| `tests/Security/ArbitraryClassTest.php` | Незарегистрированные объекты |

### Статический анализ

```bash
composer phpstan
composer phpcs
```

## Интеграция с MODX

1. Симлинк `core/components/mxheadless` в локальный MODX 3
2. `composer install` внутри компонента
3. Включите плагин mxHeadless
4. Проверьте `/api/v1/health` и ручные сценарии

Новые system settings документируйте в `docs/installation/install.md` и RU-близнеце.

## Новый зарегистрированный объект

1. `ObjectDefinition` в listener `OnMxHeadlessRegister` Extra
2. Интеграционные или cURL-проверки
3. Обновите `docs/extensions/`, если это first-party гайд
4. При изменении core bootstrap поправьте ожидания `GET /api/v1/schema`

## Middleware и маршруты

Core-маршруты: `Routing/RoutesRegistrar.php`. Новый endpoint требует:

1. Регистрацию маршрута
2. Handler в `RouteDispatcher`
3. Сервисный класс
4. Обновление `docs/openapi.yaml`
5. Scope в `Authorizer`

## Coding standards

- `declare(strict_types=1);` в каждом PHP-файле
- PSR-12 (phpcs)
- Imports только вверху файла
- Исчерпывающий `match` с `default => throw` для enum, где уместно
- Типизированные query-объекты между HTTP и xPDO (без сырых массивов в xPDO)

## Документация

- Пользовательская: `docs/` (EN) и `docs/ru/` (RU)
- ADR: `docs/decisions/`
- Держите `docs/openapi.yaml` в синхроне с routes

Проза: прямая, без filler, полные предложения.

## Pull requests

- Conventional commits: `feat:`, `fix:`, `docs:`, `test:`, `refactor:`
- Ссылки на issue GitLab/GitHub
- Test plan в описании MR
- Security-sensitive изменения нуждаются в отдельном ревью

## Релиз

Подробно: [Релизы](releases.md) — файлы версии, автоматический GitHub Release, чеклист maintainer.

Локальная сборка:

```bash
cd _build
php build.php
```

Breaking API: новый major path (`/api/v2`) и major bump пакета.

## См. также

- [Архитектура](../architecture.md)
- [Безопасность](../security.md)
- [Extension API](../extensions/overview.md)
