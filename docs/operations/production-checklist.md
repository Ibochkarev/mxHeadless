# Production checklist

Use this list before pointing a headless frontend at mxHeadless in production.

## Gateway

- [ ] mxHeadless package installed and plugin enabled
- [ ] `OnHandleRequest` plugin priority is early enough (default `-100`)
- [ ] Web server rewrites `/api/v1/*` to MODX (see [web server](../installation/web-server.md)) or `assets/components/mxheadless/api.php` fallback is configured
- [ ] `GET /api/v1/health` returns `200` from the public hostname

## Security

- [ ] `mxheadless.debug` is `false`
- [ ] `mxheadless.enabled` is `true` in production (kill switch off)
- [ ] `mxheadless.allowed_contexts` lists only contexts the API should accept
- [ ] API keys use least-privilege scopes (`resources.read`, `context.web`, `contexts.read`, …)
- [ ] Session mutations use `X-CSRF-Token` from the manager session
- [ ] CORS origins are explicit when `mxheadless.cors.enabled` is on (no `*` with credentials)
- [ ] Trusted proxy settings match your load balancer if you rely on `X-Forwarded-*`

## Caching and limits

- [ ] `mxheadless.cache.enabled` and `mxheadless.cache_ttl` match your CDN or edge strategy
- [ ] Rate limits (`mxheadless.rate_limit.*`) are set for public endpoints
- [ ] Body and URI limits are appropriate for your largest payloads

## Idempotency

- [ ] `mxheadless.idempotency.enabled` matches your mutation clients
- [ ] Shared MODX cache backend if multiple app nodes (see [idempotency](../api/idempotency.md))
- [ ] Frontend sends `Idempotency-Key` on `POST` when retrying creates

## Webhooks and ISR (optional)

- [ ] `bin/webhook-worker.php` runs every minute (cron or systemd timer, see [workers](workers.md))
- [ ] `mxheadless.webhook.worker_limit` and `mxheadless.webhook.max_attempts` tuned for your traffic
- [ ] Outbox table exists after package install
- [ ] Webhook URLs use HTTPS and are reachable from the MODX host
- [ ] Frontend `/api/revalidate` verifies `X-MxHeadless-Signature` (see [ISR revalidation](isr-revalidation.md))
- [ ] `meta.revalidate` tags mapped to your cache layer (Next.js `revalidateTag`, Nuxt storage, etc.)

## Audit log (optional)

- [ ] `mxheadless.audit.enabled` is `true` only if you need DB access history
- [ ] `bin/audit-prune.php` runs daily (see [audit log](audit-log.md))
- [ ] Table `{prefix}mxheadless_api_log` exists after package upgrade

## OAuth tokens (optional)

- [ ] `mxheadless.oauth.enabled` is `true` only when using short-lived `mxt_*` tokens
- [ ] OAuth clients created via [CLI](oauth-clients.md) or Manager workflow
- [ ] `mxheadless.oauth.password_grant_enabled` stays `false` unless you need password grant

## Frontend smoke tests

```bash
curl -s https://your-site.example/api/v1/health | jq
curl -s https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
curl -s https://your-site.example/api/v1/meta/openapi | jq '.data.openapi'
curl -s 'https://your-site.example/api/v1/resources?limit=1' | jq
curl -s -H 'Authorization: Bearer YOUR_KEY' https://your-site.example/api/v1/contexts | jq
curl -s -H 'Authorization: Bearer YOUR_KEY' https://your-site.example/api/v1/contexts/web/settings | jq
# Expect code token_required without Authorization on a protected route:
curl -s https://your-site.example/api/v1/contexts | jq '.code'
# OAuth (when enabled):
curl -s -X POST https://your-site.example/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{"grant_type":"client_credentials","client_id":"YOUR_CLIENT","client_secret":"YOUR_SECRET"}' | jq '.data.access_token'
```

## Related

- [Deployment](deployment.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
- [Security](../security.md)
- [Testing and verification checklist](../contributing/testing.md)
