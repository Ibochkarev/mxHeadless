# OAuth clients

Machine clients для `POST /api/v1/auth/token`. Долгоживущие ключи `mxh_*` по-прежнему для CI.

## CLI (рекомендуется)

Из корня MODX после установки пакета:

```bash
php core/components/mxheadless/bin/oauth-client-create.php \
  --client-id=next-preview \
  --name="Next.js preview" \
  --scopes="resources.read,contexts.read" \
  --grants=client_credentials
```

Per-client rate limits:

```bash
  --rate-limit-max=30 \
  --rate-limit-window=60
```

`client_secret` выводится один раз. Сохраните в secrets manager.

## Включить token endpoint

1. `mxheadless_oauth_enabled = true`
2. Создать OAuth client (CLI выше)
3. Выпустить токен:

```bash
curl -s -X POST https://your-site.example/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "next-preview",
    "client_secret": "YOUR_SECRET"
  }' | jq
```

Дальше: `Authorization: Bearer mxt_…`.

## Отзыв

`revoked = 1` на строке клиента. Активные токены живут до expiry, если не отозвать строки в `mxheadless_oauth_tokens`.

## См. также

- [OAuth token endpoint](../api/auth.md)
- [Rate limiting](../api/rate-limiting.md)
- [Settings](../configuration/settings.md)
