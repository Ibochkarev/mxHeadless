# API Audit Log

## Status

Accepted — implemented in Phase 2

## Context

[mxApi](https://docs.modx.pro/components/mxapi/) stores every API call in `modx_mxapi_log`: identity, token reference, context, result. mxHeadless today logs through PSR-3 to the MODX error log with redaction ([logging](../operations/logging.md)). That is enough for debugging but weak for compliance, incident response, and answering «who changed what via the API».

Headless sites often use long-lived API keys. Operators need a queryable history without parsing unstructured log files.

## Decision

Add an optional **database audit log** for API requests, written by `AuditLogMiddleware` after the response is produced.

### Table: `mxheadless_api_log`

| Column | Type | Notes |
|--------|------|-------|
| `id` | int PK | Auto increment |
| `request_id` | varchar(64) | From `RequestIdMiddleware`; correlates with problem+json and support tickets |
| `identity_key` | varchar(128) | API key lookup id, `session:{userId}`, or `anonymous` |
| `api_key_id` | int nullable | FK to `mxheadless_api_keys.id` when bearer key used |
| `method` | varchar(8) | HTTP method |
| `path` | varchar(2048) | Path without query string |
| `context_key` | varchar(64) | Resolved MODX context |
| `status_code` | smallint | HTTP status |
| `duration_ms` | int | Wall time for handler + middleware after routing |
| `created_on` | int unsigned | Unix timestamp |

Indexes: `created_on`, `api_key_id`, `identity_key`, `request_id`.

Package uninstall **does not drop** the table (same policy as mxApi log and webhook tables).

### Settings

| Key | Default | Purpose |
|-----|---------|---------|
| `mxheadless_audit_enabled` | `false` | Master switch |
| `mxheadless_audit_retention_days` | `90` | Cron or worker prunes older rows |
| `mxheadless_audit_log_get` | `false` | When false, only mutating methods (`POST`, `PUT`, `PATCH`, `DELETE`) are logged |

### What we log

- Request metadata listed above
- Route name when resolved (optional column `route_name` in v1.1 if needed)

### What we never log

- Request or response **body**
- `Authorization` header or full bearer token
- API key **secret** (only `api_key_id` / lookup id)
- CSRF tokens, cookies, passwords
- Query string values that may contain PII (path only; query params omitted unless a future ADR adds allowlisted keys)

Redaction matches mxApi spirit: audit proves access, not content.

### Write path

Synchronous insert in `AuditLogMiddleware` after `$handler->handle()` returns. Async queue is out of scope for v1; revisit if write latency becomes measurable.

Failures to insert must **not** fail the API response. Log a PSR-3 error and continue.

### Retention

Document a cron snippet or reuse webhook worker pattern to delete rows older than `retention_days`. No automatic purge in the request path.

### Relation to MODX log

Keep PSR-3 logging for errors and debug. Audit table is for structured access history, not stack traces.

## Alternatives considered

**File-only audit** — already exists; poor for SQL reporting. Rejected as sole solution.

**Full request/response capture** — GDPR and secret-leak risk. Rejected.

**External SIEM only** — valid for large ops, but MODX hosts often lack it. DB audit is the pragmatic default.

## Consequences

- New xPDO model, resolver in `_build`, migration on upgrade.
- [mxApi adoption roadmap](../roadmap/mxapi-adoption.md) Phase 2 gate: mutations logged when enabled.
- Doc: [operations/audit-log.md](../operations/audit-log.md).

## Related

- [mxHeadless vs mxApi](../comparison/mxapi.md)
- [ADR 0007: Live OpenAPI](0007-live-openapi.md)
- [Security](../security.md)
- [Trusted proxies](../configuration/trusted-proxies.md)
