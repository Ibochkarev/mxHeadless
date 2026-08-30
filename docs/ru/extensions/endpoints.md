# Endpoints

Extension API: `registerEndpoint()`. Регистрация в bootstrap или `OnMxHeadlessRegister`. См. [overview](overview.md).

## Базовая регистрация

```php
$app->extension()->registerEndpoint(
    'newsletter.subscribe',
    ['POST'],
    '/newsletter/subscribe',
    static fn (ServerRequestInterface $request, array $params): array => [
        'data' => ['subscribed' => true],
    ],
    'newsletter.write',
    false,
);
```

## Метаданные для catalog и OpenAPI

Опциональные аргументы попадают в `GET /meta/endpoints` и `GET /meta/openapi`:

```php
use MxHeadless\Routing\RouteParameter;

$app->extension()->registerEndpoint(
    'newsletter.subscribe',
    ['POST'],
    '/newsletter/subscribe',
    $handler,
    'newsletter.write',
    false,
    [],
    'Подписка email на рассылку',
    ['Newsletter'],
    [
        new RouteParameter('email', 'query', true, 'Email подписчика', ['type' => 'string', 'format' => 'email']),
    ],
);
```

| Аргумент | Назначение |
|----------|------------|
| `$description` | OpenAPI `summary` и catalog `description` |
| `$tags` | OpenAPI tags и группировка в catalog |
| `$parameters` | Доп. параметры OpenAPI (`path`, `query`, `header`) |

Path-параметры в `{фигурных}` выводятся автоматически. `RouteParameter` с тем же именем переопределяет inferred path param.

## См. также

- [Meta API](../api/meta.md)
- [ADR 0007: Live OpenAPI](../../decisions/0007-live-openapi.md)
