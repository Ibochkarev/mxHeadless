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

Cart, order submit, customer login, and MS3 tokens belong to the **native MiniShop3 Web API** (`assets/components/minishop3/api.php?route=/api/v1/...`). Do not reinvent them as CRUD on `/objects/orders` from the browser.

Use mxHeadless Extension API for **catalog/read** (`products`, `categories`) and admin-style order reads. Use MS3 Web API for **session cart / checkout / customer token**.

Optional: a thin Extra can also `registerEndpoint` under mxHeadless (`/api/v1/store/...`) if you want one host prefix. Prefer calling MS3 `api.php` directly unless you need a single gateway.

## Coexistence: mxHeadless + MS3 Web API

Both products use the path prefix `/api/v1`, but **different entrypoints and envelopes**. Align settings and frontend clients. Do not merge routers.

### Entrypoints

| API | Base | Envelope | Auth |
|-----|------|----------|------|
| mxHeadless | `https://site/api/v1/...` (plugin) or `.../mxheadless/api.php?route=/v1/...` | `data` / `meta` / `links`; errors = problem+json | API key, OAuth `mxt_`, Manager session |
| MiniShop3 Web | `.../minishop3/api.php?route=/api/v1/...` | `{ success, message, data, code, error_code }` | `ms3_token` / Bearer / cookie |

Pretty URL `/api/v1/cart/...` hits the **mxHeadless** plugin first and returns 404. Call MS3 only through its `api.php?route=` URL (or proxy rewrite that targets MS3).

### CORS (settings only)

Browser SPA calls both APIs. Mirror the same allowlist on both packages.

| Concern | mxHeadless | MiniShop3 |
|---------|------------|-----------|
| Enable / origins | `mxheadless.cors.enabled=true`, `mxheadless.cors.allowed_origins` | `ms3_cors_allowed_origins` (empty = same-origin only) |
| Credentials (cookies) | `mxheadless.cors.allow_credentials` | MS3 CORS uses `allow_credentials: true` in code when origins are set |
| Headers the SPA sends | Include `Authorization`, `X-API-Key`, `Idempotency-Key`, … | Include `Authorization`, `MS3TOKEN` |

Example for Nuxt on `https://app.example.com`:

```text
mxheadless.cors.enabled = true
mxheadless.cors.allowed_origins = https://app.example.com,http://localhost:3000
mxheadless.cors.allow_credentials = false

ms3_cors_allowed_origins = https://app.example.com,http://localhost:3000
```

If the shop needs cookie `ms3_token` cross-origin, list exact origins (never `*` with credentials). Keep CMS API key calls credential-less when possible.

See [CORS](../configuration/cors.md).

### Trusted proxies

mxHeadless: set `mxheadless.trusted_proxies` to balancer IPs so rate limit / `client_ip` use `X-Forwarded-For` correctly ([trusted proxies](../configuration/trusted-proxies.md)).

MiniShop3 does not share that setting. Rate limit and logging use its own IP resolution. Put the **same nginx/LB config** in front of both entrypoints (`X-Forwarded-For` sanitized, TLS terminated). Wrong proxy config breaks fairness on both APIs independently.

### Frontend docs: two specs or one BFF

**Option A — two clients (simplest)**

1. CMS client → mxHeadless OpenAPI: live `/api/v1/meta/openapi.json` or repo `docs/openapi.yaml`.
2. Shop client → MS3 Web routes (`config/routes/web.php` / MS3 docs). No shared OpenAPI with mxHeadless today.
3. In Nuxt/Next: `runtimeConfig.cmsBase` and `runtimeConfig.shopBase` (MS3 `api.php` URL). Separate error mappers (problem+json vs `{success:false}`).

**Option B — BFF (one DX for the SPA)**

Node/Nitro/`server/api` proxies:

- `/bff/cms/*` → mxHeadless
- `/bff/shop/*` → MS3 `api.php?route=`

SPA talks only to the BFF. Map both error shapes to one frontend type. CORS then matters only between browser and BFF (often same origin).

**Option C — reverse proxy path split**

Nginx routes `/api/v1/resources|pages|contexts|chunks|…` to MODX (mxHeadless) and `/api/v1/cart|order|customer|product|…` to MS3 `api.php`. Only do this with an explicit allowlist. Overlap names (`categories`) collide if both expose them under the same path.

Recommended default for most sites: **Option A** (catalog via mxHeadless Extension objects + cart via MS3 `api.php`), or **B** if the SPA must not know two backends.

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
- [CORS](../configuration/cors.md)
- [Trusted proxies](../configuration/trusted-proxies.md)
- [Nuxt example](../examples/nuxt.md)
