# Справочник настроек

Настройки в namespace `mxheadless` в System Settings MODX. Ключи через подчёркивание (`mxheadless_cors_enabled`), без точек.

С 1.0.42 при upgrade пакет переносит значения со старых dotted-ключей на новые.

## API

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_api_prefix` | `/api` | Префикс URL для плагина gateway |

## Основные

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_debug` | `false` | Детали исключений в problem+json |
| `mxheadless_enabled` | `true` | Kill switch. При `false` доступны только `GET /` и `GET /health`; остальное под `/api/v1` → `503` с `code: service_disabled` |
| `mxheadless_swagger_enabled` | `true` | Swagger UI на `GET /docs`. Сырой OpenAPI JSON остаётся доступен при отключении |

## Кэш

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_cache_enabled` | `true` | Кэш ответов в middleware |
| `mxheadless_cache_ttl` | `300` | `max-age` для публичных GET (секунды) |

## Rate limit

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_rate_limit_enabled` | `true` | Включить лимитер |
| `mxheadless_rate_limit_max_requests` | `120` | Запросов на окно (глобальный default) |
| `mxheadless_rate_limit_window_seconds` | `60` | Длина окна (глобальный default) |

Per-key: nullable колонки `rate_limit_max` и `rate_limit_window` в `mxheadless_api_keys`.

## Audit

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_audit_enabled` | `false` | Журнал обращений в `mxheadless_api_log` |
| `mxheadless_audit_retention_days` | `90` | Retention для `audit-prune.php` |
| `mxheadless_audit_log_get` | `false` | Логировать `GET` при `true` |

См. [audit log](../operations/audit-log.md).

## OAuth

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_oauth_enabled` | `false` | Включить `POST /auth/token` |
| `mxheadless_oauth_token_ttl` | `3600` | TTL access token (секунды) |
| `mxheadless_oauth_password_grant_enabled` | `false` | Разрешить `grant_type=password` |

См. [auth](../api/auth.md).

## CORS

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_cors_enabled` | `false` | Middleware CORS |
| `mxheadless_cors_allowed_origins` | пусто | Origins через запятую |
| `mxheadless_cors_allowed_methods` | GET,POST,... | Разрешённые методы |
| `mxheadless_cors_allowed_headers` | Authorization,... | Разрешённые заголовки |
| `mxheadless_cors_expose_headers` | ETag,X-Request-ID,... | Заголовки, видимые из JS |
| `mxheadless_cors_allow_credentials` | `false` | `Access-Control-Allow-Credentials` |

## Безопасность

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_max_body_bytes` | `1048576` | Макс. размер JSON body |
| `mxheadless_max_uri_bytes` | `2048` | Макс. длина URI |
| `mxheadless_trusted_proxies` | пусто | IP, которым доверяем forwarded headers |
| `mxheadless_csrf_enabled` | `true` | CSRF для мутаций по сессии |

## Idempotency

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_idempotency_enabled` | `true` | Обрабатывать `Idempotency-Key` на `POST` |
| `mxheadless_idempotency_ttl` | `86400` | TTL кэша ответа (секунды) |

См. [idempotency](../api/idempotency.md).

## Webhooks

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless_webhook_max_attempts` | `5` | Попыток доставки до статуса `failed` |
| `mxheadless_webhook_worker_limit` | `50` | Дефолт `--limit` для `webhook-worker.php` |
| `mxheadless_webhook_allow_private_urls` | `false` | Разрешить private/localhost/`.test` URL (только для dev) |

См. [webhooks](../api/webhooks.md) и [workers](../operations/workers.md).

## Лимиты query (дефолты в коде)

`QueryParser` читает их через `getOption`, если нет system setting:

| Ключ | По умолчанию |
|------|--------------|
| `mxheadless_max_limit` | 100 |
| `mxheadless_max_offset` | 100000 |
| `mxheadless_max_fields` | 50 |
| `mxheadless_max_include_relations` | 10 |
| `mxheadless_max_include_depth` | 2 |
| `mxheadless_allowed_contexts` | `web,mgr` |

Можно добавить как system settings в своём билде.

## См. также

- [Лимиты](limits.md)
- [CORS](cors.md)
- [Доверенные прокси](trusted-proxies.md)
