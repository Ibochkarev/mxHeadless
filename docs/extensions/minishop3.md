# MiniShop3 integration

[MiniShop3](https://github.com/modx-pro/MiniShop3) e-commerce models integrate with mxHeadless through the Extension API. mxHeadless core has no shop dependency.

## Registered objects

| Public name | Description |
|-------------|-------------|
| `products` | Product resources with price, SKU, options |
| `categories` | Category resources (parent tree) |
| `orders` | Customer orders (protected, admin scope) |
| `order_addresses` | Shipping and billing addresses |
| `product_options` | Option definitions |
| `product_links` | Related / upsell links |

Writable objects require appropriate scopes and MODX permissions. Orders are never public.

## Registration sketch

```php
<?php
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;

/** @var \MxHeadless\Extension\ExtensionApi $api */
$api = $modx->event->params['api'];

$api->registerObject(
    ObjectDefinition::create('products')
        ->setName('products')
        ->class('MiniShop3\\Model\\msProduct')
        ->fields(['id', 'pagetitle', 'alias', 'uri', 'price', 'article', 'parent', 'published'])
        ->filterable(['id', 'parent', 'price', 'published', 'article'])
        ->sorts(['id', 'price', 'pagetitle'])
        ->searchable(['pagetitle', 'article'])
        ->readable()
);

$api->registerObject(
    ObjectDefinition::create('categories')
        ->setName('categories')
        ->class('MiniShop3\\Model\\msCategory')
        ->fields(['id', 'pagetitle', 'alias', 'uri', 'parent'])
        ->filterable(['id', 'parent'])
        ->sorts(['id', 'pagetitle'])
        ->readable()
);

$api->registerRelation('products', RelationDefinition::create('category')
    ->to('categories')
    ->toOne()
    ->foreignKeyField('parent')
    ->fields(['id', 'pagetitle', 'alias'])
);

$api->registerObject(
    ObjectDefinition::create('orders')
        ->setName('orders')
        ->class('MiniShop3\\Model\\msOrder')
        ->fields(['id', 'num', 'status', 'cost', 'createdon', 'user_id'])
        ->filterable(['id', 'status', 'user_id'])
        ->sorts(['id', 'createdon'])
        ->readable()
        ->creatable(false)
        ->hiddenFields(['comment', 'properties'])
        ->protectedFields(['user_id'])
);
```

## Storefront queries

### Category product grid

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[parent][eq]=15&filter[published][eq]=1&sort=price&fields=id,pagetitle,price,article&limit=24'
```

### Product detail with category

```bash
curl -s 'https://example.com/api/v1/objects/products/101?include=category'
```

### Price range filter

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[price][gte]=1000&filter[price][lte]=5000&sort=-id'
```

## Order access

Orders require authenticated identity:

```bash
curl -s https://example.com/api/v1/objects/orders/5001 \
  -H 'Authorization: Bearer mxh_...' \
  -H 'Accept: application/json'
```

Scope: `objects.orders.read`. Users see only orders allowed by MODX ACL and field policy (`user_id` matching).

## Cart and checkout

Cart mutations may use dedicated endpoints registered via `registerEndpoint` (recommended for transactional logic) rather than raw `POST /objects/orders` from the browser.

Pattern:

1. `POST /api/v1/store/cart/items` — custom handler in MiniShop3 Extra
2. Handler uses MODX session + CSRF
3. Emits webhooks on order completion

## TVs and media

Product images often live in TVs or gallery fields. Request TVs explicitly:

```
GET /api/v1/objects/products/101?include=tv&tv_fields=thumb,gallery
```

Media URLs are resolved through MODX Media Sources. Filesystem paths are never returned.

## Webhooks

Typical events:

- `orders.created`
- `orders.updated`
- `products.updated` (search index / cache purge)

## Related

- [Extension overview](overview.md)
- [Authentication](../api/authentication.md)
- [Nuxt example](../examples/nuxt.md)
