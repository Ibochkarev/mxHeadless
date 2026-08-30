# Страницы по URI

Получить ресурс MODX по URI, а не по числовому ID. Удобно для Nuxt/Next catch-all маршрутов, которые повторяют URL сайта.

```
GET /api/v1/pages/{uri}
```

Публичное чтение, если маршрут открыт для anonymous и ресурс опубликован. Черновики требуют preview (см. [preview](preview.md)).

## Параметр пути

`{uri}` — URI без ведущего слэша. API перебирает типичные алиасы, чтобы catch-all мог обходиться без `.html`:

| Запрос | Кандидаты (по порядку) |
|--------|------------------------|
| `/pages/about` | `about`, `about.html`, `about/` |
| `/pages/about.html` | `about.html`, `about` |
| `/pages/blog/post` | `blog/post`, `blog/post.html`, `blog/post/` |
| `/pages/index` или `/pages/` | `index.html`, `index`, `` |
| `/pages/blog/` | `blog/`, `blog`, `blog.html` |

```bash
# Стиль Nuxt/Next (без .html)
curl -s 'https://example.com/api/v1/pages/about?fields=id,pagetitle,content'

# Точный URI MODX тоже работает
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content'
```

Кодируйте сегменты пути. Длинные URI упираются в `mxheadless_max_uri_bytes` (по умолчанию 2048).

## Query-параметры

Как у [resources](resources.md): `fields`, `include`, `context`, `preview`, `tv_fields`.

```bash
curl -s 'https://example.com/api/v1/pages/about?fields=id,pagetitle,content&include=parent' \
  -H 'X-Context: web'
```

## Контекст

Передайте контекст MODX заголовком `X-Context` или параметром `context`. Принимаются только значения из `mxheadless_allowed_contexts` (по умолчанию `web,mgr`).

## Ответ

```json
{
  "data": {
    "id": 5,
    "pagetitle": "About",
    "uri": "about.html",
    "content": "..."
  },
  "meta": {
    "uri": "about",
    "resolved_uri": "about.html",
    "context": "web"
  }
}
```

`meta.uri` — запрошенный путь. `meta.resolved_uri` — совпавший URI в MODX.

404, если ни один кандидат не найден в контексте или нет прав (включая правила preview).

## Conditional GET / HEAD

На успешных GET и HEAD ставится `ETag` (в том числе с API-ключом). `If-None-Match` даёт `304`. HEAD возвращает те же заголовки без тела — удобно для проверки свежести в Nuxt/Next.

```bash
curl -sI 'https://example.com/api/v1/pages/index'
# ETag: "…"
curl -sI -H 'If-None-Match: "…"' 'https://example.com/api/v1/pages/index'
# HTTP/1.1 304
```

## См. также

- [Resources API](resources.md)
- [Preview](preview.md)
- [CORS](../configuration/cors.md) для browser `fetch` из Nuxt/Next
- [Webhooks / ISR](webhooks.md)
