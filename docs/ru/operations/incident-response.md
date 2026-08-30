# Реагирование на инциденты

Эта последовательность подходит при утечке API key, abusive traffic или массовых сбоях мутаций.

## 1. Сдерживание

| Ситуация | Действие |
|----------|----------|
| Утечка `mxh_*` или OAuth secret | Немедленно отозвать key или client |
| Abuse или scan | Снизить rate limits или `mxheadless_enabled` = `false` |
| Плохой deploy | Отключить плагин mxHeadless или откатить пакет |

Kill switch (`mxheadless_enabled` = `false`) оставляет discovery и health. Остальное отдаёт `503` с `service_disabled`.

## 2. Оценка масштаба

При включённом audit log:

```sql
SELECT method, path, status_code, identity_key, request_id, created_on
FROM modx_mxheadless_api_log
WHERE api_key_id = <id>
ORDER BY created_on DESC
LIMIT 100;
```

Без audit смотрите доступ в manager, ошибки webhook delivery и access log веб-сервера. Связка через `X-Request-ID` из problem+json.

## 3. Ротация credentials

Следуйте [key rotation](key-rotation.md):

- Выдайте replacement keys до revoke скомпрометированных, если успеваете
- Ротируйте webhook secrets при exposure URL или signing secret
- Принудительно обновите OAuth tokens через revoke clients. Временно сократите `mxheadless_oauth_token_ttl`

## 4. Проверка exposure данных

mxHeadless отдаёт только зарегистрированные objects и whitelisted fields. Проверьте:

- Не регистрировали ли Extra слишком широкий `ObjectDefinition`
- Не давали ли scopes compromised key на admin objects (`orders`, unpublished preview и т.д.)
- Не расширили ли CORS до `*` с credentials

См. [security](../security.md) и [authorization](../api/authorization.md).

## 5. Восстановление сервиса

1. Устраните причину (ключ развёрнут, rate limit настроен, патч применён).
2. Верните `mxheadless_enabled` = `true`.
3. Прогоните smoke tests из [production checklist](production-checklist.md).
4. Убедитесь, что webhook worker разгреб pending, если мутации копились во время простоя.

## 6. После инцидента

- Зафиксируйте timeline и затронутые keys
- Запланируйте ротацию для keys с возможным exposure
- Включите audit log, если во время инцидента опирались на догадки

## См. также

- [Key rotation](key-rotation.md)
- [Audit log](audit-log.md)
- [Troubleshooting](troubleshooting.md)
- [Monitoring](monitoring.md)
