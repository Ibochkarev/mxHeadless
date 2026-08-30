# Мутации

`POST`, `PUT`, `PATCH` и `DELETE` проходят тот же контур авторизации, что и чтение. Валидной аутентификации мало: нужны scope, право MODX и список записываемых полей в определении объекта.

## Endpoint

| Действие | Resources | Объекты |
|----------|-----------|---------|
| Создание | `POST /api/v1/resources` | `POST /api/v1/objects/{name}` |
| Замена | `PUT /api/v1/resources/{id}` | `PUT /api/v1/objects/{name}/{id}` |
| Частичное | `PATCH /api/v1/resources/{id}` | `PATCH /api/v1/objects/{name}/{id}` |
| Удаление | `DELETE /api/v1/resources/{id}` | `DELETE /api/v1/objects/{name}/{id}` |

## Тело запроса

JSON с именами полей. Принимаются только поля из `ObjectDefinition`. Скрытые поля (`properties`) и immutable системные (`id`, `createdon`, `editedon`, `deletedon`, …) на запись дают `422`. Массивы и объекты → `422`. Boolean-поля принимают `true`/`false`/`0`/`1`; integer (`parent`, `template`, …) — только целые. `class_key` — существующий subclass `modResource`. `parent` — `0` или id существующего ресурса (не self и не потомок, иначе цикл). `content_type` — существующий тип. `template` — `0` или id существующего шаблона. `alias` уникален среди неудалённых siblings в том же контексте (макс. 255 символов).

```bash
curl -s -X POST https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_...' \
  -H 'Content-Type: application/json' \
  -d '{"pagetitle":"Черновик","parent":2,"published":0}'
```

## Сессия и CSRF

Для браузерной сессии на мутациях нужен `X-CSRF-Token`. API keys CSRF не требуют.

## Поведение DELETE

Объекты с полем `deleted` (включая resources) по умолчанию **soft-delete**: `deleted=1`, выставляются `deletedon`/`deletedby`, дочерние ресурсы в дереве помечаются так же. Soft-deleted не попадают в обычные list/get (`deleted = 0`).

`?include_deleted=true` на GET list/get включает корзину (нужны `preview`, `resources.update` или `resources.delete`) и снимает фильтр только published, чтобы черновики в корзине были видны. Восстановление: `PATCH` с `{"deleted":0}` (сбрасывает `deletedon`/`deletedby`). `PATCH` с `{"deleted":true}` — тот же soft-delete, что и `DELETE` (audit + каскад по дереву), нужно право delete. `?force=true` удаляет строку навсегда (`remove()`).

Ответ:

```json
{ "data": { "id": "12", "deleted": true, "permanent": false }, "meta": [] }
```

## Транзакции и webhooks

После успешного save в outbox попадает событие (`resource.created`, `resource.updated`, `resource.deleted` или `{name}.{action}`). Доставка описана в [webhooks](webhooks.md).

## Ошибки

| Код | Частая причина |
|-----|----------------|
| 401 | Нет учётных данных |
| 403 | Нет scope или права MODX |
| 404 | Объект не найден или скрыт |
| 422 | Неизвестное поле, валидация, битый JSON |

## См. также

- [Аутентификация](authentication.md)
- [Авторизация](authorization.md)
- [Resources](resources.md)
- [Объекты](objects.md)
