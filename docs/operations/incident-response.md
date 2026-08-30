# Incident response

Use this sequence when an API key leaks, you see abusive traffic, or mutations fail across clients.

## 1. Contain

| Situation | Action |
|-----------|--------|
| Leaked `mxh_*` or OAuth secret | Revoke the key or client immediately |
| Ongoing abuse / scan | Lower rate limits or set `mxheadless_enabled` to `false` |
| Bad deploy | Disable the mxHeadless plugin or roll back the package |

Kill switch (`mxheadless_enabled` = `false`) leaves discovery and health up. Everything else returns `503` with `service_disabled`.

## 2. Identify scope

With audit log enabled:

```sql
SELECT method, path, status_code, identity_key, request_id, created_on
FROM modx_mxheadless_api_log
WHERE api_key_id = <id>
ORDER BY created_on DESC
LIMIT 100;
```

Without audit log, check MODX manager access, webhook delivery errors, and web server access logs. Correlate using `X-Request-ID` from problem+json responses.

## 3. Rotate credentials

Follow [key rotation](key-rotation.md):

- Issue replacement API keys before revoking compromised ones when possible
- Rotate webhook secrets if the subscriber URL or signing secret was exposed
- Force new OAuth tokens by revoking clients and shortening `mxheadless_oauth_token_ttl` temporarily if needed

## 4. Review data exposure

mxHeadless exposes only registered objects and whitelisted fields. Confirm:

- No Extra registered overly broad `ObjectDefinition` instances recently
- Compromised key scopes did not include admin-only objects (`orders`, unpublished preview, etc.)
- CORS origins were not widened to `*` with credentials

See [security](../security.md) and [authorization](../api/authorization.md).

## 5. Restore service

1. Fix root cause (revoked key redeployed, rate limit tuned, patch applied).
2. Set `mxheadless_enabled` back to `true`.
3. Run smoke tests from [production checklist](production-checklist.md).
4. Confirm webhook worker drains pending rows if mutations were queued during the outage.

## 6. Post-incident

- Document timeline and affected keys
- Schedule rotation for any key that might have been exposed
- Enable audit log if you relied on guesswork during the incident

## Related

- [Key rotation](key-rotation.md)
- [Audit log](audit-log.md)
- [Troubleshooting](troubleshooting.md)
- [Monitoring](monitoring.md)
