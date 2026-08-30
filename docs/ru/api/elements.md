# Элементы и типы

Read-only shortcut’ы ядра для элементов MODX и content types. Тот же query language, что у [objects](objects.md) / [фильтрации](filtering.md). Нужна аутентификация. Scopes совпадают с префиксом маршрута (`templates.read`, `snippets.read`, …).

| Путь | Объект | Scope |
|------|--------|-------|
| `/templates` | `modTemplate` | `templates.read` |
| `/snippets` | `modSnippet` | `snippets.read` |
| `/tvs` | `modTemplateVar` (определения) | `tvs.read` |
| `/categories` | `modCategory` | `categories.read` |
| `/content_types` | `modContentType` | `content_types.read` |
| `/chunks` | `modChunk` | `chunks.read` |

У каждой коллекции есть `GET /{name}/{id}`. Эквивалент через generic API: `/objects/{name}`.

**Значения** TV у ресурсов по-прежнему через `?tv_fields=` — см. [TVs](tv.md). `/tvs` отдаёт только определения TV.

`include=category` работает для templates, snippets, chunks и TVs. У categories есть `include=parent`.

## Пример

```bash
curl -s -H "Authorization: Bearer $MXH_KEY" \
  'https://example.com/api/v1/templates?limit=20&fields=id,templatename&include=category'
```

## Связанное

- [Objects](objects.md)
- [Schema](schema.md)
