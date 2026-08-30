# Audit log

Optional database audit trail for API requests. Modeled after mxApi access logs, without storing request or response bodies.

## Enable

| Setting | Default | Description |
|---------|---------|-------------|
| `mxheadless.audit.enabled` | `false` | Master switch |
| `mxheadless.audit.retention_days` | `90` | Rows older than this are removed by the prune worker |
| `mxheadless.audit.log_get` | `false` | When `false`, only `POST`, `PUT`, `PATCH`, and `DELETE` are logged |

When disabled, `AuditLogMiddleware` is a no-op.

## What is stored

Table `{prefix}mxheadless_api_log`:

| Column | Description |
|--------|-------------|
| `request_id` | From `X-Request-ID` / `RequestIdMiddleware` |
| `identity_key` | `api_key:{lookupId}`, `session:{userId}`, or `anonymous` |
| `api_key_id` | FK to `mxheadless_api_keys.id` when a bearer key was used |
| `method` | HTTP method |
| `path` | Request path without query string |
| `context_key` | From `X-Context`, `?context=`, or current MODX context |
| `status_code` | Final HTTP status |
| `duration_ms` | Wall time for the middleware stack after audit starts |
| `created_on` | Unix timestamp |

## What is never stored

- Request or response body
- `Authorization` header or API key secret
- CSRF tokens, cookies, passwords
- Query string values

See [ADR 0008](../decisions/0008-audit-log.md).

## Retention

Run daily (cron or systemd timer):

```bash
php core/components/mxheadless/bin/audit-prune.php
```

Override retention:

```bash
php core/components/mxheadless/bin/audit-prune.php --days=30
```

Without `--days`, the worker reads `mxheadless.audit.retention_days`.

## Example queries

Recent mutations by API key:

```sql
SELECT request_id, method, path, status_code, duration_ms, created_on
FROM modx_mxheadless_api_log
WHERE api_key_id = 3
ORDER BY created_on DESC
LIMIT 50;
```

Find failed writes in the last hour:

```sql
SELECT identity_key, path, status_code, request_id
FROM modx_mxheadless_api_log
WHERE method IN ('POST', 'PUT', 'PATCH', 'DELETE')
  AND status_code >= 400
  AND created_on > UNIX_TIMESTAMP() - 3600;
```

Correlate with support tickets using `request_id` from problem+json responses.

## Failure behavior

If the insert fails, the API response still returns. A PSR-3 error is written to the MODX log.

## Related

- [Logging](logging.md)
- [Workers](workers.md)
- [OAuth clients CLI](oauth-clients.md)
- [Settings](../configuration/settings.md)
