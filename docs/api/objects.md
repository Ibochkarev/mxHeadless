# Generic objects API

Registered xPDO objects are exposed at `/api/v1/objects/{name}`. The `{name}` is the public key from `ObjectRegistry`, not the PHP class name.

Core ships with `resources`, read-only `contexts`, and read-only `chunks`. Extras register names such as `products` or `locations`.

## List

```
GET /api/v1/objects/{name}
```

Query parameters match [filtering](filtering.md). Permissions come from the object definition and MODX ACL.

```bash
curl -s 'https://example.com/api/v1/objects/products?limit=20&filter[active][eq]=1'
```

## Get one

```
GET /api/v1/objects/{name}/{id}
```

`{id}` is the primary key defined on the object (string or integer as stored in xPDO).

## Create

```
POST /api/v1/objects/{name}
Content-Type: application/json
```

Requires `creatable` on the definition plus scope and MODX permission. Session auth needs `X-CSRF-Token`.

## Update

```
PUT /api/v1/objects/{name}/{id}
PATCH /api/v1/objects/{name}/{id}
```

Requires `updatable` on the definition.

## Delete

```
DELETE /api/v1/objects/{name}/{id}
```

Requires `deletable` on the definition.

## Security model

Clients cannot call `/objects/modUser` or any unregistered class. Unknown `{name}` returns 404. Fields and filters outside the definition return 422.

Register objects from an Extra:

```php
$api->registerObject(
    ObjectDefinition::create('products')
        ->class(\MiniShop3\Model\msProduct::class)
        ->readable()
        ->fields(['id', 'pagetitle', 'price'])
        ->filterable(['parent', 'published'])
);
```

See [extensions/objects.md](../extensions/objects.md).

## Related

- [Schema](schema.md)
- [Filtering](filtering.md)
- [Mutations](mutations.md)
