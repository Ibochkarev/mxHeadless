# OAuth access tokens

## Status

Accepted — implemented in Phase 3

## Context

Integrators asked for mxApi-style short-lived tokens without replacing static `mxh_*` API keys. Keys remain ideal for CI and build pipelines. Tokens suit rotation and scoped machine clients.

## Decision

Add an optional OAuth-style token endpoint:

```
POST /api/v1/auth/token
```

### Token format

Opaque bearer tokens: `mxt_{tokenId}_{secret}`. Only `token_hash` is stored in `{prefix}mxheadless_oauth_tokens`.

### Grant types

| Grant | Default | Notes |
|-------|---------|-------|
| `client_credentials` | Enabled when OAuth is on | Requires registered client + secret hash |
| `password` | Disabled (`mxheadless_oauth_password_grant_enabled`) | MODX user credentials; client must allow grant |

### Tables

- `mxheadless_oauth_clients` — `client_id`, `client_secret_hash`, scopes, grant_types, optional per-client rate limits
- `mxheadless_oauth_tokens` — issued tokens with expiry and optional `user_id` for password grant

Uninstall does not drop tables (same policy as API keys and audit log).

### Authentication chain

`OAuthTokenAuthenticator` runs before `ApiKeyAuthenticator`. Tokens use the same scope checker as API keys.

### Settings

| Key | Default |
|-----|---------|
| `mxheadless_oauth_enabled` | `false` |
| `mxheadless_oauth_token_ttl` | `3600` |
| `mxheadless_oauth_password_grant_enabled` | `false` |

Master switch is off by default. Operators opt in explicitly.

## Alternatives considered

**JWT only** — no server-side revocation without extra infrastructure. Rejected for v1.

**Replace API keys** — breaks existing CI flows. Rejected.

**No password grant** — kept behind a separate setting for trusted first-party clients only.

## Consequences

- New error code `invalid_grant` (HTTP 400).
- Doc: [api/auth.md](../api/auth.md).
- Security review recommended before enabling password grant in production.

## Related

- [mxApi adoption roadmap](../roadmap/mxapi-adoption.md)
- [Lifecycle hooks](../extensions/lifecycle-hooks.md)
