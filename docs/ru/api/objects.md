# API объектов

Зарегистрированные xPDO-объекты доступны по `/api/v1/objects/{name}`. `{name}` — публичный ключ из `ObjectRegistry`, не имя PHP-класса.

В ядре есть:
- `resources` (CRUD; публичное чтение)
- read-only shortcut’ы: `contexts`, `chunks`, `templates`, `snippets`, `tvs`, `categories`, `content_types`

Extras регистрируют, например, `products` или `locations`. Те же объекты доступны и по `/api/v1/{name}`, если есть shortcut-маршрут.

## Список

```
GET /api/v1/objects/{name}
```

Query-параметры как в [фильтрации](filtering.md). Права задаёт определение объекта и ACL MODX.

```bash
curl -s 'https://example.com/api/v1/objects/products?limit=20&filter[active][eq]=1'
```

## Один объект

```
GET /api/v1/objects/{name}/{id}
```

`{id}` — первичный ключ из определения (строка или число, как в xPDO).

## Создание

```
POST /api/v1/objects/{name}
Content-Type: application/json
```

Нужен флаг `creatable`, scope и право MODX. Для сессии добавьте `X-CSRF-Token`.

## Обновление

```
PUT /api/v1/objects/{name}/{id}
PATCH /api/v1/objects/{name}/{id}
```

Нужен флаг `updatable`.

## Удаление

```
DELETE /api/v1/objects/{name}/{id}
```

Нужен флаг `deletable`.

## Безопасность

Вызов `/objects/modUser` или любого незарегистрированного класса невозможен. Неизвестное `{name}` даёт 404. Лишние поля и фильтры дают 422.

Регистрация из Extra:

```php
$api->registerObject(
    ObjectDefinition::create('products')
        ->class(\MiniShop3\Model\msProduct::class)
        ->readable()
        ->fields(['id', 'pagetitle', 'price'])
        ->filterable(['parent', 'published'])
);
```

См. [extensions/objects.md](../extensions/objects.md).

## См. также

- [Schema](schema.md)
- [Фильтрация](filtering.md)
- [Мутации](mutations.md)
