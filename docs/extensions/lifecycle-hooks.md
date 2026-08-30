# Request lifecycle hooks

MODX events for extending the HTTP pipeline without forking mxHeadless core.

## Events

| Event | When | Params |
|-------|------|--------|
| `OnMxHeadlessRegisterMiddleware` | Before the middleware stack is frozen | `registrar` (`MiddlewareRegistrar`) |
| `OnMxHeadlessBeforeRequest` | After auth and authorization, before route handler | `request`, `identity`, `route` |
| `OnMxHeadlessAfterRequest` | After route handler, before response emit | `request`, `response`, `identity`, `route` |

`OnMxHeadlessRegister` (object/route registration) is documented in [overview](overview.md).

## Register middleware

```php
switch ($modx->event->name) {
    case 'OnMxHeadlessRegisterMiddleware':
        /** @var \MxHeadless\Http\MiddlewareRegistrar $registrar */
        $registrar = $modx->event->params['registrar'];
        $registrar->append(new MyTracingMiddleware());
        break;
}
```

- `prepend()` adds outer middleware (runs first on the way in).
- `append()` adds inner middleware (closer to the route handler).

Default order matches [architecture](../architecture.md): audit → error handling → routing → auth → rate limit → CSRF → authorization → **lifecycle** → idempotency → cache → dispatch.

## Mutate request or response

Plugins may set `$modx->event->returned`:

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

Use PSR-7 immutable helpers (`withHeader`, `withAttribute`). Do not mutate bodies unless you understand cache and audit implications.

## Related

- [Endpoints](endpoints.md)
- [mxApi adoption roadmap](../roadmap/mxapi-adoption.md)
