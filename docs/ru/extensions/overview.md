# Extension API

Сторонние MODX Extras регистрируют API objects без правок core mxHeadless. Регистрация идёт при bootstrap через событие `OnMxHeadlessRegister`.

## Event hook

```php
<?php
/** @var \MODX\Revolution\modX $modx */
switch ($modx->event->name) {
    case 'OnMxHeadlessRegister':
        /** @var \MxHeadless\Extension\ExtensionApi $api */
        $api = $modx->event->params['api'];
        $api->registerObject(
            \MxHeadless\Definition\ObjectDefinition::create('products')
                ->setName('products')
                ->class(\MiniShop3\Model\msProduct::class)
                ->fields(['id', 'pagetitle', 'price', 'article'])
                ->filterable(['id', 'price', 'parent'])
                ->sorts(['id', 'price', 'pagetitle'])
                ->searchable(['pagetitle', 'article'])
                ->readable()
        );
        break;
}
```

После всех listeners mxHeadless замораживает registry. Поздняя регистрация бросает `RegistryFrozenException`.

Middleware и request/response hooks: [lifecycle hooks](lifecycle-hooks.md).

## Методы ExtensionApi

| Метод | Назначение |
|--------|---------|
| `registerObject(ObjectDefinition)` | Expose xPDO class под public name |
| `registerRelation(string $object, RelationDefinition)` | Добавить или обновить relation на object |
| `registerEndpoint(name, methods, pattern, callable $handler, ...)` | Custom route с PHP handler |

В будущем: `registerSerializer`, `registerFilter`, `registerPermission`, `registerWebhookEvent`.

## ObjectDefinition

Fluent builder:

```php
ObjectDefinition::create('locations')
    ->setName('locations')
    ->class(YmlLocation::class)
    ->fields(['id', 'title', 'lat', 'lng'])
    ->filterable(['id', 'city_id'])
    ->sorts(['id', 'title'])
    ->readable()
    ->creatable(false)
    ->hiddenFields(['internal_note'])
    ->protectedFields(['owner_id'])
    ->contexts(['web']);
```

Флаги:

- `readable`, `creatable`, `updatable`, `deletable`
- `hiddenFields` — never selected or serialized
- `protectedFields` — нужен field-level permission
- `contexts` — ограничение MODX contexts

## Relations

```php
use MxHeadless\Definition\RelationDefinition;

$api->registerRelation('products', RelationDefinition::create('category')
    ->to('categories')
    ->toOne()
    ->foreignKeyField('parent')
    ->fields(['id', 'pagetitle'])
);
```

Типы:

- `to_one` — один related object через `getObjectGraph`
- `to_many` — paginated batch load без SQL limit bugs

## Custom endpoints

```php
$api->registerEndpoint(
    name: 'store.locator',
    methods: ['GET'],
    pattern: '/store/locator',
    handler: 'store.locator',
    permission: 'store.read',
    public: true,
);
```

Handler реализуйте в Extra и подключите через `RouteDispatcher` или dedicated service.

## HTTP surface

Зарегистрированные objects доступны на:

```
GET    /api/v1/objects/{name}
GET    /api/v1/objects/{name}/{id}
POST   /api/v1/objects/{name}
PUT    /api/v1/objects/{name}/{id}
PATCH  /api/v1/objects/{name}/{id}
DELETE /api/v1/objects/{name}/{id}
```

Authorization scopes: `{name}.{action}`, если не переопределено.

## Правила безопасности для Extras

1. Регистрируйте только fields, которые клиент должен видеть
2. Держите `filterable` и `sorts` минимальными
3. Не expose manager-only classes (`modUser`, processors). Core отдаёт `contexts` read-only с ACL
4. `hiddenFields` для internal columns
5. Прогоняйте mxHeadless security suite

## Schema discovery

Клиенты introspect через `GET /api/v1/schema`. Field lists держите стабильными. Additive changes обратно совместимы.

## Примеры

- [YandexMapsLocator](yandex-maps-locator.md)
- [MiniShop3](minishop3.md)

## См. также

- [Architecture](../architecture.md) — ObjectRegistry и query engine
- [Contributing](../contributing/development.md)
