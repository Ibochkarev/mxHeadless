# Справочник настроек

Настройки в namespace `mxheadless` в System Settings MODX.

## API

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.api.prefix` | `/api` | Префикс URL для плагина gateway |

## Основные

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.debug` | `false` | Детали исключений в problem+json |
| `mxheadless.enabled` | `true` | Kill switch. При `false` доступны только `GET /` и `GET /health`; остальное под `/api/v1` → `503` с `code: service_disabled` |

## Кэш

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.cache.enabled` | `true` | Кэш ответов в middleware |
| `mxheadless.cache_ttl` | `300` | `max-age` для публичных GET (секунды) |

## Rate limit

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.rate_limit.enabled` | `true` | Включить лимитер |
| `mxheadless.rate_limit.max_requests` | `120` | Запросов на окно (глобальный default) |
| `mxheadless.rate_limit.window_seconds` | `60` | Длина окна (глобальный default) |

Per-key: nullable колонки `rate_limit_max` и `rate_limit_window` в `mxheadless_api_keys`.

## Audit

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.audit.enabled` | `false` | Журнал обращений в `mxheadless_api_log` |
| `mxheadless.audit.retention_days` | `90` | Retention для `audit-prune.php` |
| `mxheadless.audit.log_get` | `false` | Логировать `GET` при `true` |

См. [audit log](../operations/audit-log.md).

## OAuth

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.oauth.enabled` | `false` | Включить `POST /auth/token` |
| `mxheadless.oauth.token_ttl` | `3600` | TTL access token (секунды) |
| `mxheadless.oauth.password_grant_enabled` | `false` | Разрешить `grant_type=password` |

См. [auth](../api/auth.md).

## CORS

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.cors.enabled` | `false` | Middleware CORS |
| `mxheadless.cors.allowed_origins` | пусто | Origins через запятую |
| `mxheadless.cors.allowed_methods` | GET,POST,... | Разрешённые методы |
| `mxheadless.cors.allowed_headers` | Authorization,... | Разрешённые заголовки |
| `mxheadless.cors.expose_headers` | ETag,X-Request-ID,... | Заголовки, видимые из JS |
| `mxheadless.cors.allow_credentials` | `false` | `Access-Control-Allow-Credentials` |

## Безопасность

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.max_body_bytes` | `1048576` | Макс. размер JSON body |
| `mxheadless.max_uri_bytes` | `2048` | Макс. длина URI |
| `mxheadless.trusted_proxies` | пусто | IP, которым доверяем forwarded headers |
| `mxheadless.csrf.enabled` | `true` | CSRF для мутаций по сессии |

## Idempotency

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.idempotency.enabled` | `true` | Обрабатывать `Idempotency-Key` на `POST` |
| `mxheadless.idempotency_ttl` | `86400` | TTL кэша ответа (секунды) |

См. [idempotency](../api/idempotency.md).

## Webhooks

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.webhook.max_attempts` | `5` | Попыток доставки до статуса `failed` |
| `mxheadless.webhook.worker_limit` | `50` | Дефолт `--limit` для `webhook-worker.php` |
| `mxheadless.webhook.allow_private_urls` | `false` | Разрешить private/localhost/`.test` URL (только для dev) |

См. [webhooks](../api/webhooks.md) и [workers](../operations/workers.md).

## Лимиты query (дефолты в коде)

`QueryParser` читает их через `getOption`, если нет system setting:

| Ключ | По умолчанию |
|------|--------------|
| `mxheadless.max_limit` | 100 |
| `mxheadless.max_offset` | 100000 |
| `mxheadless.max_fields` | 50 |
| `mxheadless.max_include_relations` | 10 |
| `mxheadless.max_include_depth` | 2 |
| `mxheadless.allowed_contexts` | `web,mgr` |

Можно добавить как system settings в своём билде.

## См. также

- [Лимиты](limits.md)
- [CORS](cors.md)
- [Доверенные прокси](trusted-proxies.md)
