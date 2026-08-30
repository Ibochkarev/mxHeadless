# Deployment

Deploy mxHeadless on the same MODX host that holds your content. The API shares PHP, database, and cache with the CMS.

## Prerequisites

- MODX Revolution **3.2.3+**, PHP **8.1+**
- Friendly URLs or the `api.php` fallback (see [web server](../installation/web-server.md))
- TLS on the public hostname

Full compatibility matrix: [requirements](../installation/requirements.md).

## Install the package

1. Build or download the transport package (`_build/build.php`).
2. Upload via **Packages → Install Package** in Manager.
3. Confirm the mxHeadless plugin is enabled and runs on `OnHandleRequest` (default priority `-100`).

Manual or dev installs: symlink `core/components/mxheadless/`, run `composer install --no-dev`, register the `mxheadless` namespace. Details in [installation](../installation/install.md).

## Route traffic to MODX

The gateway matches `mxheadless.api.prefix` (default `/api`). All `/api/v1/*` requests must reach MODX `index.php`.

| Setup | Notes |
|-------|-------|
| Apache + friendly URLs | Standard MODX rewrite; no extra vhost rule if `/api` already hits `index.php` |
| Nginx | `try_files` to `index.php` for unknown paths |
| No rewrite | `assets/components/mxheadless/api.php?route=/v1/...` (or PATH_INFO `api.php/v1/...`) |

Behind a load balancer, list balancer IPs in `mxheadless.trusted_proxies` so rate limits and audit see the real client IP. See [trusted proxies](../configuration/trusted-proxies.md).

## Post-deploy checks

```bash
curl -s https://your-site.example/api/v1/health | jq
curl -s https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
```

Expect `health.data.status` of `ok` when the database answers.

## Background workers

If you use webhooks or ISR revalidation, schedule the webhook worker every minute:

```bash
php core/components/mxheadless/bin/webhook-worker.php --limit=50
```

Cron, systemd, and audit prune: [workers](workers.md).

## Production settings

Before go-live, walk through [production checklist](production-checklist.md):

- `mxheadless.debug` = `false`
- `mxheadless.enabled` = `true`
- Explicit CORS origins when the frontend is on another domain
- Rate limits and cache TTL aligned with your CDN
- API keys with least-privilege scopes

## Multi-node MODX

When several PHP nodes share one database:

- Use a shared MODX cache backend (required for [idempotency](../api/idempotency.md))
- Deploy the same package version on every node
- Run one webhook worker (or coordinate so outbox rows are not double-delivered)

## Rollback

1. Set `mxheadless.enabled` to `false` (discovery and health still respond; other routes return `503`).
2. Disable the plugin if the gateway must stop entirely.
3. Reinstall the previous transport package via Package Manager.

Revoke compromised keys before or during rollback. See [key rotation](key-rotation.md).

## Related

- [Installation](../installation/install.md)
- [Production checklist](production-checklist.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
