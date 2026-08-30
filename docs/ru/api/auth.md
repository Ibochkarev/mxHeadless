# OAuth token endpoint

Опциональные короткоживущие bearer-токены для machine clients. Статические ключи `mxh_*` остаются для CI и долгоживущих интеграций.

## Включение

| Настройка | По умолчанию | Описание |
|-----------|--------------|----------|
| `mxheadless_oauth_enabled` | `false` | Главный переключатель для `POST /auth/token` |
| `mxheadless_oauth_token_ttl` | `3600` | TTL access token (секунды) |
| `mxheadless_oauth_password_grant_enabled` | `false` | Разрешить `grant_type=password` |

Клиентов создавайте в `{prefix}mxheadless_oauth_clients` или через [CLI](../operations/oauth-clients.md).

## Выпуск токена

```http
POST /api/v1/auth/token
Content-Type: application/json

{
  "grant_type": "client_credentials",
  "client_id": "next-preview",
  "client_secret": "…",
  "scope": "resources.read"
}
```

Также принимается `Content-Type: application/x-www-form-urlencoded` (тело в стиле OAuth 2.0).

Клиентские credentials можно передать через `Authorization: Basic base64(client_id:client_secret)` вместо полей в теле.

Ответ:

```json
{
  "data": {
    "access_token": "mxt_…",
    "token_type": "Bearer",
    "expires_in": 3600,
    "scope": "resources.read"
  },
  "meta": {}
}
```

Дальше: `Authorization: Bearer mxt_…`.

## Grant types

| Grant | Нужно | Примечание |
|-------|-------|------------|
| `client_credentials` | `client_id`, `client_secret` | Сборки, service accounts |
| `password` | + `username`, `password` | Выключен по умолчанию. Клиент должен разрешать grant в `grant_types` |

## Коды ошибок

| Код | HTTP | Когда |
|-----|------|-------|
| `invalid_grant` | 400 | Неверный client, grant не поддержан, endpoint выключен |
| validation | 422 | Нет `grant_type`, `client_id` и т.д. |

## Безопасность

- В БД только хэши секретов клиента и токена.
- Токены истекают сами. Отзыв — `revoked = 1` на строке токена.
- Password grant — только для доверенных first-party клиентов.
- У клиента могут быть свои rate limits (`rate_limit_max`, `rate_limit_window`).

## См. также

- [Errors](errors.md)
- [Settings](../configuration/settings.md)
- [ADR 0009](../../decisions/0009-oauth-tokens.md)
