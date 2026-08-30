# YandexMapsLocator integration

[YandexMapsLocator](https://github.com/modx-pro/YandexMapsLocator) stores map locations as custom xPDO models. mxHeadless exposes them through the Extension API without any core dependency.

## Registered objects

| Public name | xPDO class | Description |
|-------------|------------|-------------|
| `locations` | `YmlLocation` | Map point with coordinates and metadata |
| `cities` | `YmlCity` | City grouping for locations |

Exact class names follow the Extra's model namespace.

## Registration example

In the YandexMapsLocator plugin on `OnMxHeadlessRegister`:

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

## API usage

### List locations in a city

```bash
curl -s 'https://example.com/api/v1/objects/locations?filter[city_id][eq]=3&filter[published][eq]=1&fields=id,title,lat,lng&include=city'
```

### Get single location

```bash
curl -s https://example.com/api/v1/objects/locations/42
```

### Search by address

```bash
curl -s 'https://example.com/api/v1/objects/locations?q=Lenina&limit=20'
```

## Frontend map widget

Typical Nuxt flow:

1. Fetch cities: `GET /api/v1/objects/cities?sort=name`
2. Fetch locations for selected city with bounding-box filter (if registered as custom filter) or `city_id`
3. Render markers from `lat` / `lng` fields

## Permissions

Default: public read on published locations. Restrict with:

- Object-level MODX ACL policies
- API key scopes: `objects.locations.read`
- `protectedFields` for internal routing metadata

## Webhooks

Create a webhook subscription in the Manager (or directly in `mxheadless_webhook_subscriptions`) with events such as `locations.created` and `locations.updated`. mxHeadless enqueues signed deliveries to the subscription URL when matching mutations fire.

## Related

- [Extension overview](overview.md)
- [Filtering](../api/filtering.md)
