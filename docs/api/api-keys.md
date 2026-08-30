# API keys

Server-to-server access uses bearer tokens. End-user browser flows should prefer MODX session auth with CSRF.

For short-lived machine tokens (`mxt_*`), see [OAuth token endpoint](auth.md). This page covers long-lived `mxh_*` keys.

See [authentication](authentication.md) for the full auth flow.

## Create via CLI

```bash
php core/components/mxheadless/bin/api-key-create.php \
  --name="CI build" \
  --scopes="resources.read,contexts.read"
```

Optional per-key rate limits:

```bash
  --rate-limit-max=60 \
  --rate-limit-window=60
```

The script prints the full `mxh_…` token once.

## Create in Manager

Users with `mxheadless_apikeys` (Administrator policy by default) open **Components → mxHeadless**. Create shows the full `mxh_{lookupId}_{secret}` string once. Copy it immediately. Update scopes or revoke from the same grid.

## Store safely

- Database keeps lookup ID, user ID, scopes, expiry, `password_hash(secret)`, optional per-key rate limits
- Never commit keys to git or paste them in tickets
- Rotate before revoke when changing integrations

## Scope design

Grant the smallest set that covers the integration:

| Integration | Typical scopes |
|-------------|----------------|
| Static site build (read only) | `resources.read` |
| Headless CMS editor | `resources.read`, `resources.create`, `resources.update` |
| Preview pipeline | add `preview` |

## Revoke

Revocation is immediate. Cached responses keyed by the old identity should expire within `mxheadless.cache_ttl`.

## Related

- [OAuth token endpoint](auth.md)
- [Authorization](authorization.md)
- [Key rotation](../operations/key-rotation.md)
