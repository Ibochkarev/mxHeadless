# Schema

`GET /api/v1/schema` возвращает все объекты из `ObjectRegistry` после bootstrap. По нему клиент узнаёт публичные имена, поля и разрешённые мутации.

Аутентификация не нужна. В ответе только то, что зарегистрировали в коде. Скрытые поля не попадают в registry и в schema не появляются.

## Запрос

```bash
curl -s https://example.com/api/v1/schema
```

## Формат ответа

```json
{
  "data": {
    "objects": {
      "resources": {
        "class": "MODX\\Revolution\\modResource",
        "fields": ["id", "pagetitle", "uri", "..."],
        "filterable": ["parent", "published", "deleted", "..."],
        "sortable": ["id", "menuindex", "pagetitle", "..."],
        "searchable": ["pagetitle", "longtitle", "alias", "..."],
        "required": ["pagetitle"],
        "protected": ["createdby", "editedby", "..."],
        "immutable": ["id", "createdon", "..."],
        "readable": true,
        "creatable": true,
        "updatable": true,
        "deletable": true,
        "relations": []
      }
    }
  },
  "meta": {
    "count": 1
  }
}
```

| Ключ | Смысл |
|------|--------|
| `fields` | Поля, которые могут попасть в ответ при наличии прав |
| `filterable` | Поля для `filter[field][op]` |
| `sortable` | Поля для `sort` |
| `searchable` | Поля для параметра `q` |
| `required` | Обязательны и непустые при create; нельзя очистить до пустого при update |
| `protected` | Нужно field-level право на запись |
| `immutable` | Явная запись → `422` (`Field is immutable`) |
| `readable` / `creatable` / `updatable` / `deletable` | Флаги из `ObjectDefinition` |
| `relations` | Зарегистрированные include (`name`, `target`, `type`) |

Extras добавляют записи в `OnMxHeadlessRegister` или bootstrap компонента. До регистрации `resources` через `CoreObjectBootstrap` `count` может быть `0`.

## Schema и OpenAPI

- **Schema** описывает объекты и возможности query из PHP-определений.
- **OpenAPI** описывает HTTP-пути, коды ответов и тела запросов.

При новом объекте обновляйте оба источника.

## См. также

- [Objects API](objects.md)
- [Фильтрация](filtering.md)
- [Расширения](../extensions/overview.md)
