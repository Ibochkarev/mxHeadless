# Idempotency

Safe retries for `POST` mutations use the `Idempotency-Key` header. When the client repeats the same create after a timeout, mxHeadless returns the stored response instead of creating a duplicate row.

## Header

```
POST /api/v1/resources
Idempotency-Key: 550e8400-e29b-41d4-a716-446655440000
Content-Type: application/json
```

Rules:

- Optional. Without the header, behavior is unchanged.
- 1–128 characters: letters, digits, `.`, `_`, `:`, `-`.
- Scoped per authenticated actor (API key or session) and request path.
- TTL: `mxheadless_idempotency_ttl` (default `86400` seconds).
- Toggle: `mxheadless_idempotency_enabled`.

## Responses

| Case | Status | Notes |
|------|--------|-------|
| First successful request | `201` (or other `2xx`) | Response stored |
| Replay with same key **and same body** | Original status | `Idempotency-Replayed: true` |
| Same key, **different body** | `409` | `code: idempotency_conflict` |
| Failed validation (`4xx`/`5xx`) | Original status | Not stored, retries run the handler again |
| Parallel duplicate | `409 Conflict` | Another request with the same key is in progress |
| Invalid key format | `422` | Validation error |

Only `POST` requests are idempotent. `PUT`, `PATCH`, and `DELETE` already target a stable URL.

## Storage

Records live in the MODX cache partition `mxheadless/idempotency`. Use a shared cache backend in multi-node deployments so all app servers see the same keys.

## Related

- [Mutations](mutations.md)
- [Configuration](../configuration/settings.md)
