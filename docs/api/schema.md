# Schema

`GET /api/v1/schema` lists every object registered in `ObjectRegistry` after bootstrap. Clients use it to learn which public names exist, which fields are exposed, and which mutations are allowed.

No authentication required. The response still respects what was registered: hidden internals never appear because they were never registered.

## Request

```bash
curl -s https://example.com/api/v1/schema
```

## Response shape

```json
{
  "data": {
    "objects": {
      "resources": {
        "class": "MODX\\Revolution\\modResource",
        "fields": ["id", "pagetitle", "uri", "..."],
        "filterable": ["parent", "published", "deleted", "..."],
        "sortable": ["id", "menuindex", "pagetitle", "..."],
        "searchable": ["pagetitle", "longtitle", "alias", "..."],
        "required": ["pagetitle"],
        "protected": ["createdby", "editedby", "..."],
        "immutable": ["id", "createdon", "..."],
        "readable": true,
        "creatable": true,
        "updatable": true,
        "deletable": true,
        "relations": []
      }
    }
  },
  "meta": {
    "count": 1
  }
}
```

| Key | Meaning |
|-----|---------|
| `fields` | Columns that may appear in responses when the caller is allowed |
| `filterable` | Fields accepted in `filter[field][op]` |
| `sortable` | Fields accepted in `sort` |
| `searchable` | Fields used by `q` |
| `required` | Must be present and non-empty on create; cannot be cleared to empty on update |
| `protected` | Need field-level write permission |
| `immutable` | Explicit write returns `422` (`Field is immutable`) |
| `readable` / `creatable` / `updatable` / `deletable` | Mutation flags from `ObjectDefinition` |
| `relations` | Registered includes (`name`, `target`, `type`) |

Extras add entries during `OnMxHeadlessRegister` or component bootstrap. Until registration runs, `count` may be `0` on a fresh install before `CoreObjectBootstrap` registers `resources`.

## Difference from OpenAPI

- **Schema** describes data objects and query capabilities from PHP definitions.
- **OpenAPI** describes HTTP paths, status codes, and request bodies.

Keep both in sync when you ship a new registered object.

## Related

- [Objects API](objects.md)
- [Filtering](filtering.md)
- [Extensions overview](../extensions/overview.md)
