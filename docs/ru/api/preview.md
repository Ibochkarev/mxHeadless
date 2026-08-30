# Preview

Параметр `?preview=true` на GET ресурса или страницы включает неопубликованный контент.

## Кто может

| Identity | Условие |
|----------|---------|
| Anonymous | Всегда запрещено |
| Сессия | Право MODX `view_unpublished` |
| API key | Scope `preview` и ACL пользователя key |

## Пример

```bash
curl -s 'https://example.com/api/v1/resources/12?preview=true' \
  -H 'Authorization: Bearer mxh_...'
```

Контекст и политика полей сохраняются. Hidden поля не отдаются.

## Кэш

Ответы preview с `Cache-Control: private, no-store`. Не кладите на CDN.

## См. также

- [Resources](resources.md)
- [Авторизация](authorization.md)
