# Search

Full-text style search uses the `q` query parameter on list endpoints.

```
GET /api/v1/resources?q=installation
```

## Behavior

`QueryParser` runs `LIKE %term%` across fields listed in `searchable` on the object definition. Multiple fields combine with OR.

For core `resources`, searchable fields include `pagetitle`, `longtitle`, `description`, `introtext`, `alias`, and `uri`.

## Limits

Search respects the same `limit` and `offset` as other list queries. Very short terms may match many rows; combine with `filter` to narrow results.

## Errors

Objects without searchable fields return `422 Search not supported`.

## Related

- [Filtering](filtering.md#search)
- [Schema](schema.md)
