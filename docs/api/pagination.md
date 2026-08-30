# Pagination

Use `limit` and `offset` on list endpoints. Caps come from system settings so a client cannot pull the whole database in one request.

Details also appear in [filtering](filtering.md#pagination).

## Parameters

| Parameter | Default | Max setting |
|-----------|---------|-------------|
| `limit` | 20 | `mxheadless_max_limit` (100) |
| `offset` | 0 | `mxheadless_max_offset` (100000) |
| `page` | — | 1-based page index when `offset` is omitted (`offset = (page - 1) * limit`) |

`limit` must be an integer ≥ 1. `offset` must be a non-negative integer. `page` must be ≥ 1. Invalid values return `422`. Values above the configured max are capped. Sending both `page` and `offset` returns `422`.

```
GET /api/v1/resources?limit=50&offset=100
GET /api/v1/resources?limit=50&page=3
```

## Response meta

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

`total` is the row count for the current filters, not the whole table. `prev` appears when `offset > 0`; `next` when `has_more` is true.

## Deep pages

Large `offset` on big tables can be slow on MySQL. Prefer filters and sort by indexed columns when you paginate deep lists.

## Related

- [Filtering](filtering.md)
- [Limits](../configuration/limits.md)
