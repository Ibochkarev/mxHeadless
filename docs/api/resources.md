# Resources API

MODX resources are first-class REST objects at `/api/v1/resources`. Pages are also available by URI at `/api/v1/pages/{uri}`.

## List resources

```
GET /api/v1/resources
```

Scope: `resources.read` (public read when configured).

### Query parameters

| Parameter | Example | Description |
|-----------|---------|-------------|
| `limit` | `20` | Page size; integer ≥ 1, capped by `mxheadless.max_limit` (default 100) |
| `offset` | `0` | Skip rows; non-negative integer |
| `page` | `2` | 1-based page when `offset` is omitted |
| `fields` | `id,pagetitle,uri` | Sparse fieldset |
| `sort` | `-published,+pagetitle` or `menuindex:asc` | Sort (`-` DESC, `+` ASC, or `:asc`/`:desc`) |
| `filter[field][op]` | `filter[parent][eq]=5` | See [filtering](filtering.md) |
| `include` | `parent,tv` | Registered relations only (unknown → `422`) |
| `q` | `news` | Full-text search on searchable fields |
| `context` | `web` | MODX context |
| `preview` | `true` | Include unpublished (requires permission) |

### Example

```bash
# Card list
curl -s 'https://example.com/api/v1/resources?limit=10&filter[parent][eq]=2&filter[published][eq]=1&fields=id,pagetitle,uri&sort=-id' \
  -H 'Accept: application/json'

# Nuxt/Next nav (root menu)
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=0&filter[published][eq]=1&filter[hidemenu][eq]=0&fields=id,pagetitle,uri,menuindex,isfolder&sort=menuindex'
```

### Response

```json
{
  "data": [
    { "id": 5, "pagetitle": "About", "uri": "about/" }
  ],
  "meta": {
    "total": 42,
    "limit": 10,
    "offset": 0
  },
  "links": {
    "self": "/api/v1/resources?limit=10&offset=0",
    "next": "/api/v1/resources?limit=10&offset=10"
  }
}
```

## Get one resource

```
GET /api/v1/resources/{id}
```

Returns a single resource object. Unpublished resources require `view_unpublished` permission or preview scope.

```bash
curl -s https://example.com/api/v1/resources/5
```

## Page by URI

```
GET /api/v1/pages/{uri}
```

Resolves a resource by its URI path (without leading slash in the path segment). Useful for frontend routers that mirror MODX URL structure.

```bash
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content'
```

## Create resource

```
POST /api/v1/resources
Content-Type: application/json
```

Requires `resources.create` scope and MODX permission. Session auth requires `X-CSRF-Token`. `pagetitle` is required and must be non-empty.

```bash
curl -s -X POST https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_abc123_yoursecret' \
  -H 'Content-Type: application/json' \
  -d '{"pagetitle":"New page","parent":2,"template":1,"published":0}'
```

## Update resource

```
PUT /api/v1/resources/{id}
PATCH /api/v1/resources/{id}
```

- `PUT` requires all `required` fields from schema (e.g. `pagetitle`) and merges the rest like `PATCH`
- `PATCH` merges partial updates

Scope: `resources.update`. Soft-deleted resources can be updated (for example `{"deleted":0}` to restore).

## Delete resource

```
DELETE /api/v1/resources/{id}
DELETE /api/v1/resources/{id}?force=true
```

Default is soft-delete: `deleted=1`, `deletedon` / `deletedby` set, children in the resource tree marked the same way. Soft-deleted rows stay out of normal list/get responses unless you pass `include_deleted=true` (requires `preview`, `resources.update`, or `resources.delete`; also skips the published-only filter). A second soft `DELETE` on the same id returns `404`.

Restore with `PATCH /resources/{id}` and `{"deleted":0}`. Pass `force=true` to permanently remove the row (including an already soft-deleted one). Scope: `resources.delete`.

See [mutations](mutations.md#delete-behavior).

## Template variables (TVs)

TVs are never included by default. Request explicitly:

```
GET /api/v1/resources/5?include=tv&tv_fields=image,subtitle
```

TV loading goes through `TvProviderInterface` with field-level authorization.

## Context

Pass context via query or header:

```bash
curl -s https://example.com/api/v1/resources/5 -H 'X-Context: web'
```

Only contexts listed in `mxheadless.allowed_contexts` are accepted.

## Caching

Public GET responses may include `ETag` and `Cache-Control`. Send `If-None-Match` for conditional requests. Authenticated and preview responses use `Cache-Control: private, no-store`.

## Related

- [Filtering & querying](filtering.md)
- [Authentication](authentication.md)
- [OpenAPI spec](../openapi.yaml)
