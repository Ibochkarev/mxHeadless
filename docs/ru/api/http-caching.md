# HTTP-кэш

Публичные GET можно кэшировать в браузере или CDN, если `mxheadless.cache.enabled` = `true`.

## Заголовки ответа

На безопасных маршрутах для anonymous:

```
Cache-Control: public, max-age=300
ETag: "a1b2c3d4e5f6..."
```

`max-age` берётся из `mxheadless.cache_ttl` (по умолчанию 300 с).

## Условные запросы

Передайте ETag из прошлого ответа:

```bash
curl -s -D - https://example.com/api/v1/resources/5 \
  -H 'If-None-Match: "a1b2c3d4e5f6..."'
```

Если представление не изменилось, сервер ответит `304 Not Modified` без тела.

## Сессия и preview

Ответы с сессией, API key и `?preview=true`:

```
Cache-Control: private, no-store
```

Не кладите их на общий CDN.

## Инвалидация

Save и delete ресурса сбрасывают теги кэша объекта и списков. См. [кэш в эксплуатации](../operations/cache.md).

## См. также

- [Настройки](../configuration/settings.md)
- [ADR 0005](../decisions/0005-cache.md)
