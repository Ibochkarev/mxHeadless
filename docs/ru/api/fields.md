# Выбор полей

Параметр `fields` ограничивает колонки в `data`. Сериализация projection-first: список для `select()` собирается до запроса xPDO, hidden поля в память не попадают.

Работает на GET списков и одного объекта для resources и зарегистрированных объектов.

## Синтаксис

```
GET /api/v1/resources?fields=id,pagetitle,uri,introtext
```

Имена через запятую. Потолок: `mxheadless_max_fields` (по умолчанию 50).

## По умолчанию

Без `fields` в ответ попадают все поля, которые вызывающий может читать, кроме `hiddenFields`. У resources по умолчанию скрыт `properties`.

## Protected поля

Поля из `protectedFields` (например `createdby`) есть в ответе только при отдельном праве. Если поле в `fields` без права, его пропускают, на read это не 422.

## Запись

В `POST` и `PATCH` принимаются только записываемые поля из определения. Неизвестные ключи в JSON дают `422`.

## Примеры

```bash
curl -s 'https://example.com/api/v1/resources?fields=id,pagetitle,uri,introtext&limit=12'
curl -s 'https://example.com/api/v1/pages/about.html?fields=pagetitle,content'
```

## См. также

- [Фильтрация](filtering.md)
- [Schema](schema.md)
- [Авторизация](authorization.md)
