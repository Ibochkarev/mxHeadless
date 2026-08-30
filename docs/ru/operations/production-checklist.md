# Чеклист перед production

Пройдите список до того, как подключите headless-фронт к mxHeadless в бою.

## Gateway

- [ ] Пакет mxHeadless установлен, плагин включён
- [ ] Приоритет плагина `OnHandleRequest` достаточно ранний (по умолчанию `-100`)
- [ ] Веб-сервер проксирует `/api/v1/*` в MODX (см. [веб-сервер](../ru/installation/web-server.md)) или настроен fallback `assets/components/mxheadless/api.php`
- [ ] `GET /api/v1/health` с публичного хоста отдаёт `200`

## Безопасность

- [ ] `mxheadless_debug` = `false`
- [ ] `mxheadless_enabled` = `true` в production (kill switch выключен)
- [ ] `mxheadless_allowed_contexts` содержит только разрешённые контексты
- [ ] API keys с минимальными scope (`resources.read`, `context.web`, `contexts.read`, …)
- [ ] Мутации по сессии с заголовком `X-CSRF-Token`
- [ ] CORS: явный список origin при `mxheadless_cors_enabled` (без `*` вместе с credentials)
- [ ] Trusted proxy настроен под балансировщик, если используете `X-Forwarded-*`

## Кэш и лимиты

- [ ] `mxheadless_cache_enabled` и `mxheadless_cache_ttl` согласованы с CDN или edge
- [ ] Rate limit (`mxheadless_rate_limit_*`) для публичных endpoint
- [ ] Лимиты body и URI подходят под ваши payload

## Idempotency

- [ ] `mxheadless_idempotency_enabled` согласован с клиентами мутаций
- [ ] Общий cache backend MODX при нескольких нодах (см. [idempotency](../api/idempotency.md))
- [ ] Фронт шлёт `Idempotency-Key` на `POST` при повторных create

## Webhooks и ISR (опционально)

- [ ] `bin/webhook-worker.php` каждую минуту (cron или systemd timer, см. [workers](workers.md))
- [ ] `mxheadless_webhook_worker_limit` и `mxheadless_webhook_max_attempts` под ваш трафик
- [ ] Таблица outbox создана после установки пакета
- [ ] URL webhook на HTTPS и доступны с хоста MODX
- [ ] `/api/revalidate` на фронте проверяет `X-MxHeadless-Signature` (см. [ISR revalidation](isr-revalidation.md))
- [ ] Теги `meta.revalidate` сопоставлены с кэшем (Next.js `revalidateTag`, Nuxt storage и т.д.)

## Audit log (опционально)

- [ ] `mxheadless_audit_enabled` = `true` только если нужен журнал обращений в БД
- [ ] `bin/audit-prune.php` ежедневно (см. [audit log](audit-log.md))
- [ ] Таблица `{prefix}mxheadless_api_log` создана после upgrade пакета

## OAuth tokens (опционально)

- [ ] `mxheadless_oauth_enabled` = `true` только при использовании `mxt_*` токенов
- [ ] OAuth clients через [CLI](oauth-clients.md)
- [ ] `mxheadless_oauth_password_grant_enabled` = `false`, если password grant не нужен

## Smoke-тесты для фронта

```bash
curl -s https://your-site.example/api/v1/health | jq
curl -s https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
curl -s https://your-site.example/api/v1/meta/openapi | jq '.data.openapi'
curl -s 'https://your-site.example/api/v1/resources?limit=1' | jq
curl -s -H 'Authorization: Bearer YOUR_KEY' https://your-site.example/api/v1/contexts | jq
curl -s -H 'Authorization: Bearer YOUR_KEY' https://your-site.example/api/v1/contexts/web/settings | jq
# Без Authorization на защищённом маршруте ожидайте code token_required:
curl -s https://your-site.example/api/v1/contexts | jq '.code'
# OAuth (если включён):
curl -s -X POST https://your-site.example/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{"grant_type":"client_credentials","client_id":"YOUR_CLIENT","client_secret":"YOUR_SECRET"}' | jq '.data.access_token'
```

## См. также

- [Деплой](deployment.md)
- [Мониторинг](monitoring.md)
- [Troubleshooting](troubleshooting.md)
- [Безопасность](../security.md)
- [Чеклист тестирования](../contributing/testing.md)
