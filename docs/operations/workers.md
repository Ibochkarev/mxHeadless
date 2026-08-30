# Workers

mxHeadless ships a CLI worker that drains the webhook outbox. Run it in production on a schedule.

## Command

```bash
php /path/to/modx/core/components/mxheadless/bin/webhook-worker.php --limit=50
```

| Option | Default | Description |
|--------|---------|-------------|
| `--limit=N` | `mxheadless_webhook_worker_limit` or `50` | Max deliveries per run |

Output:

```
Processed 3 webhook(s)
```

Exit code `0` on success, `1` if MODX bootstrap fails.

## Cron (every minute)

```cron
* * * * * www-data php /var/www/modx/core/components/mxheadless/bin/webhook-worker.php --limit=50 >> /var/log/mxheadless-webhook.log 2>&1
```

Replace `www-data` and paths with your deployment.

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

## Audit log prune

When `mxheadless_audit_enabled` is `true`, run daily:

```bash
php /path/to/modx/core/components/mxheadless/bin/audit-prune.php
```

Uses `mxheadless_audit_retention_days` unless you pass `--days=N`. See [audit log](audit-log.md).

## Requirements

- MODX `mgr` context initializes successfully
- mxHeadless package installed (`core/components/mxheadless/`)
- PSR-18 HTTP client registered in `$modx->services` for outbound delivery
- Webhook subscription rows in `mxheadless_webhook_subscriptions`
- Subscriber URL reachable from the MODX host (HTTPS, not private IP)

## Monitoring

Watch:

- Growing `pending` count in `mxheadless_webhook_deliveries`
- Rows stuck in `failed` after `mxheadless_webhook_max_attempts`
- Worker log for `Processed 0 webhook(s)` when mutations occur

## Related

- [Webhooks](../api/webhooks.md)
- [Audit log](audit-log.md)
- [ISR revalidation](isr-revalidation.md)
- [Production checklist](production-checklist.md)
