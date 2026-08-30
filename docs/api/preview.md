# Preview

Add `?preview=true` to resource or page GET requests to include unpublished content.

## Who can preview

| Identity | Requirement |
|----------|-------------|
| Anonymous | Always denied |
| Session | MODX permission `view_unpublished` |
| API key | Scope **`preview`** (in addition to `resources.read`) |

For Next.js Draft Mode / Nuxt preview, create a dedicated key:

```bash
php core/components/mxheadless/bin/api-key-create.php \
  --name="next-preview" \
  --scopes="resources.read,preview"
```

`resources.read` alone is not enough. Missing `preview` returns `403`.

## Example

```bash
curl -s 'https://example.com/api/v1/pages/about?preview=true' \
  -H 'Authorization: Bearer mxh_...'
```

Unpublished resources still respect context and field policies. Hidden fields never appear.

## Caching

Preview responses send `Cache-Control: private, no-store`. Do not cache at a CDN.

## Related

- [Resources](resources.md)
- [Pages](pages.md)
- [Authorization](authorization.md)
