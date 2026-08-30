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

Cart, submit заказа, login покупателя и MS3-токены живут в **нативном MiniShop3 Web API** (`assets/components/minishop3/api.php?route=/api/v1/...`). Не дублируйте их CRUD-ом `/objects/orders` из браузера.

mxHeadless Extension API — для **каталога/чтения** (`products`, `categories`) и admin-чтения заказов. MS3 Web API — для **корзины / checkout / customer token**.

Опционально Extra может `registerEndpoint` под mxHeadless (`/api/v1/store/...`), если нужен один префикс хоста. Обычно проще звать MS3 `api.php` напрямую.

## Сосуществование: mxHeadless + MS3 Web API

Оба используют префикс `/api/v1`, но **разные входы и envelope**. Выравнивайте настройки и клиенты фронта. Роутеры не сливать.

### Entrypoints

| API | Base | Envelope | Auth |
|-----|------|----------|------|
| mxHeadless | `https://site/api/v1/...` (plugin) или `.../mxheadless/api.php?route=/v1/...` | `data` / `meta` / `links`; ошибки = problem+json | API key, OAuth `mxt_`, session Manager |
| MiniShop3 Web | `.../minishop3/api.php?route=/api/v1/...` | `{ success, message, data, code, error_code }` | `ms3_token` / Bearer / cookie |

Pretty URL `/api/v1/cart/...` сначала попадает в **mxHeadless** plugin и даёт 404. MS3 вызывайте только через `api.php?route=` (или proxy rewrite на MS3).

### CORS (только настройки)

SPA в браузере бьёт в оба API. Один и тот же allowlist на обоих пакетах.

| Тема | mxHeadless | MiniShop3 |
|------|------------|-----------|
| Origins | `mxheadless_cors_enabled=true`, `mxheadless_cors_allowed_origins` | `ms3_cors_allowed_origins` (пусто = только same-origin) |
| Credentials (cookies) | `mxheadless_cors_allow_credentials` | В коде MS3 CORS `allow_credentials: true` при заданных origins |
| Заголовки SPA | `Authorization`, `X-API-Key`, `Idempotency-Key`, … | `Authorization`, `MS3TOKEN` |

Пример для Nuxt на `https://app.example.com`:

```text
mxheadless_cors_enabled = true
mxheadless_cors_allowed_origins = https://app.example.com,http://localhost:3000
mxheadless_cors_allow_credentials = false

ms3_cors_allowed_origins = https://app.example.com,http://localhost:3000
```

Если магазину нужен cookie `ms3_token` cross-origin — только явные origins (не `*` с credentials). CMS API key по возможности без credentials.

См. [CORS](../configuration/cors.md).

### Trusted proxies

mxHeadless: `mxheadless_trusted_proxies` = IP балансировщиков ([trusted proxies](../configuration/trusted-proxies.md)).

У MiniShop3 отдельной настройки нет. Rate limit и логи берут IP по своей логике. Один и тот же nginx/LB перед обоими входами (`X-Forwarded-For` sanitized, TLS на proxy). Кривой proxy бьёт fairness у обоих API независимо.

### Документация фронта: две спеки или BFF

**Вариант A — два клиента (проще всего)**

1. CMS → OpenAPI mxHeadless: live `/api/v1/meta/openapi.json` или `docs/openapi.yaml`.
2. Shop → роуты MS3 Web (`config/routes/web.php` / docs MS3). Общего OpenAPI с mxHeadless нет.
3. В Nuxt/Next: `runtimeConfig.cmsBase` и `runtimeConfig.shopBase` (URL `api.php` MS3). Разные мапперы ошибок (problem+json vs `{success:false}`).

**Вариант B — BFF (один DX для SPA)**

Node/Nitro/`server/api`:

- `/bff/cms/*` → mxHeadless
- `/bff/shop/*` → MS3 `api.php?route=`

SPA говорит только с BFF. Оба error shape → один тип на фронте. CORS тогда между browser и BFF (часто same-origin).

**Вариант C — split path на reverse proxy**

Nginx: `/api/v1/resources|pages|contexts|chunks|…` → MODX (mxHeadless), `/api/v1/cart|order|customer|product|…` → MS3 `api.php`. Только с явным allowlist. Имена вроде `categories` столкнутся, если оба отдают их на одном path.

Дефолт для большинства сайтов: **A** (каталог через Extension objects + корзина через MS3 `api.php`) или **B**, если SPA не должен знать два бэкенда.

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
- [CORS](../configuration/cors.md)
- [Trusted proxies](../configuration/trusted-proxies.md)
- [Nuxt example](../examples/nuxt.md)
