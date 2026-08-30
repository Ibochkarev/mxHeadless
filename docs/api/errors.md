# Errors

Failed requests return [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) Problem Details as `application/problem+json`. There is no `{data, meta}` envelope on errors.

## Shape

```json
{
  "type": "https://mxheadless.dev/problems/unauthorized",
  "title": "Unauthorized",
  "status": 401,
  "detail": "Authentication required",
  "instance": "/api/v1/resources",
  "code": "token_required"
}
```

| Field | Role |
|-------|------|
| `type` | URI identifying the problem category |
| `title` | Short human-readable summary |
| `status` | HTTP status code |
| `detail` | Explanation safe for production |
| `instance` | Request path |
| `code` | Optional stable machine-readable code for smoke tests and clients |
| `errors` | Optional field-level map (validation) |

## Machine-readable codes

| `code` | HTTP | When |
|--------|------|------|
| `service_disabled` | 503 | `mxheadless_enabled` is `false` (except discovery and health) |
| `token_required` | 401 | No `Authorization` / `X-API-Key` credential on a protected route |
| `invalid_token` | 401 | Credential present but wrong, expired, or revoked |
| `scope_denied` | 403 | Authenticated but not allowed |
| `rate_limited` | 429 | Rate limit exceeded |
| `idempotency_conflict` | 409 | Parallel `POST` with the same `Idempotency-Key`, or same key with a different body |
| `invalid_grant` | 400 | OAuth token request rejected (wrong client, disabled grant) |

Not every error includes `code`. Prefer `status` + `type` for generic handling; use `code` when you need a fixed string for automation.

## Production disclosure

When `mxheadless_debug` is `false` (default), responses never include SQL, stack traces, file paths, or PHP class names. Turning debug on is for local development only.

## Common status codes

| Code | When |
|------|------|
| 400 | Unsupported media type |
| 401 | Missing or invalid authentication |
| 403 | Authenticated but not allowed |
| 404 | Route or resource not found (no existence leak for hidden objects) |
| 405 | Method not allowed on route |
| 422 | Validation, malformed JSON body, unknown field, filter, or sort |
| 429 | Rate limit exceeded |
| 500 | Unhandled server error |
| 503 | API disabled via `mxheadless_enabled` |

## Rate limit errors

429 responses may include:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 0
X-RateLimit-Reset: 1693142400
```

See [rate limiting](rate-limiting.md).

## Related

- [Authentication](authentication.md)
- [Security](../security.md)
- [Kill switch](../configuration/settings.md#main)
