# Key rotation

Rotate API keys and OAuth client secrets on a schedule or after a suspected leak. mxHeadless stores only hashes. You cannot recover an old secret from the database.

## API keys (`mxh_*`)

Format: `mxh_{lookupId}_{secret}`. The database keeps `lookup_id`, `secret_hash`, scopes, and optional rate limits.

### Rotation workflow

1. Create a new key with the same or tighter scopes ([CLI](../api/api-keys.md) or Manager).
2. Deploy the new secret to every consumer (CI, frontend server, integration).
3. Verify traffic on the new key (`last_used_on` on `mxheadless_api_keys`, or audit log if enabled).
4. Revoke the old key in Manager or mark `revoked = 1`.

Never revoke until all callers use the new key. Downtime follows immediately after revoke.

### After revoke

- Bearer auth with the old secret returns `401`.
- Cached anonymous GET responses may linger up to `mxheadless.cache_ttl`. Lower TTL or disable cache during rotation if that window matters.

## OAuth clients (`mxt_*`)

When `mxheadless.oauth.enabled` is `true`:

1. Create a new client via [oauth-clients CLI](oauth-clients.md).
2. Update services that call `POST /api/v1/auth/token`.
3. Revoke the old client row.

Access tokens expire after `mxheadless.oauth.token_ttl` (default 3600 s). Rotating the client secret invalidates future token exchanges, not already-issued tokens until they expire.

## Webhook secrets

Subscription secrets live in `mxheadless_webhook_subscriptions.secret`. To rotate:

1. Update the secret on the subscription row.
2. Update the matching env var on the subscriber (e.g. `MXHEADLESS_WEBHOOK_SECRET`).
3. Trigger a test mutation and confirm signature verification passes.

Pending outbox rows store the secret snapshot from enqueue time. No extra purge step is required.

## Session and CSRF

Manager session rotation is handled by MODX. mxHeadless reads the current CSRF token from the session for mutations. No separate mxHeadless session secret exists.

## Related

- [API keys](../api/api-keys.md)
- [OAuth](../api/auth.md)
- [Incident response](incident-response.md)
- [Security](../security.md)
