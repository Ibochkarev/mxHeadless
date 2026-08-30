# Endpoints

Extension API: `registerEndpoint()`. Register in bootstrap or `OnMxHeadlessRegister`. See [overview](overview.md).

## Basic registration

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

## Metadata for catalog and OpenAPI

Optional arguments feed `GET /meta/endpoints` and `GET /meta/openapi`:

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
    'Subscribe an email address to the newsletter',
    ['Newsletter'],
    [
        new RouteParameter('email', 'query', true, 'Subscriber email', ['type' => 'string', 'format' => 'email']),
    ],
);
```

| Argument | Purpose |
|----------|---------|
| `$description` | OpenAPI `summary` and catalog `description` |
| `$tags` | OpenAPI tags and catalog grouping |
| `$parameters` | Extra OpenAPI parameters (`path`, `query`, `header`) |

Path parameters in `{braces}` are inferred automatically. Declared `RouteParameter` entries override inferred path params when names match.

## Related

- [Meta API](../api/meta.md)
- [ADR 0007: Live OpenAPI](../decisions/0007-live-openapi.md)
