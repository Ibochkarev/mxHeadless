# Performance

Tune cache TTL, query limits, and payload size before adding hardware. Most slow responses come from wide includes or missing indexes on filtered columns.

## Response cache

| Setting | Default | Tuning |
|---------|---------|--------|
| `mxheadless_cache_enabled` | `true` | Keep on for read-heavy public sites |
| `mxheadless_cache_ttl` | `300` | Raise for stable catalogs; lower for news |

Anonymous GET hits MODX cache after the first request. Authenticated reads skip shared cache. See [cache](cache.md).

## Query limits

Defaults from `QueryParser` (override via system settings if needed):

| Limit | Default |
|-------|---------|
| `mxheadless_max_limit` | 100 |
| `mxheadless_max_offset` | 100000 |
| `mxheadless_max_fields` | 50 |
| `mxheadless_max_include_relations` | 10 |
| `mxheadless_max_include_depth` | 2 |

Ask clients for smaller pages instead of `limit=100` on every call. Deep `include` trees multiply SQL work.

Full list: [limits](../configuration/limits.md).

## Request size

| Setting | Default |
|---------|---------|
| `mxheadless_max_body_bytes` | 1048576 (1 MiB) |
| `mxheadless_max_uri_bytes` | 2048 |

Long filter URLs fail early. Prefer POST with a body only where the API allows it (mutations), not ad-hoc GET workarounds.

## Rate limiting

Global defaults: 120 requests per 60 seconds per client IP and identity. Per-key overrides on `mxheadless_api_keys` replace globals for that key.

Prevents one integration from starving others. See [rate limiting](../api/rate-limiting.md).

## Database

mxHeadless compiles filters to bound xPDO queries. Ensure indexes exist on columns you expose in `filterable` (e.g. `parent`, `published`, foreign keys).

Avoid `offset` in the high five figures. Keyset pagination is not built in; cache list responses or narrow filters instead.

## Frontend patterns

| Pattern | Benefit |
|---------|---------|
| Request only needed `fields` | Smaller JSON and less serialization |
| Use `If-None-Match` | 304 saves bandwidth |
| Webhook-driven revalidation | Frontend cache, not hammering MODX on every page view |
| Server-side fetch with API key | Key stays off the browser |

Examples: [Next.js](../examples/nextjs.md), [Nuxt](../examples/nuxt.md).

## Multi-node cache

Shared MODX cache is required when several nodes serve the same site and you rely on [idempotency](../api/idempotency.md) or response cache coherence.

## Related

- [Cache](cache.md)
- [HTTP caching](../api/http-caching.md)
- [Monitoring](monitoring.md)
