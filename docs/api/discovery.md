# Discovery

`GET /api/v1` returns basic API metadata. Use it to confirm that the gateway is up and to read the API version before you wire a frontend or CI check.

No authentication required.

## Request

```bash
curl -s https://example.com/api/v1
```

The path prefix comes from the system setting `mxheadless.api.prefix` (default `/api`). Version `v1` is fixed in the router.

## Response

```json
{
  "data": {
    "name": "mxHeadless",
    "version": "1.0.11",
    "api": "/api/v1",
    "cors": {
      "enabled": true,
      "allowed_origins": ["http://localhost:3000"]
    },
    "links": {
      "health": "/api/v1/health",
      "schema": "/api/v1/schema",
      "docs": "/api/v1/docs",
      "endpoints": "/api/v1/meta/endpoints",
      "openapi": "/api/v1/meta/openapi",
      "openapi_json": "/api/v1/meta/openapi.json",
      "auth_token": "/api/v1/auth/token",
      "resources": "/api/v1/resources",
      "pages": "/api/v1/pages/{uri}",
      "contexts": "/api/v1/contexts",
      "chunks": "/api/v1/chunks",
      "templates": "/api/v1/templates",
      "snippets": "/api/v1/snippets",
      "tvs": "/api/v1/tvs",
      "categories": "/api/v1/categories",
      "content_types": "/api/v1/content_types",
      "objects": "/api/v1/objects/{name}"
    }
  },
  "meta": {}
}
```

| Field | Meaning |
|-------|---------|
| `name` | Package identifier |
| `version` | mxHeadless release version |
| `api` | Base path for all v1 routes |
| `cors` | CORS switch and configured origins (for Nuxt/Next bootstrap) |
| `links` | Related public discovery URLs |

Discovery does not list every route. For the live route list use [`/meta/endpoints`](meta.md). For route shapes and parameters, use [OpenAPI](../openapi.yaml), [`/meta/openapi`](meta.md), or interactive [`/docs`](meta.md) (Swagger UI). For registered objects and fields, call [`/schema`](schema.md).

## Typical use

- Health dashboards that only need to know the API responds
- Build scripts that print the base URL for generated clients
- First manual check after install (before `GET /health` and `GET /schema`)

## Related

- [Health](health.md)
- [Schema](schema.md)
- [Meta catalog](meta.md)
- [OpenAPI](../openapi.yaml)
- [CORS](../configuration/cors.md)
