# Интеграция YandexMapsLocator

[YandexMapsLocator](https://github.com/modx-pro/YandexMapsLocator) хранит точки карты в custom xPDO models. mxHeadless expose их через Extension API без зависимости в core.

## Зарегистрированные objects

| Public name | xPDO class | Описание |
|-------------|------------|----------|
| `locations` | `YmlLocation` | Точка с координатами и metadata |
| `cities` | `YmlCity` | Группировка городов |

Точные class names следуют namespace models Extra.

## Пример регистрации

В plugin YandexMapsLocator на `OnMxHeadlessRegister`:

```php
<?php
use MxHeadless\Definition\ObjectDefinition;
use MxHeadless\Definition\RelationDefinition;

/** @var \MxHeadless\Extension\ExtensionApi $api */
$api = $modx->event->params['api'];

$api->registerObject(
    ObjectDefinition::create('locations')
        ->setName('locations')
        ->class('YandexMapsLocator\\Model\\YmlLocation')
        ->fields(['id', 'title', 'address', 'lat', 'lng', 'city_id', 'published'])
        ->filterable(['id', 'city_id', 'published'])
        ->sorts(['id', 'title'])
        ->searchable(['title', 'address'])
        ->readable()
);

$api->registerObject(
    ObjectDefinition::create('cities')
        ->setName('cities')
        ->class('YandexMapsLocator\\Model\\YmlCity')
        ->fields(['id', 'name', 'slug'])
        ->filterable(['id', 'slug'])
        ->sorts(['id', 'name'])
        ->searchable(['name'])
        ->readable()
);

$api->registerRelation('locations', RelationDefinition::create('city')
    ->to('cities')
    ->toOne()
    ->foreignKeyField('city_id')
    ->fields(['id', 'name', 'slug'])
);
```

## Использование API

### Список locations в городе

```bash
curl -s 'https://example.com/api/v1/objects/locations?filter[city_id][eq]=3&filter[published][eq]=1&fields=id,title,lat,lng&include=city'
```

### Одна location

```bash
curl -s https://example.com/api/v1/objects/locations/42
```

### Поиск по адресу

```bash
curl -s 'https://example.com/api/v1/objects/locations?q=Lenina&limit=20'
```

## Виджет карты на фронте

Типичный flow в Nuxt:

1. Cities: `GET /api/v1/objects/cities?sort=name`
2. Locations для выбранного city по `city_id` или bounding-box filter (если зарегистрирован custom filter)
3. Markers из полей `lat` / `lng`

## Permissions

По умолчанию public read на published locations. Ограничение:

- Object-level MODX ACL policies
- API key scopes: `objects.locations.read`
- `protectedFields` для internal routing metadata

## Webhooks

Подписка в Manager или в `mxheadless_webhook_subscriptions` на events вроде `locations.created` и `locations.updated`. mxHeadless ставит signed deliveries в outbox при matching mutations.

## См. также

- [Extension overview](overview.md)
- [Filtering](../api/filtering.md)
