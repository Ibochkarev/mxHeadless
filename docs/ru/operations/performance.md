# Производительность

Сначала настройте cache TTL, лимиты запросов и размер payload. Большинство медленных ответов дают широкие `include` или отсутствие индексов на filterable-полях.

## Response cache

| Настройка | По умолчанию | Подбор |
|-----------|--------------|--------|
| `mxheadless.cache.enabled` | `true` | Оставляйте для read-heavy публичных сайтов |
| `mxheadless.cache_ttl` | `300` | Выше для стабильных каталогов. Ниже для новостей |

Анонимный GET попадает в MODX cache после первого запроса. Аутентифицированные чтения shared cache не используют. См. [cache](cache.md).

## Лимиты запросов

Значения по умолчанию из `QueryParser` (можно переопределить system settings):

| Лимит | По умолчанию |
|-------|--------------|
| `mxheadless.max_limit` | 100 |
| `mxheadless.max_offset` | 100000 |
| `mxheadless.max_fields` | 50 |
| `mxheadless.max_include_relations` | 10 |
| `mxheadless.max_include_depth` | 2 |

Просите у клиентов меньшие страницы вместо `limit=100` на каждый вызов. Глубокие деревья `include` умножают SQL.

Полный список: [limits](../configuration/limits.md).

## Размер запроса

| Настройка | По умолчанию |
|-----------|--------------|
| `mxheadless.max_body_bytes` | 1048576 (1 MiB) |
| `mxheadless.max_uri_bytes` | 2048 |

Длинные filter URL отсекаются рано. POST с body только там, где API это допускает (мутации).

## Rate limiting

Глобально: 120 запросов за 60 секунд на IP клиента и identity. Override на `mxheadless_api_keys` заменяет global для этого ключа.

Защищает от того, что одна интеграция забивает лимит для всех. См. [rate limiting](../api/rate-limiting.md).

## База данных

mxHeadless компилирует фильтры в bound xPDO queries. Индексы нужны на колонках из `filterable` (`parent`, `published`, foreign keys).

Избегайте `offset` в высоких диапазонах. Keyset pagination в core нет. Кэшируйте списки или сужайте фильтры.

## Паттерны на фронте

| Паттерн | Эффект |
|---------|--------|
| Только нужные `fields` | Меньше JSON и сериализации |
| `If-None-Match` | 304 экономит трафик |
| Revalidation через webhooks | Кэш фронта без постоянных запросов к MODX |
| Fetch с API key на сервере | Ключ не попадает в браузер |

Примеры: [Next.js](../examples/nextjs.md), [Nuxt](../examples/nuxt.md).

## Multi-node cache

Общий MODX cache нужен, когда несколько нод обслуживают один сайт и вы полагаетесь на [idempotency](../api/idempotency.md) или согласованность response cache.

## См. также

- [Cache](cache.md)
- [HTTP caching](../api/http-caching.md)
- [Monitoring](monitoring.md)
