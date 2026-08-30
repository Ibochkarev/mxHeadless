# Uninstall

Remove mxHeadless when you no longer need the API gateway or you are replacing it with another integration.

## Before uninstall

1. Stop frontend apps from calling `/api/v1/*`.
2. Revoke all API keys and OAuth clients.
3. Disable webhook subscriptions or stop the cron/systemd worker.
4. Export audit log rows if you need history (`mxheadless_api_log`).

## Package Manager

1. Disable the mxHeadless plugin (**Elements → Plugins**).
2. Open **Packages**, select mxHeadless, choose **Uninstall**.
3. If the installer offers to remove database tables, confirm only when you no longer need keys, webhooks, OAuth, or audit data.

The transport resolver does not auto-drop tables on uninstall. Leftover tables are harmless but contain hashed secrets metadata.

## Tables (when removed manually)

| Table | Contents |
|-------|----------|
| `{prefix}mxheadless_api_keys` | Key metadata and secret hashes |
| `{prefix}mxheadless_webhook_subscriptions` | Subscriber URLs and secrets |
| `{prefix}mxheadless_webhook_deliveries` | Outbox queue |
| `{prefix}mxheadless_oauth_clients` | OAuth client records |
| `{prefix}mxheadless_oauth_tokens` | Issued token hashes |
| `{prefix}mxheadless_api_log` | Audit trail |

## Filesystem cleanup

After uninstall, verify these are gone if the resolver did not remove them:

- `core/components/mxheadless/`
- `assets/components/mxheadless/`
- Namespace `mxheadless` in **System → Namespaces**
- System settings under namespace `mxheadless`

Remove leftover cron or systemd units for `webhook-worker.php` and `audit-prune.php`.

## Web server

No dedicated vhost rule is required for mxHeadless. After uninstall, `/api/v1/*` falls through to normal MODX routing (usually 404 for unknown paths).

## Reinstall

Install the transport package again and re-run [installation](../installation/install.md). You must recreate API keys. Secrets from a previous install cannot be recovered.

## Related

- [Installation](install.md)
- [Key rotation](../operations/key-rotation.md)
- [Workers](../operations/workers.md)
