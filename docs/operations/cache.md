# Cache

mxHeadless caches anonymous GET responses in the MODX cache manager. Mutations bump tag versions so stale JSON drops out without scanning every key.

## Settings

| Key | Default | Role |
|-----|---------|------|
| `mxheadless.cache.enabled` | `true` | Shared cache for anonymous GET |
| `mxheadless.cache_ttl` | `300` | `max-age` and entry TTL (seconds) |

Authenticated requests, API keys, and preview mode always get `Cache-Control: private, no-store`. See [HTTP caching](../api/http-caching.md).

## How keys are built

`HttpCacheMiddleware` hashes the request path, sorted query string, context, and tag versions:

- `object:{name}` — set from route metadata (e.g. `resources`)
- `context:{key}` — from `X-Context`, `?context=`, or current context

Tag versions live under the `mxheadless/tags` cache namespace. When a version increments, existing response keys miss and rebuild on the next GET.

## Invalidation on mutations

After `POST`, `PUT`, `PATCH`, or `DELETE`, `MutationHooks` runs:

1. `invalidateTag('object:' . $name)` — e.g. `object:resources`
2. `invalidateTag('context:' . $contextKey)` — e.g. `context:web`

That covers list and detail views for the object without flushing the whole cache partition.

## ETag and 304

Public GET responses include an `ETag` (SHA-256 of the body). Clients send `If-None-Match` to receive `304 Not Modified`. HEAD shares the same representation as GET.

## CDN and edge

You can put a CDN in front of anonymous GET routes when:

- Responses are truly public (no session cookie, no bearer token)
- `Cache-Control: public, max-age=N` matches your purge strategy
- You handle frontend cache separately via webhook `meta.revalidate` tags ([ISR revalidation](isr-revalidation.md))

Do not cache authenticated JSON at a shared edge.

## Disable or shorten cache

| Goal | Action |
|------|--------|
| Dev debugging | Set `mxheadless.cache.enabled` to `false` |
| Faster content updates | Lower `mxheadless.cache_ttl` |
| Immediate API freshness | Disable cache; keep webhooks for the frontend |

Revoking an API key does not purge cache instantly. Entries expire within `mxheadless.cache_ttl`.

## Related

- [HTTP caching](../api/http-caching.md)
- [Performance](performance.md)
- [Settings](../configuration/settings.md)
- [ADR 0005](../decisions/0005-cache.md)
