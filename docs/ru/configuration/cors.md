# CORS

Нужен, когда **браузер** с другого origin ходит в API напрямую (Nuxt SPA, Next client components). Server-side Nuxt/Next (`$fetch` в server routes, RSC, Route Handlers) CORS не требует.

## Настройки

| Ключ | Default | Заметки |
|------|---------|---------|
| `mxheadless_cors_enabled` | `false` | Главный переключатель |
| `mxheadless_cors_allowed_origins` | пусто | Точные origin через запятую, или `*` |
| `mxheadless_cors_allowed_methods` | GET,POST,PUT,PATCH,DELETE,OPTIONS | |
| `mxheadless_cors_allowed_headers` | Authorization,Content-Type,X-Request-ID,X-CSRF-Token,X-Context,X-API-Key,Idempotency-Key | |
| `mxheadless_cors_expose_headers` | ETag,X-Request-ID,X-RateLimit-*,Idempotency-Replayed | Доступны из JS |
| `mxheadless_cors_allow_credentials` | `false` | Не сочетать с `*` в origins |

## Пример для Nuxt / Next

```text
mxheadless_cors_enabled = true
mxheadless_cors_allowed_origins = http://localhost:3000,https://app.example.com
```

OPTIONS preflight отвечает `204` с ACAO, если `Origin` совпал. Discovery (`GET /api/v1`) отдаёт `data.cors.enabled`.

В `Access-Control-Expose-Headers` есть `ETag` — клиентский `fetch` может делать conditional revalidation.

Если на том же сайте крутится MiniShop3 Web API, продублируйте origin SPA в `ms3_cors_allowed_origins`. Подробности: [сосуществование с MiniShop3](../extensions/minishop3.md#сосуществование-mxheadless--ms3-web-api).
