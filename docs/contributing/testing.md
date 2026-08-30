# Testing and verification checklist

Complete map of **automatic** and **manual** checks for mxHeadless. Use it before a release, after a rebuild on a live MODX site, or when closing a bugfix.

Principle: evidence before “done”. Run the gate, read the exit code, then claim pass. Keep docs aligned with live OpenAPI.

## Coverage map

| Layer | Covered here | Gaps (manual or missing automation) |
|-------|--------------|-------------------------------------|
| PHPUnit unit/security | §1.1 (includes Cors, Csrf, BodyLimit, HttpCache, TrustedProxy, RequestId, Error, Routing, MediaUrlResolver) | — |
| Composer gates | §1 | — |
| Live e2e script | §1.2 | CSRF session mutate, rate-limit 429 (unit covers), webhook delivery, Extension Extra, Manager UI |
| Manual matrix | §2 | Required for anything not in e2e |
| Production go-live | [production-checklist](../operations/production-checklist.md) | Ops settings, cron, CDN |

## 1. Automatic gates (must pass)

Run from the component repository root. No MODX install required for unit suites.

| Gate | Command | Pass criteria |
|------|---------|---------------|
| Unit + security PHPUnit | `composer test` | Exit 0, 0 failures |
| Static analysis | `composer phpstan` | Exit 0, no errors |
| Coding standard | `composer phpcs` | Exit 0 |
| Transport rebuild | `cd _build && php build.php` | Install/update completes, cache clear OK |
| Live e2e (required before release to a real host) | `php scripts/e2e-live.php https://HOST "$API_KEY" [oauth_client] [oauth_secret]` | All checks `OK`, `0 failed` |

Version after rebuild:

```bash
curl -s https://HOST/api/v1 | jq -r '.data.version'
```

Must match `Version::STRING` and `_build/config.inc.php`.

### 1.1 PHPUnit suites

| Suite path | Behavior covered |
|------------|------------------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Object register, overwrite, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | limit/offset/page conflict, filters, `ne`→`neq`, sort `field:asc` aliases, include depth, include_deleted aliases, context header |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist select/filter/sort, bound params, boolean filters, count without limit |
| `tests/Unit/Authorization/AuthorizerContextTest.php` | Context ACL / accessible keys |
| `tests/Unit/Authorization/AuthorizerEndpointTest.php` | Public GET scopes, session vs API key, HEAD anonymous |
| `tests/Unit/Authentication/CredentialExpiryTest.php` | API key / OAuth datetime expiry |
| `tests/Unit/Authentication/IdentityAllowsTest.php` | Wildcard scope, session permissions |
| `tests/Unit/Authentication/OAuthTokenAuthenticatorTest.php` | `mxt_` bearer |
| `tests/Unit/Middleware/ContentNegotiationMiddlewareTest.php` | JSON/form CT, HTML only `/docs`, openapi+json, 406/415 |
| `tests/Unit/Middleware/CorsMiddlewareTest.php` | OPTIONS/GET allowlist Origin, disabled CORS |
| `tests/Unit/Middleware/CsrfMiddlewareTest.php` | Session mutate requires `X-CSRF-Token`; API key bypass |
| `tests/Unit/Middleware/BodyLimitMiddlewareTest.php` | URI 414, body 413 |
| `tests/Unit/Middleware/RequestIdMiddlewareTest.php` | Echo / generate `X-Request-ID` |
| `tests/Unit/Middleware/TrustedProxyMiddlewareTest.php` | `client_ip` from `X-Forwarded-For` only when trusted |
| `tests/Unit/Middleware/ErrorMiddlewareTest.php` | HttpException + debug on/off for 500 |
| `tests/Unit/Middleware/HttpCacheMiddlewareTest.php` | public ETag/304, private no-store, HEAD |
| `tests/Unit/Middleware/RoutingMiddlewareTest.php` | Match sets route attrs; unknown → 404 |
| `tests/Unit/Middleware/RateLimitMiddlewareTest.php` | Global vs per-key limits, headers, 429 |
| `tests/Unit/Middleware/IdempotencyMiddlewareTest.php` | Replay vs body conflict |
| `tests/Unit/Middleware/ServiceGateMiddlewareTest.php` | Kill switch discovery/health vs blocked routes |
| `tests/Unit/Middleware/AuditLogMiddlewareTest.php` | Audit on/off, GET logging flag |
| `tests/Unit/Middleware/RequestLifecycleMiddlewareTest.php` | Lifecycle order with auth |
| `tests/Unit/Http/MiddlewareRegistrarTest.php` | Stack registration order (includes Auth middlewares) |
| `tests/Unit/Http/PageUriResolverTest.php` | URI candidates / `.html` |
| `tests/Unit/Http/Psr7RequestFactoryTest.php` | Request factory / query decoding / `api.php` fallback path |
| `tests/Unit/Services/MetaCatalogTest.php` | Endpoint catalog, OpenAPI security/tags, object-aware include/preview/force/include_deleted |
| `tests/Unit/Services/SwaggerUiServiceTest.php` | Docs HTML on/off |
| `tests/Unit/Services/ContextSettingsServiceTest.php` | Safe settings only, ACL |
| `tests/Unit/Services/TokenServiceTest.php` | OAuth client_credentials / disabled |
| `tests/Unit/Media/MediaUrlResolverTest.php` | Absolute URLs pass-through; relative + `site_url` |
| `tests/Unit/Idempotency/IdempotencyFingerprintTest.php` | Fingerprint / payload hash |
| `tests/Unit/Idempotency/IdempotencyStoreTest.php` | Store + lock |
| `tests/Unit/Webhook/WebhookEventFactoryTest.php` | ISR payload / revalidate tags |
| `tests/Unit/Webhook/WebhookDispatcherUrlTest.php` | SSRF URL policy |
| `tests/Unit/Audit/AuditLogRepositoryTest.php` | Prune criteria |
| `tests/Unit/Exception/ErrorCodeTest.php` | Stable `code` on problem details |
| `tests/Unit/ApplicationLifecycleTest.php` | Bootstrap core objects/relations, DI singleton |
| `tests/Unit/Routing/RouteDispatcherTest.php` | Missing route 404, create 201 |
| `tests/Security/InjectionTest.php` | Malicious filter/sort fields rejected |
| `tests/Security/ArbitraryClassTest.php` | Unregistered class / object denied; ExtensionApi relation guard |

Add a unit test when you change parsers, registry, middleware, or OpenAPI generation. Add a security test when you change filters, sorts, or object exposure.

### 1.2 Live e2e (`scripts/e2e-live.php`) — full check list

| Check | Expectation |
|-------|-------------|
| `discovery` | 200, `data.name=mxHeadless` |
| `health` | 200, `status=ok` |
| `schema` | 200, `objects.resources` present |
| `meta.endpoints` | 200, endpoint count > 5 |
| `meta.openapi` | 200, enveloped OpenAPI |
| `meta.openapi.json` | 200, raw OpenAPI 3.0.3 (no `data` envelope) |
| `docs.swagger` | 200 HTML, `SwaggerUIBundle`, links to `/meta/openapi.json` |
| `resources.filter` | 200, non-empty published list |
| `resources.sort` | 200, `-id` ≠ `id` order |
| `resources.include.parent` | 200 |
| `pages.by_uri` | 200 for sample published URI |
| `pages.index_alias_docs` | 200 or 404 |
| `contexts.no_auth` | 401 `token_required` |
| `chunks.no_auth` | 401 |
| `templates.no_auth` | 401 |
| `snippets.no_auth` | 401 `token_required` |
| `categories.no_auth` | 401 |
| `content_types.no_auth` | 401 |
| `tvs.no_auth` | 401 |
| `resources.include_deleted_anon` | 403 |
| `resources.sort_asc_alias` | 200 `id:asc` |
| `limits.uri_too_long` | 414 |
| `limits.body_too_large` | 413 (needs key) |
| `contexts.with_key` | 200 (needs key) |
| `contexts.settings` | 200 |
| `contexts.get_by_key` | 200, `key=web` |
| `chunks.with_key` | 200 |
| `templates.with_key` | 200 |
| `tvs.with_key` | 200 |
| `snippets.include.category` | 200 |
| `templates.filter.published_denied` | 422 |
| `categories.filter.parent` | 200 |
| `resources.protected_fields_denied` | 422 on `fields=createdby` |
| `accept.html_on_json_denied` | 406 |
| `accept.html_docs_ok` | 200 HTML `/docs` |
| `pagination.page_offset_conflict` | 422 |
| `openapi.element_includes` | Templates tag, include example, no include on content_types, `/contexts/{key}`, DELETE `force`, GET `include_deleted` |
| `objects.resources` | 200 |
| `resources.get` | 200 matching sample id |
| `oauth.token` | 200 `mxt_` (only if secret arg passed) |
| `oauth.resources` | 200 with token |
| `oauth.chunks_denied_scope` | 403 or 401 without chunks scope |
| `oauth.disabled_or_invalid` | 400/401/422 when OAuth unused |
| `options.preflight` | 204 |
| `options.with_origin` | 204 |
| `resources.create` | 201 |
| `idempotency.replay` | same status / replayed or same id |
| `resources.patch` | 200 updated title |
| `resources.delete` | 200/204 soft-delete |
| `resources.delete_twice_404` | 404 |
| `kill_switch.health_still_ok` | health 200 |
| `404.unknown` | 404 |
| `404.missing_resource` | 404 |
| `fallback.api_php.discovery` | 200 via assets `api.php` |
| `fallback.api_php.route_health` | 200 via `api.php?route=/v1/health` |

**Not in e2e (must use §2):** CSRF session mutate (unit covers), Extension Extra registration, Manager UI settings. Live `429` verified via `bin/api-key-create.php --rate-limit-max=…` (not in e2e to avoid polluting keys).

## 2. Manual verification matrix

Run after automatic gates are green on the target host.

### 2.1 Install and gateway

- [ ] Package installed / rebuilt; plugin **OnHandleRequest** enabled (priority early, default `-100`)
- [ ] Pretty URL `/api/v1/*` or `assets/components/mxheadless/api.php` fallback (`?route=/v1/health` on nginx without PATH_INFO)
- [ ] `GET /api/v1` returns name, version, links (resources, pages, elements, docs, openapi, auth_token)
- [ ] `GET /api/v1/health` → `database: true`
- [ ] Kill switch: `mxheadless_enabled=false` → only discovery/health; other routes → service disabled (`code=service_disabled`)
- [ ] `mxheadless_api_prefix` change still routes correctly
- [ ] `mxheadless_debug=false` → no stack traces / secrets in problem details

### 2.2 Discovery, schema, meta

- [ ] Schema lists `resources`, `contexts`, `chunks`, `templates`, `snippets`, `tvs`, `categories`, `content_types` with relations (`parent`/`children`, `category`, categories `parent`)
- [ ] Schema flags: resources creatable/updatable/deletable; elements read-only
- [ ] Users / arbitrary classes **not** exposed
- [ ] `meta/endpoints`: `path` + `pattern`, count matches live catalog
- [ ] Live `openapi.json` version = package version; top-level `tags` non-empty
- [ ] Static `docs/openapi.yaml` version bumped with release
- [ ] Diff live vs static paths: no unexpected ONLY LIVE / ONLY STATIC

### 2.3 Authentication and authorization

- [ ] Protected list without key → `401` `token_required`
- [ ] Bad bearer → `401` `invalid_token`
- [ ] `Authorization: Bearer mxh_…` and `X-API-Key` both work
- [ ] Scope missing → `403` `scope_denied`
- [ ] Public published `resources` / `pages` readable anonymously
- [ ] Unpublished without `preview` → 404; with preview scope → 200 (or 403 if scope missing)
- [ ] `include_deleted` anonymous → 403; with delete/update/preview scope → OK
- [ ] Cross-context `context=mgr` / `X-Context: mgr` without ACL → 403
- [ ] Invalid context → 422
- [ ] OAuth enabled: client_credentials → `mxt_`; password grant only if `oauth.password_grant_enabled`
- [ ] CLI `bin/oauth-client-create.php` / `bin/api-key-create.php` produce working credentials (bins resolve MODX root from Extra source tree and from installed `core/components/mxheadless/bin`)
- [ ] Session mutations from Manager require `X-CSRF-Token` (CSRF middleware)
- [ ] Session in Swagger: public GET not 403 solely due to mgr cookie
- [ ] Expired API key / OAuth token → rejected

### 2.4 Resources CRUD and visibility

- [ ] List: `filter`, `sort`, `fields`, `q`, `limit`, `page` **or** `offset` (not both)
- [ ] Filter shorthand `filter[published]=1` and operators `eq|neq|ne|gt|gte|lt|lte|like|in|not_in|null|not_null`
- [ ] Sort `-field`, `+field`, `field:asc` / `field:desc`
- [ ] `include=parent` / `children` / nested within depth; too many / too deep → 422
- [ ] `tv_fields=*` or named TVs; unknown TV names → empty `tvs` (no crash)
- [ ] POST → 201; missing `pagetitle` → 422; invalid JSON → 422; oversized body → rejected (`max_body_bytes`)
- [ ] Duplicate alias → 422
- [ ] PUT replace vs PATCH partial
- [ ] PATCH unknown field → 422; immutable `id` → 422
- [ ] Soft DELETE → 200 `{deleted:true,permanent:false}`; children marked deleted; second DELETE → **404**
- [ ] `DELETE ?force=1` permanent (including soft-deleted)
- [ ] Restore `PATCH {"deleted":0}`
- [ ] Idempotency replay; conflict → 409; response may expose `Idempotency-Replayed`
- [ ] Protected (`createdby`, …) / hidden (`properties`) in `fields=` without right → 422
- [ ] Pagination `links.self|next|prev` and `meta.total|has_more`

### 2.5 Pages by URI

- [ ] Matches MODX URI (`index`, `index.html`, nested)
- [ ] Missing → 404; traversal → 404; overlong URI → 414 (`max_uri_bytes`)
- [ ] Public GET: `ETag` + `Cache-Control`; `If-None-Match` → 304
- [ ] HEAD same ETag, empty body
- [ ] Preview unpublished page only with preview permission

### 2.6 Elements and types (read-only)

For each of `chunks`, `templates`, `snippets`, `tvs`, `categories`, `content_types`:

- [ ] No auth → 401 (e2e covers chunks/templates only; check the rest manually)
- [ ] With `*.read` → list + get by id
- [ ] Object-specific filters only
- [ ] `include=category` (elements) / `include=parent` (categories)
- [ ] Write on shortcut → 404; `/objects/{name}` without write → 403
- [ ] Hidden fields not readable (`properties`, TV input/output props, `headers`)

### 2.7 Contexts

- [ ] `GET /contexts`, `/contexts/{key}`, `/contexts/{key}/settings`
- [ ] Settings allowlist only (no secrets)
- [ ] OpenAPI `/contexts/{key}`
- [ ] `mxheadless_allowed_contexts` rejects other keys with 422

### 2.8 Generic `/objects` and Extension API

- [ ] `/objects/resources` mirrors `/resources`
- [ ] Unknown name → 404
- [ ] Mutations respect creatable/updatable/deletable
- [ ] Extra via Extension API: register object + relation + optional endpoint; appears in schema/OpenAPI; deny-by-default until registered

### 2.9 Query language edges

- [ ] Unknown operator / non-filterable / non-sortable → 422
- [ ] Too many `fields` (> `max_fields`) / `include` (> `max_include_relations`) / depth → 422
- [ ] `limit=0` / negative `offset` / invalid `page` → 422
- [ ] `page` + `offset` → 422
- [ ] `limit` / `offset` capped by `max_limit` / `max_offset`

### 2.10 Negotiation, CORS, headers, limits

- [ ] Accept JSON / `*/*` / openapi+json OK; HTML on JSON → 406; HTML on `/docs` → 200
- [ ] Wrong POST Content-Type → 415; `application/json; charset=utf-8` OK
- [ ] CORS: allowlisted Origin reflected; others not; credentials policy matches settings
- [ ] `X-Request-ID` echoed; `X-RateLimit-*` present
- [ ] Force rate limit → `429` `rate_limited`
- [ ] Errors: `application/problem+json` with `type`, `title`, `status`, `code`
- [ ] Trusted proxies: client IP / scheme correct behind LB when `trusted_proxies` set

### 2.11 Swagger UI (`/docs`)

- [ ] Loads (CDN assets); Authorize; Try it out
- [ ] Object-aware query params; tags (`Templates`, `TVs`, `ContentTypes`, …)
- [ ] Optional bearer: public GET still works after Authorize
- [ ] `mxheadless_swagger_enabled=false` → not found

### 2.12 Cache and performance

- [ ] Anonymous public GET may cache; authenticated / preview → `private, no-store`
- [ ] Clients use sparse `fields`
- [ ] Multi-node: shared cache backend if idempotency/cache enabled across app servers

### 2.13 Media

- [ ] Resource/TV file paths resolve to absolute media URLs (no raw filesystem paths)
- [ ] See [media](../api/media.md)

### 2.14 Webhooks / audit / workers (if used)

- [ ] Mutation → outbox row; `bin/webhook-worker.php` delivers
- [ ] `X-MxHeadless-Signature` (or documented signature header) present
- [ ] SSRF: private URL blocked unless `webhook.allow_private_urls`
- [ ] `bin/webhook-subscribe.php` registers subscriber
- [ ] Audit rows when enabled; `bin/audit-prune.php` with retention days
- [ ] ISR `meta.revalidate` tags usable by frontend

### 2.15 Build, lexicons, docs sync

- [ ] `_build/config.inc.php` version = `Version::STRING` = live discovery version = `docs/openapi.yaml` info.version
- [ ] RU + EN API / lexicon (`setting.inc.php`) updated for behavior or setting changes
- [ ] Manager system settings for mxHeadless visible after install
- [ ] [Production checklist](../operations/production-checklist.md) for go-live

## 3. Suggested run order

1. `composer test && composer phpstan && composer phpcs`
2. `cd _build && php build.php` on the target MODX
3. `php scripts/e2e-live.php https://HOST "$KEY"`
4. Manual **§2.1–2.11** (and **§2.13–2.14** if those features are on)
5. Production checklist before public traffic

## 4. Related

- [Production checklist](../operations/production-checklist.md)
- [Development](development.md)
- [Security review](security.md)
- [Releases](releases.md)
- [Errors](../api/errors.md)
- [Pagination](../api/pagination.md)
- [Media](../api/media.md)
- [Webhooks](../api/webhooks.md)
- [Limits](../configuration/limits.md)
