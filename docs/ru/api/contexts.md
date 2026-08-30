# Контексты

Контексты MODX разделяют сайты, языки, web и mgr. mxHeadless берёт активный контекст из запроса и проверяет whitelist.

## Каталог контекстов (read-only)

В ядре зарегистрирован объект `contexts` (`modContext`). Список и чтение метаданных только для контекстов, к которым у вызывающего есть доступ.

```
GET /api/v1/contexts
GET /api/v1/contexts/{key}
GET /api/v1/objects/contexts
GET /api/v1/objects/contexts/{key}
```

Нужна аутентификация. Scope: `contexts.read`. Каждая запись фильтруется по ACL MODX (`context_{key}` для сессии, `context.{key}` для API key). Контексты вне `mxheadless.allowed_contexts` не попадают в ответ.

Поля: `key`, `name`, `description`, `rank`.

Настройки контекста (URL, стартовая страница):

```
GET /api/v1/contexts/{key}/settings
```

Тот же scope `contexts.read` и ACL, что для каталога. В ответе: `site_url`, `base_url`, `http_host`, `site_start`, `error_page`, `unauthorized_page`, `cultureKey`, `locale`.

## Как передать

Параметр:

```
GET /api/v1/resources?context=web
```

Заголовок (удобнее для кэша):

```
X-Context: web
```

Если не указано, используется текущий контекст MODX, обычно `web`.

## Whitelist

Принимаются только контексты из `mxheadless.allowed_contexts` (по умолчанию `web,mgr`). Остальные дают `422 Invalid context`.

Определение объекта может сузить список (у resources в ядре `web` и `mgr`).

Запись `context_key` у ресурса: контекст из whitelist, который MODX может загрузить в текущем запросе. Несуществующие и незагружаемые (часто `mgr` с web front controller) → `422`, не `500`. Мутации по id находят строку в любом контексте, затем проверяют доступ `context.{key}` / `context_{key}`.

## Авторизация

Чтение чужого контекста требует права `context_{key}` или ACL. API key наследует права пользователя key.

## См. также

- [Resources](resources.md)
- [Настройки](../configuration/settings.md)
