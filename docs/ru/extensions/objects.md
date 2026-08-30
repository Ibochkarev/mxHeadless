# Объекты в расширениях

Регистрируйте xPDO-классы через `ExtensionApi::registerObject` на событии `OnMxHeadlessRegister`. Полный builder и правила безопасности: [overview](overview.md).

Минимальный пример:

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

После freeze реестра поздняя регистрация бросает `RegistryFrozenException`.

## См. также

- [Обзор](overview.md)
- [Objects API](../api/objects.md)
- [MiniShop3](minishop3.md)
