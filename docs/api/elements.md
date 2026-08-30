# Elements and types

Core read-only shortcuts for MODX elements and content types. Same query language as [objects](objects.md) / [filtering](filtering.md). Authentication required. Scopes match the route prefix (`templates.read`, `snippets.read`, …).

| Path | Object | Scope |
|------|--------|-------|
| `/templates` | `modTemplate` | `templates.read` |
| `/snippets` | `modSnippet` | `snippets.read` |
| `/tvs` | `modTemplateVar` (definitions) | `tvs.read` |
| `/categories` | `modCategory` | `categories.read` |
| `/content_types` | `modContentType` | `content_types.read` |
| `/chunks` | `modChunk` | `chunks.read` |

Each collection also has `GET /{name}/{id}`. Equivalent generic URLs: `/objects/{name}`.

TV **values** on resources stay on `?tv_fields=` — see [TVs](tv.md). `/tvs` returns TV definitions only.

`include=category` works for templates, snippets, chunks, and TVs. Categories support `include=parent`.

## Example

```bash
curl -s -H "Authorization: Bearer $MXH_KEY" \
  'https://example.com/api/v1/templates?limit=20&fields=id,templatename&include=category'
```

## Related

- [Objects](objects.md)
- [Chunks](objects.md) (same pattern)
- [Schema](schema.md)
