# Связи (include)

Подгрузка связанных xPDO-объектов в том же ответе через `include`. Связь должна быть в `ObjectDefinition`. Произвольный обход графа недоступен.

## Синтаксис

```
GET /api/v1/resources/5?include=parent
GET /api/v1/resources?include=parent,children
```

Вложенность через точку:

```
include=parent,children.tv
```

## Лимиты

| Лимит | Настройка | По умолчанию |
|-------|-----------|--------------|
| Число relations | `mxheadless_max_include_relations` | 10 |
| Глубина | `mxheadless_max_include_depth` | 2 |

Превышение даёт `422`. Неизвестное имя связи тоже `422` (`Include not allowed`).

## Загрузка

To-one может идти через graph query. To-many: сначала ID родителей с пагинацией, потом batch детей, чтобы `LIMIT` на списке родителей не обрезал вложенные массивы.

## Авторизация

На каждую связь проверяется право чтения целевого объекта.

Регистрация из Extra: см. [extensions/relations.md](../extensions/relations.md).

## См. также

- [Фильтрация](filtering.md)
- [Объекты](objects.md)
