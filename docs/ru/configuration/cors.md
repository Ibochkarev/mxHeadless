# CORS

CORS нужен, когда **браузер** с другого origin ходит в API напрямую (Nuxt SPA, Next client components). Server-side Nuxt/Next (`$fetch` в server routes, RSC, Route Handlers) CORS не требует.

## Настройки

| Ключ | Default | Заметки |
|------|---------|---------|
| `mxheadless_cors_enabled` | `false` | Главный переключатель |
| `mxheadless_cors_allowed_origins` | пусто | Точные origin через запятую, или `*` |
| `mxheadless_cors_allowed_methods` | GET,POST,PUT,PATCH,DELETE,OPTIONS | |
| `mxheadless_cors_allowed_headers` | Authorization,Content-Type,X-Request-ID,X-CSRF-Token,X-Context,X-API-Key,Idempotency-Key | |
| `mxheadless_cors_expose_headers` | ETag,X-Request-ID,X-RateLimit-*,Idempotency-Replayed | Доступны из JS |
| `mxheadless_cors_allow_credentials` | `false` | Не сочетать с `*` в origins |

## Что значит дефолт

`mxheadless_cors_enabled=false` выключает CORS. API не отдаёт заголовки `Access-Control-*`.

Это не «разрешить всем». При выключенном CORS cross-origin запрос из браузера падает на клиенте. Same-origin страницы и server-side вызовы работают как раньше.

Если включить `mxheadless_cors_enabled=true`, срабатывает allowlist. Заголовки появляются только когда `Origin` совпал с `mxheadless_cors_allowed_origins`, либо в списке ровно `*`. Даже при `*` в ответ подставляется origin запроса, а не безусловный wildcard с credentials.

Код: `core/components/mxheadless/src/Middleware/CorsMiddleware.php`.

## How to: локальный Nuxt/Next SPA

Когда фронт на `localhost:3000`, а MODX/API на другом хосте или порту.

```text
mxheadless_cors_enabled = true
mxheadless_cors_allowed_origins = http://localhost:3000
mxheadless_cors_allow_credentials = false
```

Если в браузере нужны session-cookie MODX, ставьте `mxheadless_cors_allow_credentials = true` и указывайте точный origin (не `*`).

В devtools: preflight `OPTIONS` должен вернуть `204` и `Access-Control-Allow-Origin: http://localhost:3000`.

## How to: production SPA на другом домене

```text
mxheadless_cors_enabled = true
mxheadless_cors_allowed_origins = https://app.example.com
```

Staging добавляйте явно:

```text
mxheadless_cors_allowed_origins = https://app.example.com,https://staging.example.com
```

Discovery (`GET /api/v1`) отдаёт `data.cors.enabled` и `data.cors.allowed_origins`. Сверьте с origin SPA, прежде чем копать ошибки fetch.

## How to: обойтись без CORS

Если Nuxt/Next ходит в MODX только из server routes (BFF), оставьте `mxheadless_cors_enabled=false`. Браузер до MODX не доходит, CORS не нужен.

## Быстрая проверка curl

Имитация preflight из браузера:

```bash
curl -i -X OPTIONS 'https://modx.example.com/api/v1/health' \
  -H 'Origin: https://app.example.com' \
  -H 'Access-Control-Request-Method: GET'
```

CORS включён и origin в списке: `204` и `Access-Control-Allow-Origin: https://app.example.com`. CORS выключен или origin чужой: этих заголовков не будет.

В `Access-Control-Expose-Headers` есть `ETag` для conditional revalidation из `fetch`.

Если на том же сайте крутится MiniShop3 Web API, продублируйте origin SPA в `ms3_cors_allowed_origins`. Подробности: [сосуществование с MiniShop3](../extensions/minishop3.md#сосуществование-mxheadless--ms3-web-api).
