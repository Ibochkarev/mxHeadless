# Authentication

mxHeadless resolves **who** is calling. Authorization (scopes + MODX ACL) decides **what** they may do.

## Identity types

| Type | Use case | Header / mechanism |
|------|----------|-------------------|
| Anonymous | Public read endpoints | No credentials |
| Session | Manager UI or same-origin frontend with MODX cookie | Session cookie |
| API key | Long-lived server-to-server, CI, build pipelines | `Authorization: Bearer mxh_...` |
| OAuth token | Short-lived machine access (optional) | `Authorization: Bearer mxt_...` |

Authenticator order: OAuth token → API key → session → anonymous.

## API keys (`mxh_*`)

Long-lived credentials. Best for CI, static builds, and integrations that rarely rotate.

### Format

```
mxh_{lookupId}_{secret}
```

Example: `mxh_a1b2c3d4_xK9mN2pQ8rT5vW1yZ6`

- `lookupId` — public id in `{prefix}mxheadless_api_keys`
- `secret` — shown once at creation, stored as `password_hash()`

Optional per-key rate limits: columns `rate_limit_max`, `rate_limit_window`.

### Request

```bash
curl -s https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_a1b2c3d4_xK9mN2pQ8rT5vW1yZ6'
```

Create and revoke in MODX Manager (`mxheadless_apikeys`). See [API keys](api-keys.md).

## OAuth tokens (`mxt_*`)

Short-lived bearer tokens issued by `POST /api/v1/auth/token`. **Disabled by default** (`mxheadless_oauth_enabled`).

### Format

```
mxt_{tokenId}_{secret}
```

Only `token_hash` is stored in `{prefix}mxheadless_oauth_tokens`. Tokens expire after `mxheadless_oauth_token_ttl` (default 3600s).

### Issue a token

```bash
curl -s -X POST https://example.com/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "next-preview",
    "client_secret": "YOUR_CLIENT_SECRET",
    "scope": "resources.read"
  }'
```

Use the returned `access_token` as `Authorization: Bearer mxt_...`.

Clients are created via [OAuth clients CLI](../operations/oauth-clients.md). Details: [OAuth token endpoint](auth.md).

### When to use keys vs tokens

| Credential | Prefer when |
|------------|-------------|
| `mxh_*` API key | CI secrets, long-running workers, no token refresh logic |
| `mxt_*` OAuth token | Rotation without redeploy, scoped machine clients, TTL-bound access |

Both use the same scope checker (`ApiKeyPermissionChecker`). CSRF does not apply to either.

## Scopes

Examples:

- `resources.read`, `resources.create`, `resources.update`, `resources.delete`
- `objects.products.read`
- `preview` — unpublished content via API
- `*` — wildcard (OAuth clients with empty scope list issue tokens with `*`)

Missing scope → `403` with `code: scope_denied`.

## MODX session

When the browser has a MODX session cookie for the target context, mxHeadless resolves the logged-in user.

### CSRF

State-changing requests (`POST`, `PUT`, `PATCH`, `DELETE`) with session auth require:

```
X-CSRF-Token: {token from MODX session}
```

Bearer credentials (`mxh_*`, `mxt_*`) skip CSRF.

## Anonymous access

Only routes marked `public` and objects readable without auth. Everything else → `401` with `code: token_required`.

## Authorization flow

```
Request → AuthenticationMiddleware → Identity
        → AuthorizationMiddleware → scope + MODX permission + context ACL + field policy
        → RequestLifecycleMiddleware (optional hooks)
        → Controller / Service
```

Field rules: `hiddenFields` never returned. `protectedFields` need explicit permission.

## Preview mode

`?preview=true` includes unpublished resources when the identity has `view_unpublished` (session) or `preview` scope (key/token). Anonymous preview is denied.

## Error responses

| Status | `code` (when set) | Meaning |
|--------|-------------------|---------|
| 401 | `token_required` | Missing or invalid bearer/session |
| 403 | `scope_denied` | Authenticated, insufficient scope or ACL |
| 400 | `invalid_grant` | OAuth token request rejected |
| 422 | — | Validation (missing `grant_type`, bad context, …) |

See [errors](errors.md).

## Production notes

- Minimal scopes per key or OAuth client
- API keys for stable server clients. OAuth tokens when you need expiry and client credentials flow
- Never log raw secrets or full `Authorization` headers
- Keep `mxheadless_oauth_password_grant_enabled` off unless you trust every client with MODX user passwords
- See [security checklist](../security.md)

## Related

- [API keys](api-keys.md)
- [OAuth token endpoint](auth.md)
- [Authorization](authorization.md)
- [Rate limiting](rate-limiting.md)
