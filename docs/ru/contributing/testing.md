# Тестирование и чеклист проверок

Полная карта **автоматических** и **ручных** проверок mxHeadless. Используйте перед релизом, после rebuild на живом MODX или при закрытии бага.

Принцип: сначала gate (команда + exit code), потом «готово». Документация совпадает с live OpenAPI.

## Карта покрытия

| Слой | Где в этом файле | Пробелы |
|------|------------------|---------|
| PHPUnit unit/security | §1.1 (Cors, Csrf, BodyLimit, HttpCache, TrustedProxy, RequestId, Error, Routing, MediaUrlResolver) | — |
| Composer gates | §1 | — |
| Live e2e | §1.2 | CSRF session mutate (unit есть), live `429`, webhook delivery, Extension Extra, Manager UI |
| Ручная матрица | §2 | Обязательна для всего вне e2e |
| Production go-live | [production-checklist](../operations/production-checklist.md) | Ops, cron, CDN |

## 1. Автоматические gate (обязательны)

Из корня репозитория компонента. Для unit-сьютов установка MODX не нужна.

| Gate | Команда | Критерий |
|------|---------|----------|
| Unit + security PHPUnit | `composer test` | Exit 0, 0 failures |
| Static analysis | `composer phpstan` | Exit 0, без ошибок |
| Coding standard | `composer phpcs` | Exit 0 |
| Сборка transport | `cd _build && php build.php` | Install/update OK, cache clear OK |
| Live e2e (нужен перед релизом на реальный хост) | `php scripts/e2e-live.php https://HOST "$API_KEY" [oauth_client] [oauth_secret]` | Все `OK`, `0 failed` |

Версия после rebuild:

```bash
curl -s https://HOST/api/v1 | jq -r '.data.version'
```

Должна совпасть с `Version::STRING` и `_build/config.inc.php`.

### 1.1 PHPUnit suites

| Путь | Что покрывает |
|------|----------------|
| `tests/Unit/Registry/ObjectRegistryTest.php` | Register, overwrite, freeze, lookup |
| `tests/Unit/Query/QueryParserTest.php` | limit/offset/page conflict, filters, `ne`→`neq`, sort `field:asc`, include depth, include_deleted, context header |
| `tests/Unit/Query/XpdoQueryCompilerTest.php` | Whitelist select/filter/sort, bound params, boolean filters, count |
| `tests/Unit/Authorization/AuthorizerContextTest.php` | Context ACL |
| `tests/Unit/Authorization/AuthorizerEndpointTest.php` | Public GET scopes, session vs API key, HEAD anonymous |
| `tests/Unit/Authentication/CredentialExpiryTest.php` | Expiry API key / OAuth |
| `tests/Unit/Authentication/IdentityAllowsTest.php` | Wildcard scope, session permissions |
| `tests/Unit/Authentication/OAuthTokenAuthenticatorTest.php` | `mxt_` bearer |
| `tests/Unit/Middleware/ContentNegotiationMiddlewareTest.php` | JSON/form CT, HTML только `/docs`, openapi+json, 406/415 |
| `tests/Unit/Middleware/CorsMiddlewareTest.php` | OPTIONS/GET allowlist Origin |
| `tests/Unit/Middleware/CsrfMiddlewareTest.php` | Session mutate + `X-CSRF-Token`; API key bypass |
| `tests/Unit/Middleware/BodyLimitMiddlewareTest.php` | URI 414, body 413 |
| `tests/Unit/Middleware/RequestIdMiddlewareTest.php` | Echo / generate `X-Request-ID` |
| `tests/Unit/Middleware/TrustedProxyMiddlewareTest.php` | `client_ip` из `X-Forwarded-For` только у trusted |
| `tests/Unit/Middleware/ErrorMiddlewareTest.php` | HttpException + debug on/off для 500 |
| `tests/Unit/Middleware/HttpCacheMiddlewareTest.php` | public ETag/304, private no-store, HEAD |
| `tests/Unit/Middleware/RoutingMiddlewareTest.php` | Match → route attrs; unknown → 404 |
| `tests/Unit/Middleware/RateLimitMiddlewareTest.php` | Лимиты, headers, 429 |
| `tests/Unit/Middleware/IdempotencyMiddlewareTest.php` | Replay vs conflict |
| `tests/Unit/Middleware/ServiceGateMiddlewareTest.php` | Kill switch |
| `tests/Unit/Middleware/AuditLogMiddlewareTest.php` | Audit on/off, GET logging |
| `tests/Unit/Middleware/RequestLifecycleMiddlewareTest.php` | Порядок lifecycle + auth |
| `tests/Unit/Http/MiddlewareRegistrarTest.php` | Порядок стека (включая Auth) |
| `tests/Unit/Http/PageUriResolverTest.php` | URI candidates / `.html` |
| `tests/Unit/Http/Psr7RequestFactoryTest.php` | Фабрика request / query / fallback `api.php` |
| `tests/Unit/Services/MetaCatalogTest.php` | Catalog, OpenAPI tags/security, include/preview/force/include_deleted |
| `tests/Unit/Services/SwaggerUiServiceTest.php` | Docs HTML on/off |
| `tests/Unit/Services/ContextSettingsServiceTest.php` | Safe settings, ACL |
| `tests/Unit/Services/TokenServiceTest.php` | OAuth client_credentials / disabled |
| `tests/Unit/Media/MediaUrlResolverTest.php` | Absolute URL; relative + `site_url` |
| `tests/Unit/Idempotency/*` | Fingerprint, store, lock |
| `tests/Unit/Webhook/*` | ISR tags, SSRF URL policy |
| `tests/Unit/Audit/AuditLogRepositoryTest.php` | Prune |
| `tests/Unit/Exception/ErrorCodeTest.php` | Стабильный `code` |
| `tests/Unit/ApplicationLifecycleTest.php` | Bootstrap объектов/relations, DI |
| `tests/Unit/Routing/RouteDispatcherTest.php` | 404 / 201 |
| `tests/Security/InjectionTest.php` | Вредоносные filter/sort |
| `tests/Security/ArbitraryClassTest.php` | Незарегистрированный object; guard ExtensionApi |

Unit test — при смене parser/registry/middleware/OpenAPI. Security test — при filters/sorts/exposure.

### 1.2 Live e2e (`scripts/e2e-live.php`) — полный список

| Check | Ожидание |
|-------|----------|
| `discovery` | 200, `data.name=mxHeadless` |
| `health` | 200, `status=ok` |
| `schema` | 200, есть `objects.resources` |
| `meta.endpoints` | 200, count > 5 |
| `meta.openapi` | 200, OpenAPI в envelope |
| `meta.openapi.json` | 200, raw 3.0.3 без `data` |
| `docs.swagger` | 200 HTML, `SwaggerUIBundle`, ссылка на openapi.json |
| `resources.filter` | 200, непустой published list |
| `resources.sort` | 200, порядок `-id` ≠ `id` |
| `resources.include.parent` | 200 |
| `pages.by_uri` | 200 для sample URI |
| `pages.index_alias_docs` | 200 или 404 |
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
| `limits.body_too_large` | 413 (нужен key) |
| `contexts.with_key` | 200 |
| `contexts.settings` | 200 |
| `contexts.get_by_key` | 200, `key=web` |
| `chunks.with_key` | 200 |
| `templates.with_key` | 200 |
| `tvs.with_key` | 200 |
| `snippets.include.category` | 200 |
| `templates.filter.published_denied` | 422 |
| `categories.filter.parent` | 200 |
| `resources.protected_fields_denied` | 422 на `fields=createdby` |
| `accept.html_on_json_denied` | 406 |
| `accept.html_docs_ok` | 200 HTML `/docs` |
| `pagination.page_offset_conflict` | 422 |
| `openapi.element_includes` | tag Templates, include example, нет include у content_types, `/contexts/{key}`, DELETE `force`, GET `include_deleted` |
| `objects.resources` | 200 |
| `resources.get` | 200 |
| `oauth.token` | 200 `mxt_` (если передан secret) |
| `oauth.resources` | 200 |
| `oauth.chunks_denied_scope` | 403/401 |
| `oauth.disabled_or_invalid` | 400/401/422 без OAuth |
| `options.preflight` / `with_origin` | 204 |
| `resources.create` | 201 |
| `idempotency.replay` | replay / same id |
| `resources.patch` | 200 |
| `resources.delete` | 200/204 |
| `resources.delete_twice_404` | 404 |
| `kill_switch.health_still_ok` | health 200 |
| `404.unknown` / `404.missing_resource` | 404 |
| `fallback.api_php.discovery` | 200 через assets `api.php` |
| `fallback.api_php.route_health` | 200 через `api.php?route=/v1/health` |

**Нет в e2e (только §2):** CSRF session mutate (unit есть), Extension Extra, Manager UI. Live `429` проверяется через `bin/api-key-create.php --rate-limit-max=…` (в e2e не кладём, чтобы не плодить ключи).

## 2. Ручная матрица

После зелёных automatic gates на целевом хосте.

### 2.1 Установка и gateway

- [ ] Пакет установлен/пересобран; плагин **OnHandleRequest** включён (ранний priority, default `-100`)
- [ ] Pretty URL `/api/v1/*` или fallback `api.php` / `api.php?route=/v1/health`
- [ ] `GET /api/v1` — name, version, links (resources, pages, elements, docs, openapi, auth_token)
- [ ] `GET /api/v1/health` → `database: true`
- [ ] Kill switch `mxheadless_enabled=false` → только discovery/health; иначе `service_disabled`
- [ ] Смена `mxheadless_api_prefix` не ломает routing
- [ ] `mxheadless_debug=false` → нет stack traces / секретов в ошибках

### 2.2 Discovery, schema, meta

- [ ] Schema: все core objects + relations (`parent`/`children`, `category`, categories `parent`)
- [ ] Flags: resources writable; elements read-only; users не в schema
- [ ] `meta/endpoints`: `path` + `pattern`
- [ ] Live `openapi.json` version = пакет; top-level `tags` не пустой
- [ ] Static `docs/openapi.yaml` обновлён с релизом
- [ ] Diff live vs static paths без сюрпризов

### 2.3 Auth и authorization

- [ ] Без ключа → `401` `token_required`; плохой bearer → `invalid_token`
- [ ] Работают Bearer и `X-API-Key`
- [ ] Нет scope → `403` `scope_denied`
- [ ] Анонимно: published resources/pages
- [ ] Unpublished / `include_deleted` / preview — по правилам scope
- [ ] Cross-context и `X-Context` / `allowed_contexts` → 403/422
- [ ] OAuth client_credentials / password grant по настройкам
- [ ] CLI `bin/api-key-create.php`, `bin/oauth-client-create.php` (корневой MODX находится и из Extra-дерева, и из installed `core/components/mxheadless/bin`)
- [ ] Session mutations: `X-CSRF-Token`
- [ ] Swagger + mgr cookie: public GET без лишнего 403
- [ ] Истёкший ключ/токен отклоняется

### 2.4 Resources CRUD и visibility

- [ ] `filter` / `sort` / `fields` / `q` / `limit` / `page` **или** `offset`
- [ ] Shorthand `filter[published]=1` и полный набор операторов
- [ ] Sort `-` / `+` / `field:asc|desc`
- [ ] Includes и лимиты depth/count → 422 при превышении
- [ ] `tv_fields`; неизвестные имена не роняют API
- [ ] POST 201; валидация; body > `max_body_bytes` → отказ
- [ ] Дубликат alias → 422
- [ ] PUT vs PATCH
- [ ] Soft DELETE + дерево детей; второй DELETE → **404**; `force=1`; restore `deleted:0`
- [ ] Idempotency replay / 409; заголовок `Idempotency-Replayed`
- [ ] Protected / hidden fields → 422
- [ ] `links` / `meta` пагинации

### 2.5 Pages по URI

- [ ] Совпадение URI; 404; traversal; URI > `max_uri_bytes` → 414
- [ ] ETag / 304 / HEAD
- [ ] Preview unpublished только с правом

### 2.6 Элементы и типы (read-only)

Для `chunks`, `templates`, `snippets`, `tvs`, `categories`, `content_types`:

- [ ] 401 без auth (e2e только chunks/templates — остальное вручную)
- [ ] `*.read` → list + get
- [ ] Свои filters; `include=category` / `parent`
- [ ] Write shortcut → 404; `/objects/...` без write → 403
- [ ] Hidden fields закрыты

### 2.7 Contexts

- [ ] List / `{key}` / settings без секретов
- [ ] OpenAPI `{key}`; whitelist contexts

### 2.8 Generic `/objects` и Extension API

- [ ] Зеркало shortcut’ов; unknown → 404
- [ ] creatable/updatable/deletable
- [ ] Extra: register object/relation/endpoint → schema/OpenAPI; deny-by-default

### 2.9 Query language

- [ ] Неизвестный op / non-filterable / non-sortable → 422
- [ ] Лимиты `max_fields` / `max_include_*` / `max_limit` / `max_offset`
- [ ] `page`+`offset` → 422

### 2.10 Negotiation, CORS, headers, limits

- [ ] Accept / Content-Type матрица (406/415)
- [ ] CORS allowlist; credentials
- [ ] `X-Request-ID`, `X-RateLimit-*`; принудительный `429`
- [ ] `application/problem+json` + `code`
- [ ] Trusted proxies за LB

### 2.11 Swagger UI (`/docs`)

- [ ] UI, Authorize, Try it out, tags, object-aware params
- [ ] `swagger.enabled=false` → недоступен

### 2.12 Cache и performance

- [ ] Anonymous cache vs `private, no-store`
- [ ] Sparse `fields`; shared cache при multi-node + idempotency

### 2.13 Media

- [ ] Пути → абсолютные media URL, без filesystem paths ([media](../api/media.md))

### 2.14 Webhooks / audit / workers

- [ ] Outbox; `bin/webhook-worker.php`; подпись; SSRF
- [ ] `bin/webhook-subscribe.php`
- [ ] Audit + `bin/audit-prune.php`
- [ ] ISR tags для фронта

### 2.15 Сборка, лексиконы, docs

- [ ] Версии: build = `Version::STRING` = discovery = openapi.yaml
- [ ] RU + EN docs / lexicon settings
- [ ] Settings видны в Manager
- [ ] [Production checklist](../operations/production-checklist.md)

## 3. Порядок прогона

1. `composer test && composer phpstan && composer phpcs`
2. `cd _build && php build.php` на целевом MODX
3. `php scripts/e2e-live.php https://HOST "$KEY"`
4. Ручные **§2.1–2.11** (и **§2.13–2.14** при включённых фичах)
5. Production checklist перед публичным трафиком

## 4. См. также

- [Production checklist](../operations/production-checklist.md)
- [Development](development.md)
- [Security review](security.md)
- [Releases](releases.md)
- [Errors](../api/errors.md)
- [Pagination](../api/pagination.md)
- [Media](../api/media.md)
- [Webhooks](../api/webhooks.md)
- [Limits](../configuration/limits.md)
