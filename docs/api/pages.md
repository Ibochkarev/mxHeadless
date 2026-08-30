# Pages by URI

Resolve a MODX resource by its URI path instead of numeric ID. Frontend routers that mirror site URLs (Nuxt/Next catch-all routes) usually start here.

```
GET /api/v1/pages/{uri}
```

Public read when the route allows anonymous access and the resource is published. Unpublished pages need preview permission (see [preview](preview.md)).

## Path parameter

`{uri}` is the resource URI without a leading slash. The API tries common aliases so catch-all routes can omit `.html`:

| Request path | Tried URIs (order) |
|--------------|--------------------|
| `/pages/about` | `about`, `about.html`, `about/` |
| `/pages/about.html` | `about.html`, `about` |
| `/pages/blog/post` | `blog/post`, `blog/post.html`, `blog/post/` |
| `/pages/index` or `/pages/` | `index.html`, `index`, `` |
| `/pages/blog/` | `blog/`, `blog`, `blog.html` |

```bash
# Nuxt/Next style (no .html)
curl -s 'https://example.com/api/v1/pages/about?fields=id,pagetitle,content'

# Exact MODX URI still works
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content'
```

Encode path segments. Very long URIs hit `mxheadless_max_uri_bytes` (default 2048).

## Query parameters

Same as [resources](resources.md): `fields`, `include`, `context`, `preview`, `tv_fields`.

```bash
curl -s 'https://example.com/api/v1/pages/about?fields=id,pagetitle,content&include=parent' \
  -H 'X-Context: web'
```

## Context

Pass the MODX context with the `X-Context` header or `context` query parameter. Only values from `mxheadless_allowed_contexts` are accepted (default `web,mgr`).

## Response

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

`meta.uri` is the requested path. `meta.resolved_uri` is the MODX URI that matched.

404 when no candidate matches in the given context, or when the caller cannot view it (preview rules apply).

## Conditional GET / HEAD

`ETag` is set on successful GET and HEAD (including authenticated API keys). Send `If-None-Match` for `304 Not Modified`. HEAD returns the same headers without a body — useful for Nuxt/Next freshness checks before full fetch.

```bash
curl -sI 'https://example.com/api/v1/pages/index'
# ETag: "…"
curl -sI -H 'If-None-Match: "…"' 'https://example.com/api/v1/pages/index'
# HTTP/1.1 304
```

## Related

- [Resources API](resources.md)
- [Preview](preview.md)
- [CORS](../configuration/cors.md) for browser `fetch` from Nuxt/Next
- [Webhooks / ISR](webhooks.md)
