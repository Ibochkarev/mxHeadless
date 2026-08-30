# Relations (include)

Load related xPDO objects in the same response with `include`. Relations must be registered on the `ObjectDefinition`; arbitrary graph walks are not allowed.

## Syntax

```
GET /api/v1/resources/5?include=parent
GET /api/v1/resources?include=parent,children
```

Dot notation for nested includes:

```
include=parent,children.tv
```

## Limits

| Limit | Setting | Default |
|-------|---------|---------|
| Relation count | `mxheadless.max_include_relations` | 10 |
| Depth | `mxheadless.max_include_depth` | 2 |

Exceeding limits returns `422`. Unknown relation names also return `422` (`Include not allowed`).

## Loading strategy

To-one relations may use graph queries. To-many relations load paginated parent IDs first, then batch-load children so SQL `LIMIT` on the parent list does not truncate nested arrays incorrectly.

## Authorization

Each relation checks target object read permission. Unauthorized relations are omitted or denied per policy.

Register relations from Extras:

```php
$api->registerRelation('resources', RelationDefinition::create('children')
    ->target('resources')
    ->type('to-many'));
```

See [extensions/relations.md](../extensions/relations.md).

## Related

- [Filtering](filtering.md#includes-relations)
- [Objects](objects.md)
