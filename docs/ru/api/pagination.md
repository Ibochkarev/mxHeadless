# Пагинация

На списках используйте `limit` и `offset`. Потолки задаются в настройках, чтобы клиент не вытянул всю таблицу одним запросом.

Подробности также в [фильтрации](filtering.md#pagination).

## Параметры

| Параметр | По умолчанию | Потолок |
|----------|--------------|---------|
| `limit` | 20 | `mxheadless_max_limit` (100) |
| `offset` | 0 | `mxheadless_max_offset` (100000) |
| `page` | — | 1-based; если `offset` не передан: `offset = (page - 1) * limit` |

`limit` — целое ≥ 1. `offset` — неотрицательное целое. `page` ≥ 1. Невалидные значения → `422`. Выше max — обрезаются. Одновременно `page` и `offset` → `422`.

```
GET /api/v1/resources?limit=50&offset=100
GET /api/v1/resources?limit=50&page=3
```

## Meta в ответе

```json
{
  "data": [ "..." ],
  "meta": {
    "total": 420,
    "count": 50,
    "limit": 50,
    "offset": 100,
    "has_more": true
  },
  "links": {
    "self": "/api/v1/resources?limit=50&offset=100",
    "next": "/api/v1/resources?limit=50&offset=150",
    "prev": "/api/v1/resources?limit=50&offset=50"
  }
}
```

`total` считается с учётом текущих фильтров, не по всей таблице. `prev` есть при `offset > 0`, `next` — когда `has_more` true.

## Глубокие страницы

Большой `offset` на больших таблицах в MySQL может тормозить. Сужайте выборку фильтрами и сортируйте по индексированным полям.

## См. также

- [Фильтрация](filtering.md)
- [Лимиты](../configuration/limits.md)
