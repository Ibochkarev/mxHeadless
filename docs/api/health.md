# Health

`GET /api/v1/health` reports whether mxHeadless can reach the database. Use it for load balancer probes and uptime checks.

No authentication required.

## Request

```bash
curl -s https://example.com/api/v1/health
```

## Response

When the database answers `SELECT 1`:

```json
{
  "data": {
    "status": "ok",
    "database": true,
    "timestamp": "2026-08-27T12:00:00+00:00"
  },
  "meta": {}
}
```

When the query fails:

```json
{
  "data": {
    "status": "degraded",
    "database": false,
    "timestamp": "2026-08-27T12:00:00+00:00"
  },
  "meta": {}
}
```

| Field | Meaning |
|-------|---------|
| `status` | `ok` if DB is reachable, otherwise `degraded` |
| `database` | Result of the DB ping |
| `timestamp` | UTC time of the check (ISO 8601) |

Health does not validate MODX cache, disk space, or webhook workers. For operational runbooks see [monitoring](../operations/monitoring.md).

## Related

- [Discovery](discovery.md)
- [Deployment](../operations/deployment.md)
