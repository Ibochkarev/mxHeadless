# mxHeadless

[![CI](https://github.com/Ibochkarev/mxHeadless/actions/workflows/ci.yml/badge.svg)](https://github.com/Ibochkarev/mxHeadless/actions/workflows/ci.yml)

REST API gateway for [MODX Revolution 3](https://modx.com/). It turns resources, pages, elements, contexts, and registered xPDO objects into JSON for Nuxt, Next.js, SvelteKit, mobile apps, and custom clients.

Current release: **1.0.42** (see discovery `data.version`).

License: GPL-2.0-or-later. No feature tiers.

## What it does

- Serves `https://your-site.example/api/v1/...` through an `OnHandleRequest` plugin (prefix configurable).
- Falls back to `assets/components/mxheadless/api.php` when friendly URLs are unavailable (`?route=/v1/health` on nginx without PATH_INFO).
- Keeps a closed registry: only registered objects and fields are exposed. No arbitrary PHP class access.
- Speaks PSR-7/15 middleware (CORS, rate limit, CSRF for sessions, idempotency, HTTP cache, audit, webhooks).
- Ships live OpenAPI and Swagger UI at `/api/v1/docs`.

Full docs (repo): [English](docs/index.md) · [Russian](docs/ru/index.md)

Published on docs.modx.pro: [Русский](https://docs.modx.pro/components/mxheadless/) · [English](https://docs.modx.pro/en/components/mxheadless/)

## Requirements

- MODX Revolution 3.2.3+
- PHP 8.1+
- MySQL or MariaDB (xPDO 3)

## Install

Build and install the transport package:

```bash
cd _build
php build.php
```

Upload the `.transport.zip` in Manager → Packages. The installer registers the namespace, plugin, menus, and system settings.

Friendly URLs should route `/api/v1/*` to MODX `index.php`. Details: [install](docs/installation/install.md), [web server](docs/installation/web-server.md).

Fallback without rewrite:

```text
https://your-site.example/assets/components/mxheadless/api.php?route=/v1/health
```

## Quick start

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
curl -s 'https://your-site.example/api/v1/resources?limit=5&filter[published]=1' | jq
```

Interactive docs (browser):

```text
https://your-site.example/api/v1/docs
```

Raw OpenAPI JSON:

```text
https://your-site.example/api/v1/meta/openapi.json
```

## Response shape

Success responses use a fixed envelope:

```json
{
  "data": {},
  "meta": {
    "total": 100,
    "count": 20,
    "limit": 20,
    "offset": 0,
    "has_more": true
  },
  "links": {
    "self": "/api/v1/resources?limit=20&offset=0",
    "next": "/api/v1/resources?limit=20&offset=20"
  }
}
```

Errors use [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) (`application/problem+json`) with stable `code` values. See [errors](docs/api/errors.md).

## Core endpoints

| Area | Paths | Notes |
|------|-------|-------|
| Discovery | `GET /` | Name, version, CORS snapshot, links |
| Health | `GET /health` | DB check; stays up when kill switch is on |
| Schema | `GET /schema` | Registered objects, fields, relations, flags |
| Meta | `GET /meta/endpoints`, `/meta/openapi`, `/meta/openapi.json` | Live catalog and OpenAPI |
| Docs | `GET /docs` | Swagger UI (`mxheadless_swagger_enabled`) |
| Auth | `POST /auth/token` | OAuth `client_credentials` → `mxt_…` when enabled |
| Resources | `GET/POST /resources`, `GET/PUT/PATCH/DELETE /resources/{id}` | CRUD; soft delete; `?force=1` permanent |
| Pages | `GET /pages/{uri}` | Resolve by MODX URI (`index`, nested paths) |
| Contexts | `GET /contexts`, `/contexts/{key}`, `/contexts/{key}/settings` | Settings allowlist only |
| Elements | `GET /chunks`, `/templates`, `/snippets`, `/tvs`, `/categories`, `/content_types` (+ `/{id}`) | Read-only; auth required |
| Objects | `/objects/{name}` … | Generic CRUD for any registered object |

Public anonymous read covers published resources and pages (subject to ACL). Element and context lists need credentials.

## Query language

Same query params across list endpoints:

```bash
# Filters (shorthand or operators)
curl -s 'https://your-site.example/api/v1/resources?filter[published]=1&limit=10'
curl -s 'https://your-site.example/api/v1/resources?filter[id][gt]=10&filter[pagetitle][like]=%news%'

# Operators: eq, neq, ne, gt, gte, lt, lte, like, in, not_in, null, not_null

# Sort: -id, +pagetitle, id:asc, pagetitle:desc
curl -s 'https://your-site.example/api/v1/resources?sort=-publishedon,id&limit=20'

# Fields, search, pagination (use page OR offset, not both)
curl -s 'https://your-site.example/api/v1/resources?fields=id,pagetitle,uri&q=about&page=2&limit=20'

# Relations and TVs
curl -s 'https://your-site.example/api/v1/resources/12?include=parent,children&tv_fields=*'
curl -s -H "Authorization: Bearer $MXH_KEY" \
  'https://your-site.example/api/v1/templates?include=category&fields=id,templatename,category'
```

Unknown operators, non-filterable fields, too-deep includes, or `page`+`offset` together return `422`. Limits come from system settings (`max_limit`, `max_offset`, `max_fields`, …). Docs: [filtering](docs/api/filtering.md), [pagination](docs/api/pagination.md), [limits](docs/configuration/limits.md).

## Mutations

```bash
# Create
curl -s -X POST https://your-site.example/api/v1/resources \
  -H "Authorization: Bearer $MXH_KEY" \
  -H 'Content-Type: application/json' \
  -H "Idempotency-Key: $(uuidgen)" \
  -d '{"pagetitle":"Hello","alias":"hello","published":0,"template":1}'

# Partial update
curl -s -X PATCH https://your-site.example/api/v1/resources/42 \
  -H "Authorization: Bearer $MXH_KEY" \
  -H 'Content-Type: application/json' \
  -d '{"pagetitle":"Hello world"}'

# Soft delete (second DELETE on the same id → 404)
curl -s -X DELETE https://your-site.example/api/v1/resources/42 \
  -H "Authorization: Bearer $MXH_KEY"

# Permanent delete (also finds soft-deleted rows)
curl -s -X DELETE 'https://your-site.example/api/v1/resources/42?force=1' \
  -H "Authorization: Bearer $MXH_KEY"

# Restore soft-deleted
curl -s -X PATCH 'https://your-site.example/api/v1/resources/42?include_deleted=1' \
  -H "Authorization: Bearer $MXH_KEY" \
  -H 'Content-Type: application/json' \
  -d '{"deleted":0}'
```

Idempotency: same key + same body replays the first response; different body → `409`. See [mutations](docs/api/mutations.md), [idempotency](docs/api/idempotency.md).

## Authentication and authorization

| Identity | How | Typical use |
|----------|-----|-------------|
| Anonymous | No header | Published resources/pages |
| API key | `Authorization: Bearer mxh_…` or `X-API-Key` | CI, server-to-server |
| OAuth token | `Authorization: Bearer mxt_…` from `POST /auth/token` | Short-lived machine access |
| Manager session | MODX cookie + `X-CSRF-Token` on mutations | Same-origin Manager / session apps |

Scopes follow object names (`resources.read`, `chunks.read`, `objects.products.read`, …). MODX ACL still applies. Preview unpublished content needs preview permission. `include_deleted` is denied for anonymous callers.

Create keys/clients in Manager or via CLI:

```bash
php core/components/mxheadless/bin/api-key-create.php \
  --name="CI" --scopes="resources.read,contexts.read"

php core/components/mxheadless/bin/oauth-client-create.php \
  --client-id="build" --name="Build" --scopes="resources.read"
```

Docs: [authentication](docs/api/authentication.md), [API keys](docs/api/api-keys.md), [OAuth](docs/api/auth.md).

## Elements, contexts, media

```bash
# Templates with category relation
curl -s -H "Authorization: Bearer $MXH_KEY" \
  'https://your-site.example/api/v1/templates?limit=20&include=category'

# Context settings (allowlisted keys only)
curl -s -H "Authorization: Bearer $MXH_KEY" \
  https://your-site.example/api/v1/contexts/web/settings

# Page by URI + conditional GET
curl -sI https://your-site.example/api/v1/pages/index
curl -s -H 'If-None-Match: "etag-from-previous-response"' \
  https://your-site.example/api/v1/pages/index
```

TV definitions live under `/tvs`. TV **values** on resources use `tv_fields`. Media paths resolve to absolute URLs (no filesystem paths). See [elements](docs/api/elements.md), [contexts](docs/api/contexts.md), [media](docs/api/media.md), [pages](docs/api/pages.md).

## Extension API

Extras register objects, relations, serializers, and custom endpoints on `OnMxHeadlessRegister` without patching mxHeadless core. Typical shop pattern: catalog reads via registered `products` / `categories`, cart/checkout via native MiniShop3 Web API (`api.php?route=`).

```php
/** @var \MxHeadless\Extension\ExtensionApi $api */
$api->registerObject(
    ObjectDefinition::create('products')
        ->class(\MiniShop3\Model\msProduct::class)
        ->fields(['id', 'pagetitle', 'price', 'article', 'published'])
        ->filterable(['parent', 'price', 'published'])
        ->readable()
);
```

Guides: [extensions](docs/extensions/overview.md), [MiniShop3](docs/extensions/minishop3.md) (CORS alignment and two-client / BFF options).

## Ops features

| Feature | Setting / tool | Role |
|---------|----------------|------|
| Kill switch | `mxheadless_enabled=false` | Only discovery + health stay up |
| CORS | `mxheadless_cors_*` | Browser SPA origins |
| Trusted proxies | `mxheadless_trusted_proxies` | Correct `client_ip` / rate limits behind LB |
| Rate limit | `mxheadless_rate_limit_*` (+ per-key overrides) | `429` with `X-RateLimit-*` |
| HTTP cache | `mxheadless_cache_*` | Public ETag / `304`; auth → `private, no-store` |
| Idempotency | `mxheadless_idempotency_*` | Safe POST/PATCH retries |
| Webhooks | outbox + `bin/webhook-worker.php` | ISR / revalidate payloads, SSRF guards |
| Audit log | `mxheadless_audit_*` + `bin/audit-prune.php` | Request audit retention |
| Debug | `mxheadless_debug` | Extra detail on 500s (keep off in production) |

Checklists: [production](docs/operations/production-checklist.md), [deployment](docs/operations/deployment.md), [webhooks](docs/api/webhooks.md).

## Frontend examples

- [cURL](docs/examples/curl.md)
- [JavaScript](docs/examples/javascript.md) · [TypeScript](docs/examples/typescript.md)
- [Nuxt](docs/examples/nuxt.md) · [Next.js](docs/examples/nextjs.md) · [SvelteKit](docs/examples/sveltekit.md)

## Development

```bash
composer ci
cd _build && php build.php
php scripts/e2e-live.php https://your-site.example "$API_KEY"
```

Testing matrix (automatic + manual): [docs/contributing/testing.md](docs/contributing/testing.md).

## Docs map

| Topic | Link |
|-------|------|
| Online (docs.modx.pro) | [RU](https://docs.modx.pro/components/mxheadless/) · [EN](https://docs.modx.pro/en/components/mxheadless/) |
| Architecture | [docs/architecture.md](docs/architecture.md) |
| Security | [docs/security.md](docs/security.md) |
| OpenAPI (static) | [docs/openapi.yaml](docs/openapi.yaml) |
| Settings | [docs/configuration/settings.md](docs/configuration/settings.md) |
| vs mxApi | [docs/comparison/mxapi.md](docs/comparison/mxapi.md) |
| Contributing | [docs/contributing/development.md](docs/contributing/development.md) |
| Releases | [docs/contributing/releases.md](docs/contributing/releases.md) |

Russian mirror: [docs/ru/index.md](docs/ru/index.md).
