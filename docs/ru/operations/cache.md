# Кэш

mxHeadless кэширует анонимные GET в MODX cache manager. Мутации поднимают версии тегов, и устаревший JSON исчезает без перебора всех ключей.

## Настройки

| Ключ | По умолчанию | Роль |
|-----|--------------|------|
| `mxheadless.cache.enabled` | `true` | Общий кэш для анонимных GET |
| `mxheadless.cache_ttl` | `300` | `max-age` и TTL записи (секунды) |

Аутентифицированные запросы, API keys и preview всегда получают `Cache-Control: private, no-store`. См. [HTTP caching](../api/http-caching.md).

## Как строится ключ

`HttpCacheMiddleware` хеширует path, отсортированный query string, context и версии тегов:

- `object:{name}` — из метаданных маршрута (например `resources`)
- `context:{key}` — из `X-Context`, `?context=` или текущего context

Версии тегов лежат в namespace `mxheadless/tags`. После инкремента старые response keys промахиваются и пересобираются на следующем GET.

## Инвалидация при мутациях

После `POST`, `PUT`, `PATCH` или `DELETE` `MutationHooks` вызывает:

1. `invalidateTag('object:' . $name)` — например `object:resources`
2. `invalidateTag('context:' . $contextKey)` — например `context:web`

Этого хватает для list и detail без сброса всего partition.

## ETag и 304

Публичные GET отдают `ETag` (SHA-256 тела). Клиент шлёт `If-None-Match` и получает `304 Not Modified`. HEAD использует то же представление, что GET.

## CDN и edge

CDN перед анонимными GET имеет смысл, когда:

- Ответы действительно публичные (нет session cookie и bearer token)
- `Cache-Control: public, max-age=N` согласован с вашей стратегией purge
- Кэш фронта обновляете через webhook `meta.revalidate` ([ISR revalidation](isr-revalidation.md))

Аутентифицированный JSON на shared edge не кэшируйте.

## Отключить или укоротить кэш

| Цель | Действие |
|------|----------|
| Отладка | `mxheadless.cache.enabled` = `false` |
| Быстрее контент | Уменьшить `mxheadless.cache_ttl` |
| Свежий API | Выключить кэш. Webhooks оставить для фронта |

Отзыв API key не чистит кэш мгновенно. Записи протухают в пределах `mxheadless.cache_ttl`.

## См. также

- [HTTP caching](../api/http-caching.md)
- [Performance](performance.md)
- [Settings](../configuration/settings.md)
- [ADR 0005](../decisions/0005-cache.md)
