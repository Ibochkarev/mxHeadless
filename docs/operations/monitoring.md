# Monitoring

Use lightweight HTTP probes plus database checks on webhook and audit tables. mxHeadless does not ship a metrics exporter.

## Health probe

`GET /api/v1/health` requires no auth. Poll it from your load balancer or uptime service.

| `data.status` | Meaning |
|---------------|---------|
| `ok` | Database answered `SELECT 1` |
| `degraded` | Database unreachable |

Health does not check cache, disk, or background workers. See [health endpoint](../api/health.md).

Suggested probe interval: 30–60 seconds. Alert on consecutive failures or HTTP errors outside 200.

## Discovery smoke test

```bash
curl -sf https://your-site.example/api/v1/health
curl -sf https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
```

A sudden drop in endpoint count after deploy may mean the plugin failed to bootstrap.

## Rate limit headers

Successful responses may include:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1693142460
```

Spikes in `429` responses point to abuse or a client retry loop. Details: [rate limiting](../api/rate-limiting.md).

## Webhook outbox

When mutations fire webhooks, monitor `mxheadless_webhook_deliveries`:

```sql
SELECT status, COUNT(*) AS cnt
FROM modx_mxheadless_webhook_deliveries
GROUP BY status;
```

Watch for:

- Growing `pending` count (worker not running or subscriber down)
- Rows stuck in `failed` after `mxheadless.webhook.max_attempts`
- Worker log showing `Processed 0 webhook(s)` while editors publish content

Runbook: [workers](workers.md).

## Audit log queries

With audit enabled, sample recent errors:

```sql
SELECT request_id, path, status_code, identity_key, created_on
FROM modx_mxheadless_api_log
WHERE status_code >= 400
ORDER BY created_on DESC
LIMIT 20;
```

More examples: [audit log](audit-log.md).

## Kill switch

`mxheadless.enabled` = `false` returns `503` with `code: service_disabled` on all routes except discovery and health. Use it as an emergency brake without uninstalling the package.

## Related

- [Production checklist](production-checklist.md)
- [Logging](logging.md)
- [Incident response](incident-response.md)
- [Troubleshooting](troubleshooting.md)
