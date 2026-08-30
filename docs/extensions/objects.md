# Extension objects

Register xPDO classes with `ExtensionApi::registerObject` during `OnMxHeadlessRegister`. Full builder reference and security rules live in [overview](overview.md).

Minimal pattern:

```php
$api->registerObject(
    \MxHeadless\Definition\ObjectDefinition::create('products')
        ->setName('products')
        ->class(\MiniShop3\Model\msProduct::class)
        ->fields(['id', 'pagetitle', 'price'])
        ->filterable(['id', 'parent', 'price'])
        ->sorts(['id', 'price'])
        ->readable()
);
```

After bootstrap freezes the registry, late registration throws `RegistryFrozenException`.

## Related

- [Overview](overview.md)
- [Objects API](../api/objects.md)
- [MiniShop3](minishop3.md)
