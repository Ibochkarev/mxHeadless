# Extension API

Third-party MODX Extras register API objects without modifying mxHeadless core. Registration happens during bootstrap via the `OnMxHeadlessRegister` event.

## Event hook

```php
<?php
/** @var \MODX\Revolution\modX $modx */
switch ($modx->event->name) {
    case 'OnMxHeadlessRegister':
        /** @var \MxHeadless\Extension\ExtensionApi $api */
        $api = $modx->event->params['api'];
        $api->registerObject(
            \MxHeadless\Definition\ObjectDefinition::create('products')
                ->setName('products')
                ->class(\MiniShop3\Model\msProduct::class)
                ->fields(['id', 'pagetitle', 'price', 'article'])
                ->filterable(['id', 'price', 'parent'])
                ->sorts(['id', 'price', 'pagetitle'])
                ->searchable(['pagetitle', 'article'])
                ->readable()
        );
        break;
}
```

After all listeners run, mxHeadless freezes the registry. Late registration throws `RegistryFrozenException`.

For middleware and request/response hooks, see [lifecycle hooks](lifecycle-hooks.md).

## ExtensionApi methods

| Method | Purpose |
|--------|---------|
| `registerObject(ObjectDefinition)` | Expose an xPDO class under a public name |
| `registerRelation(string $object, RelationDefinition)` | Add or update a relation on an existing object |
| `registerEndpoint(name, methods, pattern, callable $handler, ...)` | Custom route with a PHP callable handler |

Planned for a future release: `registerSerializer`, `registerFilter`, `registerPermission`, `registerWebhookEvent`.

## ObjectDefinition

Fluent builder:

```php
ObjectDefinition::create('locations')
    ->setName('locations')
    ->class(YmlLocation::class)
    ->fields(['id', 'title', 'lat', 'lng'])
    ->filterable(['id', 'city_id'])
    ->sorts(['id', 'title'])
    ->readable()
    ->creatable(false)
    ->hiddenFields(['internal_note'])
    ->protectedFields(['owner_id'])
    ->contexts(['web']);
```

Flags:

- `readable`, `creatable`, `updatable`, `deletable`
- `hiddenFields` — never selected or serialized
- `protectedFields` — require field-level permission
- `contexts` — restrict to specific MODX contexts

## Relations

```php
use MxHeadless\Definition\RelationDefinition;

$api->registerRelation('products', RelationDefinition::create('category')
    ->to('categories')
    ->toOne()
    ->foreignKeyField('parent')
    ->fields(['id', 'pagetitle'])
);
```

Types:

- `to_one` — single related object via `getObjectGraph`
- `to_many` — paginated batch load to avoid SQL limit bugs

## Custom endpoints

```php
$api->registerEndpoint(
    name: 'store.locator',
    methods: ['GET'],
    pattern: '/store/locator',
    handler: 'store.locator',
    permission: 'store.read',
    public: true,
);
```

Implement the handler in your Extra and wire it through `RouteDispatcher` or a dedicated service.

## HTTP surface

Registered objects are available at:

```
GET    /api/v1/objects/{name}
GET    /api/v1/objects/{name}/{id}
POST   /api/v1/objects/{name}
PUT    /api/v1/objects/{name}/{id}
PATCH  /api/v1/objects/{name}/{id}
DELETE /api/v1/objects/{name}/{id}
```

Authorization scopes follow the pattern `{name}.{action}` unless overridden.

## Security rules for Extras

1. Register only fields clients should see
2. Keep `filterable` and `sorts` minimal
3. Never expose manager-only classes (`modUser`, processors). Core exposes `contexts` read-only with ACL.
4. Use `hiddenFields` for internal columns
5. Test with the mxHeadless security suite

## Schema discovery

Clients introspect your objects via `GET /api/v1/schema`. Keep field lists stable; additive changes are backward compatible.

## Examples

- [YandexMapsLocator](yandex-maps-locator.md)
- [MiniShop3](minishop3.md)

## Related

- [Architecture](../architecture.md) — ObjectRegistry and query engine
- [Contributing](../contributing/development.md)
