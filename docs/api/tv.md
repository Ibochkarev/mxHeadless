# Template variables (TVs)

MODX template variables are opt-in. They never appear unless you request them.

## Request TVs

```
GET /api/v1/resources/5?tv_fields=image,subtitle
```

Or combine with includes when TV loading is wired on the resource serializer path.

`tv_fields` is a comma-separated list of TV names. Unknown or unauthorized TVs are skipped.

## Implementation

`ModxTvProvider` reads values through `modTemplateVar::getValue()` for the resource ID. Field-level authorization still applies.

## Performance

Each TV may trigger extra queries. Keep `tv_fields` minimal on list endpoints. Prefer single-resource GET for heavy TV sets.

## Related

- [Resources](resources.md)
- [Fields](fields.md)
