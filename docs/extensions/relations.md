# Extension relations

Add relations with `ExtensionApi::registerRelation` after the target objects exist in the registry.

```php
use MxHeadless\Definition\RelationDefinition;

$api->registerRelation(
    'products',
    RelationDefinition::create('category')
        ->to('categories')
        ->toOne()
        ->foreignKeyField('parent')
        ->fields(['id', 'pagetitle'])
);
```

Clients load them with `include=category`. Details and `to_many` notes: [overview](overview.md#relations).

## Related

- [Relations API](../api/relations.md)
- [Overview](overview.md)
