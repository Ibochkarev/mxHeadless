# mxHeadless vs mxApi

Comparison for teams choosing between [mxApi](https://docs.modx.pro/components/mxapi/) and mxHeadless on **MODX Revolution 3**. MODX 2 support is out of scope for mxHeadless.

## One-line summary

| Package | Role |
|---------|------|
| **mxApi** | API transport: routing, tokens, scopes, audit, live OpenAPI. Business endpoints come from providers. |
| **mxHeadless** | Headless content API: resources, pages, query, webhooks, ISR. Infrastructure grows toward mxApi where it helps integrators. |

## Positioning

**mxApi** answers: «How do we expose *any* HTTP API on MODX safely?»

Out of the box it ships only meta routes: token issue/revoke, endpoint catalog, OpenAPI. Orders, users, or custom CRUD are added by other packages or site code.

**mxHeadless** answers: «How do we feed a Nuxt/Next/SvelteKit frontend from MODX JSON?»

It ships content endpoints (`resources`, `pages`, `contexts`, `chunks`), a whitelist query engine, relations, TVs, preview, webhooks with ISR tags, and an Extension API for Extras.

## Feature matrix (MODX 3)

| Area | mxApi | mxHeadless |
|------|-------|------------|
| MODX versions | 2.6+ and 3.0+ | 3.2.3+ only |
| Default prefix | `/mxapi/v1` | `/api/v1` |
| Content API out of the box | No | Yes |
| Bearer auth | Opaque DB tokens (`sha256`) | API keys `mxh_*` + session + anonymous |
| Token endpoint | `POST /auth/token` (password, client_credentials) | `POST /auth/token` when `mxheadless_oauth_enabled` |
| Scopes + MODX ACL | Yes (`mxapi` namespace policies) | Yes (key scopes + MODX permissions) |
| Rate limiting | Per integration client | Global + per-key API limits + per OAuth client |
| Idempotency | `Idempotency-Key` | `Idempotency-Key` on `POST` (2xx cached) |
| Audit log | DB table (`modx_mxapi_log`) | DB table `{prefix}mxheadless_api_log` (optional) |
| OpenAPI | Live `/meta/openapi` from registry | Static YAML + live `/meta/openapi` |
| Admin UI | Vue catalog (VueTools on MODX 3) | Components → mxHeadless (API keys) + System Settings |
| Webhooks / ISR | Provider responsibility | Built-in outbox + `meta.revalidate` tags |
| Query (`filter`, `include`, `fields`) | Provider responsibility | Core whitelist query engine |
| Pages by URI | Provider responsibility | `GET /pages/{uri}` |
| Extension model | `mxApiOnRegisterEndpoints`, middleware hooks | `OnMxHeadlessRegister`, lifecycle hooks, `registerObject`, `registerEndpoint` |

## When to choose mxHeadless

- Headless site on MODX 3: content, menus, contexts, preview
- You need filtering, pagination, relations without writing SQL endpoints
- ISR via webhooks and cache tags for Next.js or Nuxt
- Open source stack with no VueTools dependency in core
- One package for «MODX → JSON» with deny-by-default object registry

## When to choose mxApi

- Partner or internal API with mostly custom routes (orders, CRM, imports)
- You need a token endpoint and machine clients without pre-created static keys
- Central audit log in the database is mandatory from day one
- Other Extras will register as mxApi providers
- You may run MODX 2 today or care about 2→3 migration of tokens and clients

## When both appear on one site

Technically possible (`/mxapi/v1` vs `/api/v1`), but usually a mistake:

- Two gateways duplicate auth, rate limits, and documentation
- Integrators confuse prefixes and credential types

Pick one primary public API layer. Use mxHeadless for the frontend and mxApi only if a separate integration surface is a hard requirement—and document which URL owns which clients.

## What mxHeadless adopts from mxApi

See [roadmap](../roadmap/mxapi-adoption.md). Planned in phases:

1. Kill switch, stable error codes, `/meta/endpoints`, `/meta/openapi`
2. Audit log, per-key rate limits, richer endpoint metadata
3. Optional token endpoint and request lifecycle hooks (disabled by default; see [auth](../api/auth.md))

## What mxHeadless does not adopt

| mxApi feature | Reason |
|---------------|--------|
| MODX 2 line | Explicit non-goal |
| Transport-only (no content API) | Different product |
| `/mxapi/v1` prefix | Breaking change for existing clients |
| VueTools admin in core | Heavy optional dependency |
| Replacing API keys with tokens only | Keys fit CI/build; tokens are additive |

## Related

- [mxApi documentation](https://docs.modx.pro/components/mxapi/)
- [mxHeadless architecture](../architecture.md)
- [Adoption roadmap](../roadmap/mxapi-adoption.md)
- [ADR: Live OpenAPI](../decisions/0007-live-openapi.md)
- [ADR: Audit log](../decisions/0008-audit-log.md)
