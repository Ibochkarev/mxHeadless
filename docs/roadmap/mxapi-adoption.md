# mxApi adoption roadmap

What mxHeadless (MODX 3) will adopt from [mxApi](https://docs.modx.pro/components/mxapi/) to strengthen integration infrastructure without becoming a transport-only gateway.

**Status:** Phases 1–3 implemented in code (Phase 3 optional features ship disabled by default).

**Comparison:** [mxHeadless vs mxApi](../comparison/mxapi.md)

## Already covered (do not duplicate)

| mxApi practice | mxHeadless today |
|----------------|------------------|
| Bearer + scope + MODX ACL | `ApiKeyAuthenticator`, `Authorizer` |
| Rate limiting | `RateLimitMiddleware` (identity + IP) |
| Idempotency | `IdempotencyMiddleware` (`POST`, 2xx only) |
| CORS | `CorsMiddleware` |
| Provider registry | `ObjectRegistry`, `OnMxHeadlessRegister` |
| OpenAPI | Static `docs/openapi.yaml`, `GET /schema` |
| Credential revocation | `revoked`, `expires_on` on API keys |

## Phase 1 — Quick wins

Low risk, high value for operators and integrators.

### 1.1 Global kill switch

**Setting:** `mxheadless.enabled` (default `true`)

When `false`, gateway returns `503` with problem code `service_disabled`. Matches mxApi `mxapi.enabled` behavior.

**Contract decision:** `GET /health` and `GET /` (discovery) stay available for load balancers, or everything under `/api/v1` returns 503. Document the chosen behavior in [settings](../configuration/settings.md) before implementation.

**Touch points:** `Application` or first middleware, [production checklist](../operations/production-checklist.md).

### 1.2 Stable machine-readable error codes

Add `code` field to RFC 9457 responses alongside `type` and `title`.

| Code | HTTP | When |
|------|------|------|
| `service_disabled` | 503 | API disabled |
| `token_required` | 401 | Protected route, no credentials |
| `scope_denied` | 403 | Authenticated, missing scope |
| `rate_limited` | 429 | Budget exhausted |
| `idempotency_conflict` | 409 | Parallel idempotent `POST` |

Update [errors](../api/errors.md) and smoke tests in [production checklist](../operations/production-checklist.md).

### 1.3 Live endpoint catalog and OpenAPI

**New routes:**

```
GET /api/v1/meta/endpoints   — route list from RouteCollection
GET /api/v1/meta/openapi     — OpenAPI 3.0 JSON from routes + ObjectRegistry
```

**Services:** `EndpointCatalogService`, `OpenApiGenerator`

**Registrar:** `RoutesRegistrar`

Static `docs/openapi.yaml` remains for repo CI and client generation. Runtime `/meta/openapi` is source of truth on a deployed site (mxApi pattern). See [ADR 0007](../decisions/0007-live-openapi.md).

### 1.4 Comparison document

Done: [comparison/mxapi.md](../comparison/mxapi.md).

### Phase 1 gate

- [x] `meta/openapi` reflects registered routes and objects on a live install
- [x] Smoke curls from production checklist pass
- [x] Error `code` present on documented failure modes

---

## Phase 2 — Operational maturity

### 2.1 Request audit log

DB table for API access metadata (not bodies). Modeled after mxApi `modx_mxapi_log`.

| Column | Purpose |
|--------|---------|
| `request_id` | From `RequestIdMiddleware` |
| `identity_key` | API key lookup, session user id, or `anonymous` |
| `method`, `path` | HTTP route |
| `context_key` | MODX context |
| `status_code` | Response status |
| `duration_ms` | Handler time |
| `api_key_id` | Nullable FK to `mxheadless_api_keys` |
| `created_on` | Unix timestamp |

**Settings:** `mxheadless.audit.enabled`, `mxheadless.audit.retention_days`, optional GET sampling.

**Middleware:** `AuditLogMiddleware` after dispatch.

See [ADR 0008](../decisions/0008-audit-log.md). Future doc: `operations/audit-log.md`.

### 2.2 Per-key rate limits

Extend `mxheadless_api_keys`:

- `rate_limit_max` (nullable → global default)
- `rate_limit_window` (nullable → global default)

`RateLimitMiddleware` reads limits from the verified API key record when present.

### 2.3 Richer `registerEndpoint` metadata

Extend `Route` and `ExtensionApi::registerEndpoint()`:

- `description`, `tags`
- `parameters[]` (`name`, `in`, `schema`, `required`)

Feeds `meta/endpoints` and `meta/openapi`. Extras declare metadata in PHP, not duplicate YAML.

### Phase 2 gate

- [x] Audit rows written for mutations (and sampled GET if enabled)
- [x] Per-key limit covered by unit test
- [x] Retention and privacy documented

---

## Phase 3 — Integrator UX (optional)

Implement only when integrators request it.

### 3.1 Token endpoint (additive, not replacement)

```
POST /api/v1/auth/token
  grant_type=client_credentials
  grant_type=password   (trusted clients only)
```

Tables: `mxheadless_oauth_clients`, `mxheadless_oauth_tokens` (secrets and tokens stored as hashes).

Static `mxh_*` API keys remain for CI and build pipelines. Short-lived tokens suit rotation without redeploy.

**Risk:** Scope creep and security review required before shipping.

### 3.2 Request lifecycle hooks

| Event | When |
|-------|------|
| `OnMxHeadlessRegisterMiddleware` | Before pipeline freeze |
| `OnMxHeadlessBeforeRequest` | After auth, before dispatch |
| `OnMxHeadlessAfterRequest` | After response, before emit |

Analogous to mxApi `mxApiOnRegisterMiddleware`, `mxApiOnBeforeRequest`, `mxApiOnResponse`. Keeps PSR-15 pipeline intact.

### 3.3 Admin endpoint catalog (deferred)

mxApi ships a Vue admin (VueTools). **Not planned for mxHeadless core.**

If needed later: optional extra `mxheadless-admin`, not a core dependency. JSON from `meta/endpoints` and `meta/openapi` is enough for external tools.

### Phase 3 gate

- [x] Token flow covered by unit tests (`client_credentials`, disabled endpoint)
- [x] Extension hooks documented and lifecycle middleware in stable order

---

## Explicit non-goals

| mxApi | Why skip |
|-------|----------|
| MODX 2 / package line 1.x | mxHeadless targets MODX 3.2.3+ only |
| Transport-only API | Conflicts with headless content mission |
| `/mxapi/v1` prefix | Breaking change |
| API keys replaced by tokens only | Keys stay; tokens optional |
| VueTools UI in core | Heavy dependency |
| Running mxApi + mxHeadless as dual gateways | Operational duplication |

## ADRs

| ADR | Topic |
|-----|-------|
| [0007](../decisions/0007-live-openapi.md) | Runtime OpenAPI vs static YAML |
| [0008](../decisions/0008-audit-log.md) | Audit log fields and retention |
| [0009](../decisions/0009-oauth-tokens.md) | OAuth token endpoint |

## Related

- [Architecture](../architecture.md)
- [Extensions overview](../extensions/overview.md)
- [mxApi docs](https://docs.modx.pro/components/mxapi/)
