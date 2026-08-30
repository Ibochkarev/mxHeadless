# mxHeadless vs mxApi

Сравнение для выбора между [mxApi](https://docs.modx.pro/components/mxapi/) и mxHeadless на **MODX Revolution 3**. Поддержка MODX 2 для mxHeadless не рассматривается.

## В одном предложении

| Пакет | Роль |
|-------|------|
| **mxApi** | Транспорт API: маршруты, токены, scope, аудит, живой OpenAPI. Бизнес-эндпоинты добавляют провайдеры. |
| **mxHeadless** | Headless content API: ресурсы, страницы, query, webhooks, ISR. Инфраструктура дорабатывается по образцу mxApi там, где это помогает интеграторам. |

## Позиционирование

**mxApi** отвечает на вопрос: «Как безопасно отдать *любой* HTTP API поверх MODX?»

Из коробки только служебные маршруты: выпуск/отзыв токена, каталог, OpenAPI. Заказы, пользователи, свой CRUD — в других пакетах или коде сайта.

**mxHeadless** отвечает на вопрос: «Как кормить Nuxt/Next/SvelteKit JSON из MODX?»

В комплекте: `resources`, `pages`, `contexts`, `chunks`, whitelist query, relations, TVs, preview, webhooks с ISR-тегами и Extension API для Extras.

## Матрица возможностей (MODX 3)

| Область | mxApi | mxHeadless |
|---------|-------|------------|
| Версии MODX | 2.6+ и 3.0+ | только 3.2.3+ |
| Префикс по умолчанию | `/mxapi/v1` | `/api/v1` |
| Content API из коробки | нет | да |
| Bearer | непрозрачные токены в БД (`sha256`) | API keys `mxh_*` + OAuth `mxt_*` + сессия + anonymous |
| Token endpoint | `POST /auth/token` | `POST /auth/token` при `mxheadless.oauth.enabled` |
| Scope + MODX ACL | да (политики `mxapi`) | да (scope ключа/токена + права MODX) |
| Rate limit | на клиента интеграции | глобально + per-key + per OAuth client |
| Idempotency | `Idempotency-Key` | `Idempotency-Key` на `POST` (кэш только `2xx`) |
| Audit log | таблица `modx_mxapi_log` | таблица `{prefix}mxheadless_api_log` (опционально) |
| OpenAPI | живой `/meta/openapi` | static YAML + live `/meta/openapi` |
| Админка | Vue-каталог (VueTools на MODX 3) | Components → mxHeadless (API keys) + System Settings |
| Webhooks / ISR | зона провайдера | outbox + `meta.revalidate` |
| Query (`filter`, `include`) | зона провайдера | whitelist query engine в core |
| Страница по URI | зона провайдера | `GET /pages/{uri}` |
| Расширение | `mxApiOnRegisterEndpoints`, middleware hooks | `OnMxHeadlessRegister`, lifecycle hooks, `registerObject`, `registerEndpoint` |

## Когда выбирать mxHeadless

- Headless-сайт на MODX 3: контент, меню, контексты, preview
- Нужны filter, pagination, relations без своих SQL-эндпоинтов
- ISR через webhooks и cache tags для Next.js или Nuxt
- OSS без VueTools в core
- Один пакет «MODX → JSON» с deny-by-default registry

## Когда выбирать mxApi

- Партнёрский или внутренний API с кастомными маршрутами (заказы, CRM, импорт)
- Нужен token endpoint и machine clients без заранее выданных статических ключей
- Audit log в БД обязателен с первого дня
- Другие Extras регистрируются как провайдеры mxApi
- Сейчас MODX 2 или важна миграция 2→3 с сохранением токенов

## Оба пакета на одном сайте

Технически возможно (`/mxapi/v1` и `/api/v1`), но обычно лишнее:

- два gateway дублируют auth, лимиты и документацию
- интеграторы путают префиксы и типы credentials

Выберите один публичный API-слой. mxHeadless — для фронта. mxApi — только если отдельная интеграционная поверхность обязательна, и зафиксируйте, какой URL для каких клиентов.

## Что mxHeadless перенимает из mxApi

См. [roadmap](../roadmap/mxapi-adoption.md). Реализовано по фазам 1–3:

1. Kill switch, стабильные error codes, `/meta/endpoints`, `/meta/openapi`
2. Audit log, per-key rate limits, метаданные эндпоинтов
3. OAuth token endpoint и lifecycle hooks (выключены по умолчанию, см. [auth](../api/auth.md))

## Что не переносим

| mxApi | Причина |
|-------|---------|
| Линия MODX 2 | explicit non-goal |
| Только транспорт без content API | другой продукт |
| Префикс `/mxapi/v1` | ломает контракт |
| VueTools admin в core | тяжёлая зависимость |
| Только tokens вместо API keys | ключи удобны для CI/build; tokens — дополнение |

## См. также

- [Документация mxApi](https://docs.modx.pro/components/mxapi/)
- [Архитектура mxHeadless](../architecture.md)
- [Roadmap переноса](../roadmap/mxapi-adoption.md)
- [ADR: Live OpenAPI](../../decisions/0007-live-openapi.md)
- [ADR: Audit log](../../decisions/0008-audit-log.md)
- [ADR: OAuth tokens](../../decisions/0009-oauth-tokens.md)
