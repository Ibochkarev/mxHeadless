# Roadmap: перенос практик mxApi

Что mxHeadless (MODX 3) перенимает из [mxApi](https://docs.modx.pro/components/mxapi/) для инфраструктуры интеграций, не превращаясь в transport-only gateway.

**Статус:** Фазы 1–3 реализованы в коде (фаза 3 выключена настройками по умолчанию).

**Сравнение:** [mxHeadless vs mxApi](../comparison/mxapi.md)

## Уже есть (не дублировать)

| Практика mxApi | mxHeadless сейчас |
|----------------|-------------------|
| Bearer + scope + MODX ACL | `ApiKeyAuthenticator`, `Authorizer` |
| Rate limiting | `RateLimitMiddleware` (identity + IP) |
| Idempotency | `IdempotencyMiddleware` (`POST`, только `2xx`) |
| CORS | `CorsMiddleware` |
| Реестр провайдеров | `ObjectRegistry`, `OnMxHeadlessRegister` |
| OpenAPI | статический `docs/openapi.yaml`, `GET /schema` |
| Отзыв credentials | `revoked`, `expires_on` у API keys |

## Фаза 1 — Quick wins

Низкий риск, высокая польза для ops и интеграторов.

### 1.1 Глобальный kill switch

**Настройка:** `mxheadless_enabled` (по умолчанию `true`)

При `false` gateway отдаёт `503` с кодом `service_disabled`. Как `mxapi.enabled` у mxApi.

**Контракт:** `GET /health` и discovery остаются для балансировщика или весь `/api/v1` уходит в 503. Зафиксировать в [settings](../configuration/settings.md) до реализации.

**Файлы:** `Application` или первый middleware, [production checklist](../operations/production-checklist.md).

### 1.2 Стабильные machine-readable error codes

Поле `code` в RFC 9457 рядом с `type` и `title`.

| Code | HTTP | Когда |
|------|------|-------|
| `service_disabled` | 503 | API выключен |
| `token_required` | 401 | Защищённый маршрут без credentials |
| `scope_denied` | 403 | Нет scope |
| `rate_limited` | 429 | Лимит исчерпан |
| `idempotency_conflict` | 409 | Параллельный idempotent `POST` |

Обновить [errors](../api/errors.md) и smoke-тесты в [production checklist](../operations/production-checklist.md).

### 1.3 Живой каталог и OpenAPI

**Маршруты:**

```
GET /api/v1/meta/endpoints   — список из RouteCollection
GET /api/v1/meta/openapi     — OpenAPI 3.0 JSON из routes + ObjectRegistry
```

**Сервисы:** `EndpointCatalogService`, `OpenApiGenerator`

**Регистрация:** `RoutesRegistrar`

Статический `docs/openapi.yaml` остаётся для CI в репозитории. На боевом сайте source of truth — `/meta/openapi`. См. [ADR 0007](../decisions/0007-live-openapi.md).

### 1.4 Документ сравнения

Готово: [comparison/mxapi.md](../comparison/mxapi.md).

### Gate фазы 1

- [x] `meta/openapi` отражает зарегистрированные маршруты на live-инсталле
- [x] Smoke curl из production checklist проходят
- [x] Поле `code` на документированных ошибках

---

## Фаза 2 — Операционная зрелость

### 2.1 Журнал обращений (audit log)

Таблица метаданных запросов (без body). По образцу `modx_mxapi_log`.

| Колонка | Назначение |
|---------|------------|
| `request_id` | Из `RequestIdMiddleware` |
| `identity_key` | lookup ключа, id сессии или `anonymous` |
| `method`, `path` | HTTP |
| `context_key` | Контекст MODX |
| `status_code` | Статус ответа |
| `duration_ms` | Время handler |
| `api_key_id` | Nullable FK |
| `created_on` | Timestamp |

**Настройки:** `mxheadless_audit_enabled`, `mxheadless_audit_retention_days`, sampling для GET.

**Middleware:** `AuditLogMiddleware` после dispatch.

См. [ADR 0008](../../decisions/0008-audit-log.md). Док: [audit-log](../operations/audit-log.md).

### 2.2 Per-key rate limits

Расширить `mxheadless_api_keys`:

- `rate_limit_max` (nullable → глобальный дефолт)
- `rate_limit_window` (nullable → глобальный дефолт)

`RateLimitMiddleware` читает лимит из записи ключа.

### 2.3 Метаданные `registerEndpoint`

Расширить `Route` и `ExtensionApi::registerEndpoint()`:

- `description`, `tags`
- `parameters[]` (`name`, `in`, `schema`, `required`)

Питает `meta/endpoints` и `meta/openapi`. Метаданные в PHP у Extras, без дублирования YAML.

### Gate фазы 2

- [x] Audit пишется на мутации (и sampled GET)
- [x] Per-key limit покрыт тестом
- [x] Retention и privacy задокументированы

---

## Фаза 3 — UX интегратора (опционально)

Только по запросу интеграторов.

### 3.1 Token endpoint (дополнение, не замена)

```
POST /api/v1/auth/token
  grant_type=client_credentials
  grant_type=password   (только trusted clients)
```

Таблицы: `mxheadless_oauth_clients`, `mxheadless_oauth_tokens` (хэши секретов и токенов).

Статические `mxh_*` keys остаются для CI. Короткоживущие tokens — для ротации без redeploy.

**Риск:** scope creep, обязателен security review.

### 3.2 Хуки жизненного цикла

| Событие | Когда |
|---------|-------|
| `OnMxHeadlessRegisterMiddleware` | До freeze pipeline |
| `OnMxHeadlessBeforeRequest` | После auth, до dispatch |
| `OnMxHeadlessAfterRequest` | После response, до emit |

Аналог mxApi middleware/request hooks. PSR-15 pipeline не ломаем.

### 3.3 Админ-каталог (отложено)

Vue-админка mxApi (VueTools) **не планируется в core** mxHeadless.

При необходимости — optional extra `mxheadless-admin`. Для инструментов достаточно JSON `meta/endpoints` и `meta/openapi`.

### Gate фазы 3

- [x] Token flow покрыт unit-тестами (`client_credentials`, disabled endpoint)
- [x] Хуки задокументированы, lifecycle middleware в стабильном порядке

---

## Явные non-goals

| mxApi | Почему не берём |
|-------|-----------------|
| MODX 2 / линия 1.x | mxHeadless только 3.2.3+ |
| Только транспорт | Противоречит headless-миссии |
| Префикс `/mxapi/v1` | Breaking change |
| Только tokens вместо keys | Keys остаются |
| VueTools UI в core | Тяжёлая зависимость |
| mxApi + mxHeadless как два gateway | Дублирование ops |

## ADR

| ADR | Тема |
|-----|------|
| [0007](../decisions/0007-live-openapi.md) | Runtime OpenAPI vs static YAML |
| [0008](../../decisions/0008-audit-log.md) | Audit log |
| [0009](../../decisions/0009-oauth-tokens.md) | OAuth tokens |

## См. также

- [Архитектура](../architecture.md)
- [Расширения](../extensions/overview.md)
- [Документация mxApi](https://docs.modx.pro/components/mxapi/)
