# Sorting

List endpoints accept a `sort` query parameter. Only fields listed in `ObjectDefinition::sorts()` for that object are valid.

Full syntax and limits are in [filtering](filtering.md#sorting). This page covers behavior at the HTTP layer.

## Syntax

```
GET /api/v1/resources?sort=-published,+pagetitle
```

| Prefix / suffix | Order |
|-----------------|-------|
| `-field` | DESC |
| `+field` or `field` | ASC |
| `field:asc` / `field:desc` | ASC / DESC (Nuxt/Strapi-style alias) |

Comma separates multiple fields. Order in the string is the order applied in SQL.

## Default

When you omit `sort` and the object has an `id` field, the compiler uses `id ASC`.

## Errors

Unknown sort fields return `422` with a problem detail. The compiler never passes client field names into raw SQL.

## Examples

```bash
# Newest resources first
curl -s 'https://example.com/api/v1/resources?sort=-id&limit=10'

# Menu order within a parent
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=2&sort=+menuindex'
```

## Related

- [Filtering](filtering.md)
- [Pagination](pagination.md)
- [Schema](schema.md) (see `sortable` per object)
