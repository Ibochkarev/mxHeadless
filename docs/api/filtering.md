# Filtering and querying

mxHeadless converts HTTP query parameters into typed `ObjectQuery` objects. The query compiler validates every field against the object definition whitelist and builds xPDO queries with bound parameters only.

Applies to `/api/v1/resources`, `/api/v1/objects/{name}`, and other list endpoints.

## Pagination

| Parameter | Default | Max (configurable) |
|-----------|---------|-------------------|
| `limit` | 20 | `mxheadless.max_limit` (100); must be an integer ≥ 1 |
| `offset` | 0 | `mxheadless.max_offset` (100000); non-negative integer |
| `page` | — | When `offset` is omitted: `offset = (page - 1) * limit`; page ≥ 1 |

Invalid `limit`, `offset`, or `page` return `422`. Values above the configured max are capped.

```
GET /api/v1/resources?limit=50&offset=100
GET /api/v1/resources?limit=50&page=3
```

Response `meta` includes `total`, `count`, `limit`, `offset`, and `has_more`. Top-level `links` includes `self` and optional `next` / `prev`.

## Field selection

```
GET /api/v1/resources?fields=id,pagetitle,uri
```

- Comma-separated list
- Maximum `mxheadless.max_fields` (default 50)
- Unknown fields return `422 Field not allowed`
- Empty `fields` returns all non-hidden fields allowed for the caller

## Sorting

```
GET /api/v1/resources?sort=-published,+pagetitle
```

| Prefix | Direction |
|--------|-----------|
| `-` | DESC |
| `+` or none | ASC |

Only fields listed in `ObjectDefinition::sorts()` are accepted. Default sort: `id ASC` when the object has an `id` field. Direction: `-field` (DESC), `+field` / `field` (ASC), or `field:asc` / `field:desc`.

## Filters

Nested query syntax:

```
filter[field][operator]=value
```

Alternative key: `filters` (same structure).

### Operators

| Operator | Meaning | Example |
|----------|---------|---------|
| `eq` | equals | `filter[published][eq]=1` or `true`/`false` |
| `neq` | not equals | `filter[parent][neq]=0` |
| `gt` | greater than | `filter[id][gt]=100` |
| `gte` | greater or equal | `filter[id][gte]=100` |
| `lt` | less than | `filter[id][lt]=50` |
| `lte` | less or equal | `filter[id][lte]=50` |
| `like` | SQL LIKE | `filter[pagetitle][like]=%news%` |
| `in` | in list | `filter[parent][in]=1,2,3` |
| `not_in` | not in list | `filter[id][not_in]=5,6` |
| `null` | IS NULL | `filter[deletedon][null]=1` |
| `not_null` | IS NOT NULL | `filter[publishedon][not_null]=1` |

Multiple filters are combined with AND.

Boolean filterable fields (`published`, `deleted`, `hidemenu`, …) accept `0`/`1` and `true`/`false` (also `yes`/`no`/`on`/`off`). Other strings return `422`.

Only `filterable` fields from the object definition are accepted. Unknown fields return `422 Filter not allowed`.

### Security

Filter field names and sort fields are whitelisted. SQL injection payloads in field names are rejected before query compilation. Values are always passed as bound parameters.

## Search

```
GET /api/v1/resources?q=installation
```

Full-text search runs `LIKE %term%` across `searchable` fields (OR combined). Objects without searchable fields return `422 Search not supported`.

## Includes (relations)

```
GET /api/v1/resources?include=parent,children
```

| Limit | Setting |
|-------|---------|
| Max relations | `mxheadless.max_include_relations` (10) |
| Max depth | `mxheadless.max_include_depth` (2) |

Nested paths use dot notation: `include=parent,children.tv`.

Relations must be registered on the object definition. Unknown names return `422`. Unauthorized relations are omitted or return `403` depending on policy.

## Context and preview

```
GET /api/v1/resources?context=web&preview=false
```

Or header:

```
X-Context: web
```

Invalid contexts return `422 Invalid context`.

## Published content defaults

For objects with `published` and `deleted` fields, list queries automatically add:

- `published = 1`
- `deleted = 0 OR NULL`

unless `preview=true` and the caller is authorized.

## Generic objects

```
GET /api/v1/objects/products?filter[price][gte]=100&sort=-createdon
```

Same query syntax. Object capabilities come from the Extra that registered `products` via `ExtensionApi`.

## Schema introspection

```
GET /api/v1/schema
```

Returns registered objects with `fields`, `filterable`, `sortable`, and `searchable` arrays for dynamic UIs.

## Examples

See [cURL examples](../examples/curl.md) and [OpenAPI](../openapi.yaml).
