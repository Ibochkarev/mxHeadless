# Audit log

Опциональный журнал обращений к API в БД. По образцу mxApi, без тел запросов и ответов.

## Включение

| Настройка | По умолчанию | Описание |
|-----------|--------------|----------|
| `mxheadless_audit_enabled` | `false` | Главный переключатель |
| `mxheadless_audit_retention_days` | `90` | Срок хранения для `audit-prune.php` |
| `mxheadless_audit_log_get` | `false` | При `false` пишутся только `POST`, `PUT`, `PATCH`, `DELETE` |

При `false` middleware `AuditLogMiddleware` ничего не делает.

## Что сохраняется

Таблица `{prefix}mxheadless_api_log`:

| Колонка | Описание |
|---------|----------|
| `request_id` | Из `X-Request-ID` |
| `identity_key` | `api_key:{lookupId}`, `session:{userId}` или `anonymous` |
| `api_key_id` | FK на `mxheadless_api_keys.id` при bearer key |
| `method` | HTTP-метод |
| `path` | Путь без query string |
| `context_key` | Из `X-Context`, `?context=` или текущего контекста MODX |
| `status_code` | Итоговый HTTP-статус |
| `duration_ms` | Время обработки после старта audit |
| `created_on` | Unix timestamp |

## Чего нет в журнале

- Тела запроса и ответа
- Заголовок `Authorization` и секрет ключа
- CSRF, cookies, пароли
- Значения query string

См. [ADR 0008](../../decisions/0008-audit-log.md).

## Retention

Ежедневно (cron или systemd):

```bash
php core/components/mxheadless/bin/audit-prune.php
```

С `--days=30` переопределяет `mxheadless_audit_retention_days`.

## Примеры SQL

Мутации по ключу:

```sql
SELECT request_id, method, path, status_code, duration_ms, created_on
FROM modx_mxheadless_api_log
WHERE api_key_id = 3
ORDER BY created_on DESC
LIMIT 50;
```

Ошибки записи за последний час:

```sql
SELECT identity_key, path, status_code, request_id
FROM modx_mxheadless_api_log
WHERE method IN ('POST', 'PUT', 'PATCH', 'DELETE')
  AND status_code >= 400
  AND created_on > UNIX_TIMESTAMP() - 3600;
```

Связка с тикетами поддержки — по `request_id` из problem+json.

## Сбой записи

Если insert не удался, ответ API всё равно отдаётся. Ошибка пишется в MODX log.

## См. также

- [Logging](logging.md)
- [Workers](workers.md)
- [Settings](../configuration/settings.md)
