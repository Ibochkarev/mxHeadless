# Rate limiting

When `mxheadless.rate_limit.enabled` is `true` (default), each client identity gets a request budget per time window.

## Defaults

| Setting | Default |
|---------|---------|
| `mxheadless.rate_limit.max_requests` | 120 |
| `mxheadless.rate_limit.window_seconds` | 60 |

The limiter keys by client IP (after trusted proxy resolution) and identity (`identity_key`).

Per-identity overrides:

| Source | Columns / attributes |
|--------|----------------------|
| API key | `rate_limit_max`, `rate_limit_window` on `mxheadless_api_keys` |
| OAuth client | same columns on `mxheadless_oauth_clients` (applies to `mxt_*` tokens from that client) |

When set, these replace global `mxheadless.rate_limit.*` for that identity.

## Response headers

Successful responses may include:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1693142460
```

When the budget is exhausted:

```
HTTP/1.1 429 Too Many Requests
```

See [errors](errors.md).

## Disabling

Set `mxheadless.rate_limit.enabled` to `false` only on trusted internal networks. Public sites should keep limiting on.

## Related

- [Trusted proxies](../configuration/trusted-proxies.md)
- [Limits](../configuration/limits.md)
