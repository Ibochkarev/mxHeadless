# Live OpenAPI vs Static YAML

## Status

Accepted (Phase 1 implemented)

## Context

mxHeadless ships a hand-maintained [`docs/openapi.yaml`](../openapi.yaml) and a runtime [`GET /schema`](../api/schema.md) for registered objects. [mxApi](https://docs.modx.pro/components/mxapi/) exposes `/meta/openapi` generated from the live endpoint registry so integrators always see what the site actually serves.

Extras register objects and routes at bootstrap via `OnMxHeadlessRegister`. A static YAML file can drift from production when:

- An Extra is installed or removed
- Site-specific endpoints are registered in a plugin
- Object fields or permissions change in a custom build

## Decision

Maintain **two complementary OpenAPI sources**:

| Source | Role | Audience |
|--------|------|----------|
| `docs/openapi.yaml` in the repository | Versioned contract for CI, client generators, and docs site | Developers working in git |
| `GET /api/v1/meta/openapi` at runtime | Generated JSON from `RouteCollection` + `ObjectRegistry` after bootstrap | Integrators on a live MODX install |

Add `GET /api/v1/meta/endpoints` as a lightweight route catalog (name, methods, pattern, public flag, permission).

Generation logic lives in `OpenApiGenerator` and `EndpointCatalogService`, registered in `RoutesRegistrar`.

### Generation rules

1. **Routes** — every entry in `RouteCollection`, including Extras via `registerEndpoint`.
2. **Objects** — CRUD paths derived from `ObjectRegistry` definitions (same surface as `/objects/{name}`).
3. **Core routes** — discovery, health, schema, resources, pages, contexts, meta routes included explicitly.
4. **Security** — `bearerAuth` scheme documents API keys; public routes marked `security: []`.
5. **Envelope** — document `{ data, meta, links }` response wrapper and RFC 9457 errors in `components`.

### Static YAML maintenance

- Release workflow: when public routes change, update `docs/openapi.yaml` in the same PR.
- CI check (future): diff or lint that static YAML covers core routes; runtime generator is authoritative on install.
- Russian docs link to the same `openapi.yaml`; no duplicate spec.

### Access control

`meta/endpoints` and `meta/openapi` are **public read** (no auth), matching mxApi catalog behavior. They expose route shapes and field names already implied by public API calls, not secrets.

## Alternatives considered

**Static YAML only** — simpler, but drifts on customized installs. Rejected as sole source.

**Drop static YAML** — breaks offline client generation and modx-docs style references in the repo. Rejected.

**OpenAPI only from `/schema`** — schema covers objects, not custom endpoints or HTTP parameters. Insufficient.

## Consequences

- Implement `OpenApiGenerator` and tests comparing core routes to static YAML baseline.
- Document both URLs in [discovery](../api/discovery.md) and [comparison/mxapi](../comparison/mxapi.md).
- Extension authors can add OpenAPI metadata via enriched `registerEndpoint` (Phase 2).

## Related

- [mxApi adoption roadmap](../roadmap/mxapi-adoption.md)
- [ADR 0008: Audit log](0008-audit-log.md)
