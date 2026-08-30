# Аутентификация

mxHeadless определяет **кто** вызывает API. **Что** можно делать, решает авторизация (scopes + ACL MODX).

## Типы identity

| Тип | Когда | Механизм |
|-----|-------|----------|
| Anonymous | Публичное чтение | Без заголовков |
| Session | UI в mgr или фронт с cookie MODX | Cookie сессии |
| API key | Долгоживущий доступ: CI, сборки, сервер-сервер | `Authorization: Bearer mxh_...` |
| OAuth token | Короткоживущий machine access (опционально) | `Authorization: Bearer mxt_...` |

Порядок authenticator: OAuth token → API key → session → anonymous.

## API keys (`mxh_*`)

Долгоживущие credentials. Удобны для CI, статических сборок и интеграций без частой ротации.

### Формат

```
mxh_{lookupId}_{secret}
```

Пример: `mxh_a1b2c3d4_xK9mN2pQ8rT5vW1yZ6`

- `lookupId` — публичный id в `{prefix}mxheadless_api_keys`
- `secret` — показывают один раз, в БД только `password_hash()`

Per-key rate limits: колонки `rate_limit_max`, `rate_limit_window`.

### Запрос

```bash
curl -s https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_a1b2c3d4_xK9mN2pQ8rT5vW1yZ6'
```

Создание и отзыв в mgr при праве `mxheadless_apikeys`. См. [API keys](api-keys.md).

## OAuth tokens (`mxt_*`)

Короткоживущие bearer-токены через `POST /api/v1/auth/token`. **По умолчанию выключено** (`mxheadless_oauth_enabled`).

### Формат

```
mxt_{tokenId}_{secret}
```

В `{prefix}mxheadless_oauth_tokens` хранится только `token_hash`. TTL — `mxheadless_oauth_token_ttl` (по умолчанию 3600 с).

### Выпуск токена

```bash
curl -s -X POST https://example.com/api/v1/auth/token \
  -H 'Content-Type: application/json' \
  -d '{
    "grant_type": "client_credentials",
    "client_id": "next-preview",
    "client_secret": "YOUR_CLIENT_SECRET",
    "scope": "resources.read"
  }'
```

Дальше: `Authorization: Bearer mxt_...` из поля `access_token`.

Клиентов создавайте через [CLI OAuth clients](../operations/oauth-clients.md). Подробнее: [token endpoint](auth.md).

### Key или token

| Credential | Когда |
|------------|-------|
| `mxh_*` | CI, долгие workers, без логики refresh |
| `mxt_*` | Ротация без redeploy, TTL, client_credentials |

Оба типа используют один scope checker. CSRF для bearer не нужен.

## Scopes

Примеры: `resources.read`, `resources.create`, `resources.update`, `resources.delete`, `objects.products.read`, `preview`, `*`.

Нет нужного scope → `403`, `code: scope_denied`.

## Сессия MODX

При cookie сессии целевого контекста подставляется текущий пользователь.

### CSRF

Для `POST`, `PUT`, `PATCH`, `DELETE` по сессии:

```
X-CSRF-Token: {токен из сессии MODX}
```

`mxh_*` и `mxt_*` CSRF не требуют.

## Anonymous

Только public-маршруты и объекты, разрешённые без входа. Иначе `401`, `code: token_required`.

## Цепочка

```
Запрос → AuthenticationMiddleware → Identity
       → AuthorizationMiddleware → scope + MODX + контекст + поля
       → RequestLifecycleMiddleware (хуки)
       → Сервис
```

Скрытые поля не отдаются. Protected — только с отдельным правом.

## Preview

`?preview=true` отдаёт неопубликованное при `view_unpublished` (сессия) или scope `preview` (key/token). Anonymous preview запрещён.

## Ошибки

| HTTP | `code` | Смысл |
|------|--------|-------|
| 401 | `token_required` | Нет или неверные credentials |
| 403 | `scope_denied` | Вошли, действие запрещено |
| 400 | `invalid_grant` | OAuth token отклонён |
| 422 | — | Validation |

См. [errors](errors.md).

## Production

- Минимальные scopes на key и OAuth client
- Keys — стабильные серверные клиенты. Tokens — когда нужен expiry
- Не логируйте `Authorization` целиком
- `mxheadless_oauth_password_grant_enabled` держите `false`, если не доверяете клиентам пароли MODX
- [Чеклист безопасности](../security.md)

## См. также

- [API keys](api-keys.md)
- [OAuth token endpoint](auth.md)
- [Авторизация](authorization.md)
- [Rate limiting](rate-limiting.md)
