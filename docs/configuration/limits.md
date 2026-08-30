# Limits

Caps that stop a client from pulling the whole database or sending huge payloads. Change them in System Settings.

| Setting | Default | Role |
|---------|---------|------|
| `mxheadless_max_body_bytes` | 1048576 | Max JSON body size |
| `mxheadless_max_uri_bytes` | 2048 | Max request URI length |
| `mxheadless_max_limit` | 100 | Max `limit` on list endpoints |
| `mxheadless_max_offset` | 100000 | Max `offset` (and derived page offsets) |
| `mxheadless_max_fields` | 50 | Max comma-separated `fields` |
| `mxheadless_max_include_relations` | 10 | Max `include` paths |
| `mxheadless_max_include_depth` | 2 | Max nesting in `include=a.b` |

`limit` below 1 is rejected (`422`). `page` without `offset` becomes `(page - 1) * limit`, then capped by `max_offset`. Sending both `page` and `offset` returns `422`.

## Related

- [Pagination](../api/pagination.md)
- [Filtering](../api/filtering.md)
- [Settings](settings.md)
