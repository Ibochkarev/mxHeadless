# Удаление

Уберите mxHeadless, если API gateway больше не нужен или вы переходите на другую интеграцию.

## Перед удалением

1. Остановите вызовы `/api/v1/*` с фронта.
2. Отзовите все API keys и OAuth clients.
3. Отключите webhook subscriptions или cron/systemd worker.
4. Экспортируйте audit log, если нужна история (`mxheadless_api_log`).

## Package Manager

1. Отключите плагин mxHeadless (**Elements → Plugins**).
2. **Packages** → mxHeadless → **Uninstall**.
3. Удаление таблиц подтверждайте только если keys, webhooks, OAuth и audit больше не нужны.

Transport resolver при uninstall таблицы сам не дропает. Остатки безвредны, но содержат metadata hashed secrets.

## Таблицы (ручное удаление)

| Таблица | Содержимое |
|---------|------------|
| `{prefix}mxheadless_api_keys` | Metadata keys и secret hashes |
| `{prefix}mxheadless_webhook_subscriptions` | URL и secrets subscriber |
| `{prefix}mxheadless_webhook_deliveries` | Outbox queue |
| `{prefix}mxheadless_oauth_clients` | OAuth clients |
| `{prefix}mxheadless_oauth_tokens` | Hashes выданных tokens |
| `{prefix}mxheadless_api_log` | Audit trail |

## Файловая система

После uninstall проверьте, что удалено:

- `core/components/mxheadless/`
- `assets/components/mxheadless/`
- Namespace `mxheadless` в **System → Namespaces**
- System settings namespace `mxheadless`

Уберите cron или systemd units для `webhook-worker.php` и `audit-prune.php`.

## Веб-сервер

Отдельное правило vhost для mxHeadless не требовалось. После uninstall `/api/v1/*` идёт в обычный routing MODX (часто 404).

## Повторная установка

Снова установите transport и пройдите [installation](../installation/install.md). API keys нужно создать заново. Secrets прошлой установки не восстановить.

## См. также

- [Installation](install.md)
- [Key rotation](../operations/key-rotation.md)
- [Workers](../operations/workers.md)
