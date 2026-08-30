# Интеграция MiniShop3

[MiniShop3](https://github.com/modx-pro/MiniShop3) подключается к mxHeadless через Extension API. В core mxHeadless нет зависимости от магазина.

## Зарегистрированные objects

| Public name | Описание |
|-------------|----------|
| `products` | Product resources с price, SKU, options |
| `categories` | Category resources (parent tree) |
| `orders` | Заказы (protected, admin scope) |
| `order_addresses` | Адреса доставки и оплаты |
| `product_options` | Определения options |
| `product_links` | Related / upsell links |

Writable objects требуют scopes и MODX permissions. Orders никогда не public.

## Пример регистрации

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

## Запросы витрины

### Сетка товаров категории

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[parent][eq]=15&filter[published][eq]=1&sort=price&fields=id,pagetitle,price,article&limit=24'
```

### Карточка товара с категорией

```bash
curl -s 'https://example.com/api/v1/objects/products/101?include=category'
```

### Фильтр по цене

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[price][gte]=1000&filter[price][lte]=5000&sort=-id'
```

## Доступ к заказам

Orders требуют authenticated identity:

```bash
curl -s https://example.com/api/v1/objects/orders/5001 \
  -H 'Authorization: Bearer mxh_...' \
  -H 'Accept: application/json'
```

Scope: `objects.orders.read`. Пользователь видит только заказы по MODX ACL и field policy (совпадение `user_id`).

## Корзина и checkout

Cart mutations лучше через dedicated endpoints (`registerEndpoint`), а не raw `POST /objects/orders` из браузера.

Паттерн:

1. `POST /api/v1/store/cart/items` — custom handler в MiniShop3 Extra
2. Handler использует MODX session + CSRF
3. Webhooks при завершении заказа

## TVs и media

Изображения часто в TVs или gallery fields. Запрашивайте TVs явно:

```
GET /api/v1/objects/products/101?include=tv&tv_fields=thumb,gallery
```

Media URLs через MODX Media Sources. Filesystem paths не возвращаются.

## Webhooks

Типичные events:

- `orders.created`
- `orders.updated`
- `products.updated` (search index / cache purge)

## См. также

- [Extension overview](overview.md)
- [Authentication](../api/authentication.md)
- [Nuxt example](../examples/nuxt.md)
