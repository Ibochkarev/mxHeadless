# Ротация ключей

Ротируйте API keys и секреты OAuth client по расписанию или после подозрения на утечку. mxHeadless хранит только хеши. Старый secret из базы не восстановить.

## API keys (`mxh_*`)

Формат: `mxh_{lookupId}_{secret}`. В базе: `lookup_id`, `secret_hash`, scopes и опциональные rate limits.

### Порядок ротации

1. Создайте новый ключ с теми же или более узкими scopes ([CLI](../api/api-keys.md) или Manager).
2. Разверните новый secret во всех consumer (CI, frontend server, интеграции).
3. Убедитесь, что трафик идёт с нового ключа (`last_used_on` в `mxheadless_api_keys` или audit log).
4. Отзовите старый ключ в Manager или `revoked = 1`.

Не отзывайте ключ, пока все caller не перешли. После revoke сразу будет downtime.

### После revoke

- Bearer со старым secret вернёт `401`.
- Кэшированные анонимные GET могут жить до `mxheadless_cache_ttl`. На время ротации снизьте TTL или выключите cache.

## OAuth clients (`mxt_*`)

При `mxheadless_oauth_enabled` = `true`:

1. Создайте нового client через [oauth-clients CLI](oauth-clients.md).
2. Обновите сервисы, которые вызывают `POST /api/v1/auth/token`.
3. Отзовите старую строку client.

Access tokens истекают через `mxheadless_oauth_token_ttl` (по умолчанию 3600 с). Смена client secret блокирует новые обмены. Уже выданные tokens живут до expiry.

## Секреты webhook

Секреты лежат в `mxheadless_webhook_subscriptions.secret`. Ротация:

1. Обновите secret в subscription.
2. Обновите env на subscriber (например `MXHEADLESS_WEBHOOK_SECRET`).
3. Сделайте тестовую мутацию и проверьте подпись.

Pending outbox хранит snapshot secret на момент enqueue. Отдельный purge не нужен.

## Session и CSRF

Ротацию manager session делает MODX. mxHeadless читает текущий CSRF token из session для мутаций. Отдельного session secret у mxHeadless нет.

## См. также

- [API keys](../api/api-keys.md)
- [OAuth](../api/auth.md)
- [Incident response](incident-response.md)
- [Security](../security.md)
