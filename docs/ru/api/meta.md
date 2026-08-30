# Meta-каталог

Живой список маршрутов и OpenAPI для текущей установки. Публично, без аутентификации.

Статический [`docs/openapi.yaml`](../openapi.yaml) остаётся контрактом репозитория для CI. Runtime meta — source of truth на боевом сайте (см. [ADR 0007](../decisions/0007-live-openapi.md)).

## Список эндпоинтов

```bash
curl -s https://example.com/api/v1/meta/endpoints | jq
```

Включает core-маршруты и Extra через `registerEndpoint`.

## Живой OpenAPI

```bash
curl -s https://example.com/api/v1/meta/openapi | jq '.data.openapi'
```

Поле `data` — полный документ OpenAPI 3.0.3 из `RouteCollection` и `ObjectRegistry`.

## Сырой OpenAPI JSON

Для Swagger UI и генераторов, которым нужен корневой `openapi` без envelope:

```bash
curl -s https://example.com/api/v1/meta/openapi.json | jq '.openapi'
```

Content-Type: `application/openapi+json`.

## Swagger UI

```bash
open https://example.com/api/v1/docs
```

Интерактивная документация (CDN Swagger UI) грузит `/meta/openapi.json`. Выключатель: настройка `mxheadless_swagger_enabled` (по умолчанию Да). При «Нет» `/docs` отдаёт `404`; сырой OpenAPI остаётся.

## См. также

- [Discovery](discovery.md)
- [Schema](schema.md)
- [Статический OpenAPI](../openapi.yaml)
