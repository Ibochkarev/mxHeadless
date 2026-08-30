# Logging

mxHeadless separates three logging paths: request IDs on every response, MODX error log for failures, and an optional DB audit trail.

## Request ID

`RequestIdMiddleware` assigns `X-Request-ID` on every response. Clients may send their own ID (8–64 chars, `[a-zA-Z0-9\-_.]`). Otherwise the gateway generates one.

Use the ID when correlating support tickets with audit rows or problem+json errors.

## MODX error log

Unexpected exceptions (non-`HttpException`) are written to the MODX log:

```
[mxHeadless] <message>
```

When `mxheadless.debug` is `true`, problem+json may include a `debug.message` field for the same error. Keep debug off in production.

Audit log insert failures also log to MODX at error level. The API response still returns normally.

## What never appears in logs

Per the [security model](../security.md):

- Raw API key secrets or `Authorization` values
- Webhook subscription secrets
- Request or response bodies (including in audit log)
- CSRF tokens and session cookies

Error messages should not echo user-supplied SQL or filter strings. Report issues with `request_id` instead.

## Optional audit log

When `mxheadless.audit.enabled` is `true`, `AuditLogMiddleware` writes one row per request to `{prefix}mxheadless_api_log`:

| Stored | Not stored |
|--------|------------|
| Method, path, status, duration | Body, query values |
| `identity_key`, `api_key_id` | Bearer token |
| `request_id`, `context_key` | Passwords |

By default only mutations are logged (`mxheadless.audit.log_get` = `false`). Full reference: [audit log](audit-log.md).

Prune old rows daily:

```bash
php core/components/mxheadless/bin/audit-prune.php
```

## Worker logs

Redirect webhook worker stdout/stderr to a file or journal:

```cron
* * * * * www-data php .../webhook-worker.php --limit=50 >> /var/log/mxheadless-webhook.log 2>&1
```

Look for `Processed N webhook(s)` and non-zero exit codes.

## PSR-3

The component depends on `psr/log` for internal interfaces. Application code today writes through `$modx->log()`. Do not register a custom logger that dumps request bodies.

## Related

- [Audit log](audit-log.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
- [Settings](../configuration/settings.md)
