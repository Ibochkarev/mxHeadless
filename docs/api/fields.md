# Field selection

The `fields` parameter limits which columns appear in `data`. mxHeadless uses projection-first serialization: the query `select()` list is built from allowed fields before xPDO runs, so hidden columns never hit memory.

Applies to list and single-object GET on resources and registered objects.

## Syntax

```
GET /api/v1/resources?fields=id,pagetitle,uri,introtext
```

Comma-separated names. Maximum count: `mxheadless.max_fields` (default 50).

Unknown or hidden field names return `422 Field not allowed`. Protected fields without permission are omitted from the response instead of failing the request.

## Default behavior

When you omit `fields`, the response includes every field the caller may read on that object, except `hiddenFields` from the definition. For resources, `properties` is hidden by default.

## Protected fields

Fields listed in `protectedFields` (for example `createdby`) appear only when the identity has field permission. If you list a protected field without permission, it is skipped rather than causing an error on read.

## Write operations

`POST` and `PATCH` accept only writable fields from the definition. Unknown keys in the JSON body return `422`.

## Examples

```bash
# Card list for a blog index
curl -s 'https://example.com/api/v1/resources?fields=id,pagetitle,uri,introtext&limit=12'

# Single page, content only
curl -s 'https://example.com/api/v1/pages/about.html?fields=pagetitle,content'
```

## Related

- [Filtering](filtering.md)
- [Schema](schema.md)
- [Authorization](authorization.md)
