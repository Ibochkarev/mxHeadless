# Поиск

Поиск по подстроке через параметр `q` на списках.

```
GET /api/v1/resources?q=installation
```

## Поведение

`QueryParser` строит `LIKE %term%` по полям из `searchable` в определении объекта. Поля объединяются через OR.

У `resources` в ядре searchable: `pagetitle`, `longtitle`, `description`, `introtext`, `alias`, `uri`.

## Лимиты

Действуют те же `limit` и `offset`, что у списка. Короткий термин может дать много строк. Сужайте выборку через `filter`.

## Ошибки

Без searchable полей в определении: `422 Search not supported`.

## См. также

- [Фильтрация](filtering.md)
- [Schema](schema.md)
