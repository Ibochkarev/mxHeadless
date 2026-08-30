# OAuth clients

Create machine clients for `POST /api/v1/auth/token`. Static `mxh_*` API keys remain for long-lived CI credentials.

## CLI (recommended)

From the MODX root, after package install and DB tables exist:

```bash
php core/components/mxheadless/bin/oauth-client-create.php \
  --client-id=next-preview \
  --name="Next.js preview" \
  --scopes="resources.read,contexts.read" \
  --grants=client_credentials
```

Optional per-client rate limits:

```bash
  --rate-limit-max=30 \
  --rate-limit-window=60
```

The script prints `client_secret` once. Store it in your secrets manager.

## Enable the token endpoint

1. Set `mxheadless.oauth.enabled` to `true`
2. Create at least one OAuth client (CLI above)
3. Issue a token:

```bash
curl -s -X POST https://your-site.example/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "next-preview",
    "client_secret": "YOUR_SECRET"
  }' | jq
```

Use `Authorization: Bearer mxt_…` on API requests.

## Manual SQL (fallback)

Insert into `{prefix}mxheadless_oauth_clients` with `password_hash()` for `client_secret_hash`. Prefer the CLI to avoid copy/paste mistakes.

## Revoke

Set `revoked = 1` on the client row. Active tokens remain valid until expiry unless you also revoke rows in `mxheadless_oauth_tokens`.

## Related

- [OAuth token endpoint](../api/auth.md)
- [Rate limiting](../api/rate-limiting.md)
- [Settings](../configuration/settings.md)
