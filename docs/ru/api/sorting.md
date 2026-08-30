# Сортировка

Списочные endpoint принимают параметр `sort`. Допустимы только поля из `ObjectDefinition::sorts()` для этого объекта.

Полный синтаксис и лимиты в [фильтрации](filtering.md#sorting).

## Синтаксис

```
GET /api/v1/resources?sort=-published,+pagetitle
```

| Префикс / суффикс | Порядок |
|-------------------|---------|
| `-field` | DESC |
| `+field` или `field` | ASC |
| `field:asc` / `field:desc` | ASC / DESC (алиас как в Nuxt/Strapi) |

Поля через запятую. Порядок в строке совпадает с порядком в SQL.

## По умолчанию

Без `sort`, если у объекта есть поле `id`, компилятор ставит `id ASC`.

## Ошибки

Неизвестное поле сортировки даёт `422`. Имена полей клиента не попадают в сырой SQL.

## Примеры

```bash
curl -s 'https://example.com/api/v1/resources?sort=-id&limit=10'
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=2&sort=+menuindex'
```

## См. также

- [Фильтрация](filtering.md)
- [Пагинация](pagination.md)
- [Schema](schema.md)
