# Мониторинг

Используйте лёгкие HTTP-probe и проверки таблиц webhook и audit. Отдельного metrics exporter mxHeadless не поставляет.

## Health probe

`GET /api/v1/health` без auth. Опросите его с балансировщика или uptime-сервиса.

| `data.status` | Значение |
|---------------|----------|
| `ok` | База ответила на `SELECT 1` |
| `degraded` | База недоступна |

Health не проверяет cache, диск и background workers. См. [health endpoint](../api/health.md).

Интервал probe: 30–60 секунд. Алерт на серию ошибок или HTTP ≠ 200.

## Smoke test discovery

```bash
curl -sf https://your-site.example/api/v1/health
curl -sf https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
```

Резкое падение числа endpoints после деплоя может означать, что плагин не поднял bootstrap.

## Заголовки rate limit

Успешные ответы могут содержать:

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1693142460
```

Всплеск `429` указывает на abuse или retry loop клиента. Подробнее: [rate limiting](../api/rate-limiting.md).

## Webhook outbox

При мутациях с webhooks смотрите `mxheadless_webhook_deliveries`:

```sql
SELECT status, COUNT(*) AS cnt
FROM modx_mxheadless_webhook_deliveries
GROUP BY status;
```

Обратите внимание на:

- Рост `pending` (worker не работает или subscriber недоступен)
- Строки в `failed` после `mxheadless_webhook_max_attempts`
- `Processed 0 webhook(s)` в логе при активной публикации контента

Runbook: [workers](workers.md).

## Запросы к audit log

При включённом audit:

```sql
SELECT request_id, path, status_code, identity_key, created_on
FROM modx_mxheadless_api_log
WHERE status_code >= 400
ORDER BY created_on DESC
LIMIT 20;
```

Ещё примеры: [audit log](audit-log.md).

## Kill switch

`mxheadless_enabled` = `false` отдаёт `503` с `code: service_disabled` на всех маршрутах кроме discovery и health. Аварийная остановка без удаления пакета.

## См. также

- [Production checklist](production-checklist.md)
- [Logging](logging.md)
- [Incident response](incident-response.md)
- [Troubleshooting](troubleshooting.md)
