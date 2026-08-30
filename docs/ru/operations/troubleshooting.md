# Диагностика

Типичные сбои mxHeadless и куда смотреть в первую очередь.

## 404 на `/api/v1/*`

| Причина | Исправление |
|---------|-------------|
| Плагин выключен | Включите mxHeadless в Manager |
| Неверный prefix | Проверьте `mxheadless.api.prefix` (по умолчанию `/api`) |
| Rewrite мимо MODX | Направьте `/api` в `index.php`. См. [web server](../installation/web-server.md) |
| Friendly URLs выключены | Используйте `assets/components/mxheadless/api.php?route=/v1/health` (или PATH_INFO `api.php/v1/health`, если сервер поддерживает) |

Проверка:

```bash
curl -sI https://your-site.example/api/v1/health
```

## 503 `service_disabled`

`mxheadless.enabled` = `false`. Discovery (`GET /api/v1`) и health работают. Включите настройку в **System Settings → mxheadless**.

## 401 / 403 на защищённых маршрутах

| Симптом | Вероятная причина |
|---------|-------------------|
| `token_required` | Нет `Authorization: Bearer mxh_...` |
| Invalid key | Revoked, expired или неверный secret |
| 403 на mutation | Нет scope, MODX ACL или CSRF при session auth |
| Preview content | Нужен scope `preview` или `view_unpublished` |

CSRF: шлите `X-CSRF-Token` из manager session на `POST`/`PUT`/`PATCH`/`DELETE`. См. [authentication](../api/authentication.md).

## 429 Too Many Requests

Превышен rate limit. Смотрите `X-RateLimit-*` и per-key overrides. Подкрутите `mxheadless.rate_limit.*` или `rate_limit_max` / `rate_limit_window` ключа.

Если лимиты «не те» для реальных клиентов, проверьте [trusted proxies](../configuration/trusted-proxies.md). Без IP балансировщика все могут делить один IP.

## 422 validation errors

Filter, sort и field names должны совпадать с object definition. Неизвестные public names дают 404 из registry, а не SQL errors.

Разрешённые параметры: `GET /api/v1/schema` и [filtering](../api/filtering.md).

## 500 internal errors

1. MODX error log, строки `[mxHeadless]`.
2. `mxheadless.debug` = `true` только на staging и ненадолго.
3. `request_id` из заголовка ответа для audit.

## Webhooks не доставляются

1. `bin/webhook-worker.php` по расписанию ([workers](workers.md)).
2. Pending: `SELECT * FROM modx_mxheadless_webhook_deliveries WHERE status = 'pending' LIMIT 10`.
3. URL subscriber — HTTPS и reachable (в production не полагайтесь на `mxheadless.webhook.allow_private_urls`).
4. Подпись на фронте совпадает с subscription secret ([ISR revalidation](isr-revalidation.md)).

## Устаревший JSON после правки

1. TTL response cache (`mxheadless.cache_ttl`). Tag invalidation на mutation есть. CDN edge может отставать.
2. Статический кэш фронта ждёт webhook revalidation tags.
3. Браузер держит ETag. Hard refresh или `Cache-Control: no-cache`.

## Пустые списки или missing objects

- Object не зарегистрирован в `OnMxHeadlessRegister`
- Неверный context (`X-Context` или `?context=`)
- `mxheadless.allowed_contexts` не включает target context
- Filter `published` скрывает unpublished resources

## См. также

- [Deployment](deployment.md)
- [Monitoring](monitoring.md)
- [Logging](logging.md)
- [Production checklist](production-checklist.md)
