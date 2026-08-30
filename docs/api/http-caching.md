# HTTP caching

Public GET responses can be cached in the browser or CDN when `mxheadless.cache.enabled` is `true`.

## Response headers

Anonymous reads on safe routes may return:

```
Cache-Control: public, max-age=300
ETag: "a1b2c3d4e5f6..."
```

`max-age` follows `mxheadless.cache_ttl` (default 300 seconds).

## Conditional requests

Send the ETag from a previous response:

```bash
curl -s -D - https://example.com/api/v1/resources/5 \
  -H 'If-None-Match: "a1b2c3d4e5f6..."'
```

When the representation is unchanged, the server returns `304 Not Modified` without a body.

## Authenticated and preview

Session, API key, and `?preview=true` responses use:

```
Cache-Control: private, no-store
```

Do not cache these at a shared CDN.

## Invalidation

Resource saves and deletes invalidate cache tags for the object and related lists. See [cache operations](../operations/cache.md).

## Related

- [Configuration: cache](../configuration/settings.md)
- [ADR 0005](../decisions/0005-cache.md)
