# Troubleshooting

Common mxHeadless failures and where to look first.

## 404 on `/api/v1/*`

| Cause | Fix |
|-------|-----|
| Plugin disabled | Enable mxHeadless plugin in Manager |
| Wrong prefix | Check `mxheadless.api.prefix` (default `/api`) |
| Rewrite bypasses MODX | Route `/api` to `index.php`; see [web server](../installation/web-server.md) |
| Friendly URLs off | Use `assets/components/mxheadless/api.php?route=/v1/health` (or PATH_INFO `api.php/v1/health` when supported) |

Verify:

```bash
curl -sI https://your-site.example/api/v1/health
```

## 503 `service_disabled`

`mxheadless.enabled` is `false`. Discovery (`GET /api/v1`) and health still work. Turn the setting on in **System Settings → mxheadless**.

## 401 / 403 on protected routes

| Symptom | Likely cause |
|---------|--------------|
| `token_required` | Missing `Authorization: Bearer mxh_...` |
| Invalid key | Revoked, expired, or wrong secret |
| 403 on mutation | Missing scope, MODX ACL, or CSRF on session auth |
| Preview content | Need `preview` scope or `view_unpublished` |

CSRF: send `X-CSRF-Token` from the manager session on `POST`/`PUT`/`PATCH`/`DELETE`. See [authentication](../api/authentication.md).

## 429 Too Many Requests

Rate limit exceeded. Check `X-RateLimit-*` headers and per-key overrides. Tune `mxheadless.rate_limit.*` or the key's `rate_limit_max` / `rate_limit_window`.

If limits look wrong for real clients, verify [trusted proxies](../configuration/trusted-proxies.md). Without trusted balancer IPs, everyone may share one IP.

## 422 validation errors

Filter, sort, or field names must match the object definition. Unknown public names return 404 from the registry, not SQL errors.

Use `GET /api/v1/schema` and [filtering](../api/filtering.md) docs for allowed parameters.

## 500 internal errors

1. Check MODX error log for `[mxHeadless]` lines.
2. Temporarily set `mxheadless.debug` to `true` in staging only (never production long-term).
3. Note `request_id` from the response header for audit correlation.

## Webhooks not delivered

1. Confirm `bin/webhook-worker.php` runs on a schedule ([workers](workers.md)).
2. Query pending deliveries: `SELECT * FROM modx_mxheadless_webhook_deliveries WHERE status = 'pending' LIMIT 10`.
3. Check subscriber URL is HTTPS and reachable (not blocked by `mxheadless.webhook.allow_private_urls` in production).
4. Verify signature on the frontend matches the subscription secret ([ISR revalidation](isr-revalidation.md)).

## Stale JSON after edit

1. Response cache TTL may still apply (`mxheadless.cache_ttl`). Tag invalidation runs on mutation; CDN edge may lag.
2. Frontend static cache needs webhook revalidation tags.
3. Browser may hold ETag. Hard refresh or send `Cache-Control: no-cache`.

## Empty lists or missing objects

- Object not registered in `OnMxHeadlessRegister`
- Wrong context (`X-Context` header or `?context=`)
- `mxheadless.allowed_contexts` excludes the target context
- Published filter hides unpublished resources

## Related

- [Deployment](deployment.md)
- [Monitoring](monitoring.md)
- [Logging](logging.md)
- [Production checklist](production-checklist.md)
