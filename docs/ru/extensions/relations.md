# Связи в расширениях

Добавляйте связи через `ExtensionApi::registerRelation`, когда объекты уже в реестре.

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

Клиент подгружает их через `include=category`. Подробности и `to_many`: [overview](overview.md#relations).

## См. также

- [Связи API](../api/relations.md)
- [Обзор](overview.md)
