# Хуки жизненного цикла запроса

События MODX для расширения HTTP pipeline без форка core.

## События

| Событие | Когда | Параметры |
|---------|-------|-----------|
| `OnMxHeadlessRegisterMiddleware` | До freeze middleware stack | `registrar` (`MiddlewareRegistrar`) |
| `OnMxHeadlessBeforeRequest` | После auth и authorization, до handler | `request`, `identity`, `route` |
| `OnMxHeadlessAfterRequest` | После handler, до emit | `request`, `response`, `identity`, `route` |

Регистрация объектов и маршрутов — [overview](overview.md) (`OnMxHeadlessRegister`).

## Middleware

```php
switch ($modx->event->name) {
    case 'OnMxHeadlessRegisterMiddleware':
        /** @var \MxHeadless\Http\MiddlewareRegistrar $registrar */
        $registrar = $modx->event->params['registrar'];
        $registrar->append(new MyTracingMiddleware());
        break;
}
```

- `prepend()` — внешний слой (выполняется первым на входе).
- `append()` — ближе к route handler.

Порядок по умолчанию: audit → error → routing → auth → rate limit → CSRF → authorization → **lifecycle** → idempotency → cache → dispatch.

## Замена request / response

Через `$modx->event->returned`:

```php
case 'OnMxHeadlessBeforeRequest':
    $request = $modx->event->params['request'];
    $modx->event->returned['request'] = $request->withAttribute('tenant_id', 'acme');
    break;

case 'OnMxHeadlessAfterRequest':
    $response = $modx->event->params['response'];
    $modx->event->returned['response'] = $response->withHeader('X-Custom', '1');
    break;
```

Используйте immutable PSR-7 (`withHeader`, `withAttribute`). Не меняйте body без понимания cache и audit.

## См. также

- [Endpoints](endpoints.md)
- [Roadmap mxApi](../roadmap/mxapi-adoption.md)
