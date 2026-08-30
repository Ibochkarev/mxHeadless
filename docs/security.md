# mxHeadless Security

## Threat model

### Assets

- MODX database (resources, users, TVs, custom xPDO models)
- API keys and webhook secrets
- Session tokens
- Cache contents
- Internal paths and configuration

### Trust boundaries

| Boundary | Controls |
|----------|----------|
| Internet → web server | TLS, rate limits, body size limits |
| Web server → gateway | Prefix match only, no MODX resource parsing |
| Gateway → middleware | Fail-closed pipeline, request ID |
| Middleware → auth | Identity resolution, no privilege escalation |
| Auth → authorization | MODX ACL + scopes + field policy |
| Authorization → xPDO | Whitelisted fields/filters only |
| Application → cache | Visibility-aware keys |
| Webhooks → external URL | SSRF policy, signed payloads |

### Threat actors

- Anonymous internet clients
- Authenticated frontend users
- Compromised API keys
- Malicious Extras registering unsafe definitions
- Webhook endpoint attackers

## Security invariants

1. **Deny by default.** Objects, fields, relations, filters, sorts, mutations, and endpoints are closed until explicitly registered and permitted.
2. **Authentication ≠ authorization.** Valid identity does not imply any action permission.
3. **No arbitrary class access.** Public names map only through `ObjectRegistry`.
4. **No SQL from clients.** Filters use whitelisted fields and bound parameters.
5. **No secret leakage.** Logs, errors, and cache keys never contain raw API keys, passwords, or webhook secrets.
6. **Context isolation.** Cross-context access requires explicit authorization.
7. **Preview is privileged.** Unpublished content requires `view_unpublished` and preview scope.
8. **Separate APIs.** Public content routes and admin mutation routes use different middleware policies.

## Authentication

### Anonymous

Allowed only on explicitly public endpoints with public object definitions.

### MODX session

Uses existing MODX session in target context. Mutations require CSRF token (`X-CSRF-Token` header matching session).

### API keys

- Format: `mxh_{lookupId}_{secret}` (example)
- Storage: `password_hash()` of secret, never plaintext
- Lookup by public ID, verify with `password_verify()`
- Constant-time comparison for lookup ID
- Scopes: `resources.read`, `resources.create`, etc.
- Rotation: new key issued before old revoked
- Never returned after creation

## Authorization matrix

| Action | Anonymous | Session | API key |
|--------|-----------|---------|---------|
| GET published resource | ✓ (public fields) | ✓ | ✓ (scope + ACL) |
| GET unpublished | ✗ | ✓ (view_unpublished) | ✓ (preview scope) |
| POST resource | ✗ | ✓ (permission + CSRF) | ✓ (scope + permission) |
| Admin objects | ✗ | ✓ | ✓ |

Field-level: `hiddenFields` never loaded. `protectedFields` require field permission.

## Input validation

| Parameter | Limit |
|-----------|-------|
| `limit` | max 100 (configurable) |
| `offset` | max 100000 |
| `fields` | max 50 fields |
| `include` | max depth 2, max 10 relations |
| JSON body | max 1 MB |
| URI path | max 2048 bytes |

## CORS

Default: disabled. When enabled, explicit origins (not `*` with credentials).

## Trusted proxies

`X-Forwarded-For` and `Forwarded` honored only from configured trusted proxy IPs.

## Error disclosure

Production responses never include:

- SQL queries
- Stack traces
- Filesystem paths
- PHP class names
- Credentials

Development mode (`mxheadless.debug`) may include limited debug extension in problem+json.

## Webhook SSRF

Blocked: private IPs, link-local, metadata endpoints, non-HTTP(S) schemes, excessive redirects.

## API key management

Manager processors require `mxheadless_apikeys` permission. Create shows secret once. Revoke is immediate.

## Production checklist

- [ ] `mxheadless.debug` = false
- [ ] CORS origins explicitly configured
- [ ] Rate limiting enabled
- [ ] Trusted proxies configured if behind load balancer
- [ ] API keys use minimal scopes
- [ ] Webhook URLs are HTTPS only
- [ ] MODX manager not exposed on same origin without protection
- [ ] TLS enabled

## Abuse testing

Security test suite covers:

- Arbitrary class injection
- Filter/sort injection
- Context escalation
- Preview bypass (`?preview=1`)
- Unpublished/deleted resource access
- Manager object exposure
- Include depth abuse
- Excessive pagination
- API key timing attacks
- Webhook SSRF
