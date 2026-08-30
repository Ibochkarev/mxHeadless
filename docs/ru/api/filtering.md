# Фильтрация и запросы

mxHeadless превращает query-параметры HTTP в типизированный `ObjectQuery`. Компилятор сверяет каждое поле с whitelist в определении объекта и строит xPDO-запрос только с bindings.

Работает на `/api/v1/resources`, `/api/v1/objects/{name}` и других списках.

## Пагинация

| Параметр | По умолчанию | Потолок |
|----------|--------------|---------|
| `limit` | 20 | `mxheadless.max_limit` (100); целое ≥ 1 |
| `offset` | 0 | `mxheadless.max_offset` (100000); неотрицательное целое |
| `page` | — | Если нет `offset`: `offset = (page - 1) * limit`; page ≥ 1 |

Невалидные `limit`, `offset` или `page` → `422`. Значения выше max обрезаются.

```
GET /api/v1/resources?limit=50&offset=100
GET /api/v1/resources?limit=50&page=3
```

В `meta` приходят `total`, `count`, `limit`, `offset` и `has_more`. В корне ответа — `links` (`self`, опционально `next` / `prev`).

## Выбор полей

```
GET /api/v1/resources?fields=id,pagetitle,uri
```

Список через запятую. Максимум `mxheadless.max_fields` (50). Неизвестное поле: `422 Field not allowed`. Пустой `fields` отдаёт все разрешённые нескрытые поля.

## Сортировка

```
GET /api/v1/resources?sort=-published,+pagetitle
```

| Префикс | Направление |
|---------|-------------|
| `-` | DESC |
| `+` или нет | ASC |

Только поля из `ObjectDefinition::sorts()`. По умолчанию `id ASC`, если поле `id` есть. Направление: `-field` (DESC), `+field` / `field` (ASC), либо `field:asc` / `field:desc`.

## Фильтры

```
filter[field][operator]=value
```

Альтернативный ключ: `filters` (та же структура).

### Операторы

| Оператор | Смысл | Пример |
|----------|-------|--------|
| `eq` | равно | `filter[published][eq]=1` |
| `neq` | не равно | `filter[parent][neq]=0` |
| `gt` | больше | `filter[id][gt]=100` |
| `gte` | больше или равно | `filter[id][gte]=100` |
| `lt` | меньше | `filter[id][lt]=50` |
| `lte` | меньше или равно | `filter[id][lte]=50` |
| `like` | SQL LIKE | `filter[pagetitle][like]=%news%` |
| `in` | в списке | `filter[parent][in]=1,2,3` |
| `not_in` | не в списке | `filter[id][not_in]=5,6` |
| `null` | IS NULL | `filter[deletedon][null]=1` |
| `not_null` | IS NOT NULL | `filter[publishedon][not_null]=1` |

Несколько фильтров объединяются через AND. Только `filterable` поля. Неизвестное поле: `422 Filter not allowed`.

### Безопасность

Имена полей в filter и sort проходят whitelist. SQL injection в имени поля отсекается до компиляции. Значения всегда через bindings.

## Поиск

```
GET /api/v1/resources?q=installation
```

`LIKE %term%` по `searchable` полям (OR). Без searchable полей: `422 Search not supported`.

## Include (связи)

```
GET /api/v1/resources?include=parent,children
```

| Лимит | Настройка |
|-------|-----------|
| Число relations | `mxheadless.max_include_relations` (10) |
| Глубина | `mxheadless.max_include_depth` (2) |

Точка для вложенности: `include=parent,children.tv`. Связи регистрируют в определении объекта. Неизвестные имена → `422`.

## Контекст и preview

```
GET /api/v1/resources?context=web&preview=false
```

Или заголовок `X-Context: web`. Неверный контекст: `422 Invalid context`.

## Опубликованный контент по умолчанию

Для полей `published` и `deleted` списки автоматически добавляют `published = 1` и `deleted = 0 OR NULL`, если нет авторизованного `preview=true`.

## Generic-объекты

```
GET /api/v1/objects/products?filter[price][gte]=100&sort=-createdon
```

Тот же синтаксис. Возможности объекта задаёт Extra при регистрации.

## Schema

```
GET /api/v1/schema
```

Список объектов с `fields`, `filterable`, `sortable`, `searchable` для динамических UI.

## Примеры

[cURL](../examples/curl.md), [OpenAPI](../openapi.yaml).
