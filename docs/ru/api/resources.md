# Resources API

Ресурсы MODX — полноценные REST-объекты по адресу `/api/v1/resources`. Страницы также доступны по URI: `/api/v1/pages/{uri}`.

## Список ресурсов

```
GET /api/v1/resources
```

Scope: `resources.read` (публичное чтение, если настроено).

### Query-параметры

| Параметр | Пример | Описание |
|----------|--------|----------|
| `limit` | `20` | Размер страницы; целое ≥ 1, потолок `mxheadless.max_limit` (по умолчанию 100) |
| `offset` | `0` | Пропустить строк; неотрицательное целое |
| `page` | `2` | Страница с 1, если `offset` не задан |
| `fields` | `id,pagetitle,uri` | Разреженный набор полей |
| `sort` | `-published,+pagetitle` или `menuindex:asc` | Сортировка (`-` DESC, `+` ASC или `:asc`/`:desc`) |
| `filter[field][op]` | `filter[parent][eq]=5` | См. [фильтрацию](filtering.md) |
| `include` | `parent,tv` | Только зарегистрированные связи (неизвестные → `422`) |
| `q` | `news` | Полнотекстовый поиск по searchable-полям |
| `context` | `web` | Контекст MODX |
| `preview` | `true` | Включить неопубликованные (нужны права) |

### Пример

```bash
# Список карточек
curl -s 'https://example.com/api/v1/resources?limit=10&filter[parent][eq]=2&filter[published][eq]=1&fields=id,pagetitle,uri&sort=-id' \
  -H 'Accept: application/json'

# Навигация Nuxt/Next (корневое меню)
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=0&filter[published][eq]=1&filter[hidemenu][eq]=0&fields=id,pagetitle,uri,menuindex,isfolder&sort=menuindex'
```

### Ответ

```json
{
  "data": [
    { "id": 5, "pagetitle": "About", "uri": "about/" }
  ],
  "meta": {
    "total": 42,
    "limit": 10,
    "offset": 0
  },
  "links": {
    "self": "/api/v1/resources?limit=10&offset=0",
    "next": "/api/v1/resources?limit=10&offset=10"
  }
}
```

## Получить один ресурс

```
GET /api/v1/resources/{id}
```

Возвращает один объект ресурса. Неопубликованные ресурсы требуют права `view_unpublished` или preview scope.

```bash
curl -s https://example.com/api/v1/resources/5
```

## Страница по URI

```
GET /api/v1/pages/{uri}
```

Находит ресурс по URI-пути (без ведущего `/` в сегменте пути). Удобно для фронтенд-роутеров, которые повторяют URL-структуру MODX.

```bash
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content'
```

## Создать ресурс

```
POST /api/v1/resources
Content-Type: application/json
```

Требует scope `resources.create` и права MODX. При сессионной аутентификации нужен `X-CSRF-Token`. Поле `pagetitle` обязательно и не может быть пустым.

```bash
curl -s -X POST https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_abc123_yoursecret' \
  -H 'Content-Type: application/json' \
  -d '{"pagetitle":"New page","parent":2,"template":1,"published":0}'
```

## Обновить ресурс

```
PUT /api/v1/resources/{id}
PATCH /api/v1/resources/{id}
```

- `PUT` требует все `required` поля из schema (например `pagetitle`); остальные поля мержатся как у `PATCH`
- `PATCH` применяет частичное обновление

Scope: `resources.update`. Soft-deleted ресурсы можно обновлять (например `{"deleted":0}` для восстановления).

## Удалить ресурс

```
DELETE /api/v1/resources/{id}
DELETE /api/v1/resources/{id}?force=true
```

По умолчанию soft-delete: `deleted=1`, выставляются `deletedon` / `deletedby`, дочерние узлы дерева ресурсов помечаются так же. Soft-deleted строки не попадают в обычные list/get, пока не передадите `include_deleted=true` (нужны `preview`, `resources.update` или `resources.delete`; также снимает фильтр только published). Повторный soft `DELETE` того же id даёт `404`.

Восстановление: `PATCH /resources/{id}` с `{"deleted":0}`. `force=true` удаляет строку навсегда (в том числе уже soft-deleted). Scope: `resources.delete`.

См. [мутации](mutations.md#delete-behavior).

## Template variables (TV)

TV никогда не включаются по умолчанию. Запросите явно:

```
GET /api/v1/resources/5?include=tv&tv_fields=image,subtitle
```

Загрузка TV идёт через `TvProviderInterface` с авторизацией на уровне полей.

## Контекст

Передайте контекст через query или заголовок:

```bash
curl -s https://example.com/api/v1/resources/5 -H 'X-Context: web'
```

Принимаются только контексты из `mxheadless.allowed_contexts`.

## Кэширование

Публичные GET-ответы могут содержать `ETag` и `Cache-Control`. Отправляйте `If-None-Match` для conditional requests. Аутентифицированные и preview-ответы используют `Cache-Control: private, no-store`.

## См. также

- [Фильтрация и запросы](filtering.md)
- [Аутентификация](authentication.md)
- [OpenAPI spec](../../openapi.yaml)
