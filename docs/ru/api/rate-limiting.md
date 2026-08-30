# Rate limiting

При `mxheadless_rate_limit_enabled` = `true` (по умолчанию) у каждого клиента есть лимит запросов на окно времени.

## По умолчанию

| Настройка | Значение |
|-----------|----------|
| `mxheadless_rate_limit_max_requests` | 120 |
| `mxheadless_rate_limit_window_seconds` | 60 |

Ключ: IP (после trusted proxy) + `identity_key`.

Переопределения:

| Источник | Где задаётся |
|----------|--------------|
| API key | `rate_limit_max`, `rate_limit_window` в `mxheadless_api_keys` |
| OAuth client | те же колонки в `mxheadless_oauth_clients` (для `mxt_*` этого client) |

Если заданы, подменяют глобальные `mxheadless_rate_limit_*`.

## Заголовки

```
X-RateLimit-Limit: 120
X-RateLimit-Remaining: 87
X-RateLimit-Reset: 1693142460
```

При исчерпании лимита: `429 Too Many Requests`. См. [ошибки](errors.md).

## Отключение

`mxheadless_rate_limit_enabled` = `false` имеет смысл только во внутренней сети. На публичном сайте лимит лучше оставить.

## См. также

- [Доверенные прокси](../configuration/trusted-proxies.md)
- [Лимиты](../configuration/limits.md)
