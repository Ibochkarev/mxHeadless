# OAuth token endpoint

Optional short-lived bearer tokens for machine clients. Static `mxh_*` API keys remain for CI and long-lived integrations.

## Enable

| Setting | Default | Description |
|---------|---------|-------------|
| `mxheadless_oauth_enabled` | `false` | Master switch for `POST /auth/token` |
| `mxheadless_oauth_token_ttl` | `3600` | Access token lifetime in seconds |
| `mxheadless_oauth_password_grant_enabled` | `false` | Allow `grant_type=password` |

Create OAuth clients in `{prefix}mxheadless_oauth_clients` (hashed secret, scopes, allowed grant types). See [OAuth clients CLI](../operations/oauth-clients.md).

## Issue a token

```http
POST /api/v1/auth/token
Content-Type: application/json

{
  "grant_type": "client_credentials",
  "client_id": "next-preview",
  "client_secret": "…",
  "scope": "resources.read"
}
```

`Content-Type: application/x-www-form-urlencoded` is also accepted (OAuth 2.0 style body).

Client credentials may also be sent as `Authorization: Basic base64(client_id:client_secret)` instead of body fields.

Response:

```json
{
  "data": {
    "access_token": "mxt_…",
    "token_type": "Bearer",
    "expires_in": 3600,
    "scope": "resources.read"
  },
  "meta": {}
}
```

Use the token as `Authorization: Bearer mxt_…` on subsequent requests.

## Grant types

| Grant | Requires | Notes |
|-------|----------|-------|
| `client_credentials` | `client_id`, `client_secret` | Default for build pipelines and service accounts |
| `password` | `client_id`, `client_secret`, `username`, `password` | Disabled by default. Client must list `password` in `grant_types` |

## Error codes

| Code | HTTP | When |
|------|------|------|
| `invalid_grant` | 400 | Wrong client credentials, unsupported grant, disabled endpoint |
| validation errors | 422 | Missing `grant_type`, `client_id`, etc. |

## Security notes

- Store only password hashes for client secrets and token secrets (`token_hash`).
- Tokens expire automatically. Revoke by setting `revoked = 1` on the token row.
- Password grant is for trusted first-party clients only. Keep it disabled unless you explicitly need it.
- OAuth clients may define per-client rate limits (`rate_limit_max`, `rate_limit_window`).

## Related

- [Errors](errors.md)
- [Settings](../configuration/settings.md)
- [ADR 0009](../decisions/0009-oauth-tokens.md)
