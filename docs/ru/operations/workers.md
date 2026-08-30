# Workers

CLI worker сливает webhook outbox. В production запускайте по расписанию.

## Команда

```bash
php /path/to/modx/core/components/mxheadless/bin/webhook-worker.php --limit=50
```

| Опция | По умолчанию | Описание |
|-------|--------------|----------|
| `--limit=N` | `mxheadless_webhook_worker_limit` или `50` | Макс. доставок за запуск |

Вывод:

```
Processed 3 webhook(s)
```

Код `0` при успехе, `1` если MODX не поднялся.

## Cron (каждую минуту)

```cron
* * * * * www-data php /var/www/modx/core/components/mxheadless/bin/webhook-worker.php --limit=50 >> /var/log/mxheadless-webhook.log 2>&1
```

Подставьте пользователя и пути своего сервера.

## systemd timer

`/etc/systemd/system/mxheadless-webhook.service`:

```ini
[Unit]
Description=mxHeadless webhook outbox worker
After=network.target

[Service]
Type=oneshot
User=www-data
WorkingDirectory=/var/www/modx
ExecStart=/usr/bin/php core/components/mxheadless/bin/webhook-worker.php --limit=50
```

`/etc/systemd/system/mxheadless-webhook.timer`:

```ini
[Unit]
Description=Run mxHeadless webhook worker every minute

[Timer]
OnBootSec=1min
OnUnitActiveSec=1min
AccuracySec=1s

[Install]
WantedBy=timers.target
```

```bash
sudo systemctl enable --now mxheadless-webhook.timer
```

## Очистка audit log

При `mxheadless_audit_enabled` = `true` — ежедневно:

```bash
php /path/to/modx/core/components/mxheadless/bin/audit-prune.php
```

Без `--days` берётся `mxheadless_audit_retention_days`. См. [audit log](audit-log.md).

## Требования

- MODX контекст `mgr` инициализируется
- Пакет mxHeadless установлен
- PSR-18 client в `$modx->services`
- Подписки в `mxheadless_webhook_subscriptions`
- URL подписчика доступен с хоста MODX (HTTPS, не private IP)

## Мониторинг

- Рост `pending` в `mxheadless_webhook_deliveries`
- Строки `failed` после исчерпания `mxheadless_webhook_max_attempts`
- Лог worker: `Processed 0` при активных мутациях

## См. также

- [Webhooks](../api/webhooks.md)
- [Audit log](audit-log.md)
- [ISR revalidation](isr-revalidation.md)
- [Чеклист production](production-checklist.md)
