# Configuration reference

System settings live in the `mxheadless` namespace in MODX Manager. Keys use underscores (`mxheadless_cors_enabled`), not dots.

From 1.0.42 the package renames any leftover dotted keys on upgrade and copies their values.

## API

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_api_prefix` | `/api` | URL prefix matched by the gateway plugin |

## Main

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_debug` | `false` | Include exception details in problem+json |
| `mxheadless_enabled` | `true` | Kill switch. When `false`, only `GET /` (discovery) and `GET /health` respond; all other `/api/v1` routes return `503` with `code: service_disabled` |
| `mxheadless_swagger_enabled` | `true` | Serve Swagger UI at `GET /docs`. OpenAPI JSON stays available when disabled |

## Cache

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_cache_enabled` | `true` | Enable response cache middleware |
| `mxheadless_cache_ttl` | `300` | `max-age` for public GET (seconds) |

## Rate limit

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_rate_limit_enabled` | `true` | Enable rate limiter |
| `mxheadless_rate_limit_max_requests` | `120` | Requests per window (global default) |
| `mxheadless_rate_limit_window_seconds` | `60` | Window length (global default) |

Per-key overrides: nullable columns `rate_limit_max` and `rate_limit_window` on `mxheadless_api_keys`. When set, they replace the global defaults for that key.

## Audit

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_audit_enabled` | `false` | Write API access rows to `mxheadless_api_log` |
| `mxheadless_audit_retention_days` | `90` | Default retention for `audit-prune.php` |
| `mxheadless_audit_log_get` | `false` | Log `GET` requests when `true` |

See [audit log](../operations/audit-log.md).

## OAuth

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_oauth_enabled` | `false` | Enable `POST /auth/token` |
| `mxheadless_oauth_token_ttl` | `3600` | Access token lifetime (seconds) |
| `mxheadless_oauth_password_grant_enabled` | `false` | Allow `grant_type=password` |

See [auth](../api/auth.md).

## CORS

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_cors_enabled` | `false` | Enable CORS middleware |
| `mxheadless_cors_allowed_origins` | empty | Comma-separated origins |
| `mxheadless_cors_allowed_methods` | GET,POST,... | Allowed methods |
| `mxheadless_cors_allowed_headers` | Authorization,... | Allowed request headers |
| `mxheadless_cors_expose_headers` | ETag,X-Request-ID,... | Headers exposed to browser JS |
| `mxheadless_cors_allow_credentials` | `false` | `Access-Control-Allow-Credentials` |

## Security

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_max_body_bytes` | `1048576` | Max JSON body size |
| `mxheadless_max_uri_bytes` | `2048` | Max request URI length |
| `mxheadless_trusted_proxies` | empty | IPs that may set forwarded headers |
| `mxheadless_csrf_enabled` | `true` | CSRF check for session mutations |

## Idempotency

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_idempotency_enabled` | `true` | Honor `Idempotency-Key` on `POST` |
| `mxheadless_idempotency_ttl` | `86400` | Cached response TTL (seconds) |

See [idempotency](../api/idempotency.md).

## Webhooks

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless_webhook_max_attempts` | `5` | Max delivery attempts before `failed` |
| `mxheadless_webhook_worker_limit` | `50` | Default `--limit` for `webhook-worker.php` |
| `mxheadless_webhook_allow_private_urls` | `false` | Allow private/localhost/`.test` webhook targets (dev only) |

See [webhooks](../api/webhooks.md) and [workers](../operations/workers.md).

## Query limits (code defaults)

These are read in `QueryParser` when not defined as system settings:

| Key | Default |
|-----|---------|
| `mxheadless_max_limit` | 100 |
| `mxheadless_max_offset` | 100000 |
| `mxheadless_max_fields` | 50 |
| `mxheadless_max_include_relations` | 10 |
| `mxheadless_max_include_depth` | 2 |
| `mxheadless_allowed_contexts` | `web,mgr` |

You may add them as system settings in a custom build to override defaults.

## Related

- [Limits](limits.md)
- [CORS](cors.md)
- [Trusted proxies](trusted-proxies.md)
