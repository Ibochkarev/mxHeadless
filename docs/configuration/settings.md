# Configuration reference

System settings use the `mxheadless` namespace in MODX Manager.

## API

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.api.prefix` | `/api` | URL prefix matched by the gateway plugin |

## Main

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.debug` | `false` | Include exception details in problem+json |
| `mxheadless.enabled` | `true` | Kill switch. When `false`, only `GET /` (discovery) and `GET /health` respond; all other `/api/v1` routes return `503` with `code: service_disabled` |

## Cache

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.cache.enabled` | `true` | Enable response cache middleware |
| `mxheadless.cache_ttl` | `300` | `max-age` for public GET (seconds) |

## Rate limit

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.rate_limit.enabled` | `true` | Enable rate limiter |
| `mxheadless.rate_limit.max_requests` | `120` | Requests per window (global default) |
| `mxheadless.rate_limit.window_seconds` | `60` | Window length (global default) |

Per-key overrides: nullable columns `rate_limit_max` and `rate_limit_window` on `mxheadless_api_keys`. When set, they replace the global defaults for that key.

## Audit

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.audit.enabled` | `false` | Write API access rows to `mxheadless_api_log` |
| `mxheadless.audit.retention_days` | `90` | Default retention for `audit-prune.php` |
| `mxheadless.audit.log_get` | `false` | Log `GET` requests when `true` |

See [audit log](../operations/audit-log.md).

## OAuth

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.oauth.enabled` | `false` | Enable `POST /auth/token` |
| `mxheadless.oauth.token_ttl` | `3600` | Access token lifetime (seconds) |
| `mxheadless.oauth.password_grant_enabled` | `false` | Allow `grant_type=password` |

See [auth](../api/auth.md).

## CORS

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.cors.enabled` | `false` | Enable CORS middleware |
| `mxheadless.cors.allowed_origins` | empty | Comma-separated origins |
| `mxheadless.cors.allowed_methods` | GET,POST,... | Allowed methods |
| `mxheadless.cors.allowed_headers` | Authorization,... | Allowed request headers |
| `mxheadless.cors.expose_headers` | ETag,X-Request-ID,... | Headers exposed to browser JS |
| `mxheadless.cors.allow_credentials` | `false` | `Access-Control-Allow-Credentials` |

## Security

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.max_body_bytes` | `1048576` | Max JSON body size |
| `mxheadless.max_uri_bytes` | `2048` | Max request URI length |
| `mxheadless.trusted_proxies` | empty | IPs that may set forwarded headers |
| `mxheadless.csrf.enabled` | `true` | CSRF check for session mutations |

## Idempotency

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.idempotency.enabled` | `true` | Honor `Idempotency-Key` on `POST` |
| `mxheadless.idempotency_ttl` | `86400` | Cached response TTL (seconds) |

See [idempotency](../api/idempotency.md).

## Webhooks

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.webhook.max_attempts` | `5` | Max delivery attempts before `failed` |
| `mxheadless.webhook.worker_limit` | `50` | Default `--limit` for `webhook-worker.php` |
| `mxheadless.webhook.allow_private_urls` | `false` | Allow private/localhost/`.test` webhook targets (dev only) |

See [webhooks](../api/webhooks.md) and [workers](../operations/workers.md).

## Query limits (code defaults)

These are read in `QueryParser` when not defined as system settings:

| Key | Default |
|-----|---------|
| `mxheadless.max_limit` | 100 |
| `mxheadless.max_offset` | 100000 |
| `mxheadless.max_fields` | 50 |
| `mxheadless.max_include_relations` | 10 |
| `mxheadless.max_include_depth` | 2 |
| `mxheadless.allowed_contexts` | `web,mgr` |

You may add them as system settings in a custom build to override defaults.

## Related

- [Limits](limits.md)
- [CORS](cors.md)
- [Trusted proxies](trusted-proxies.md)
