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
      "endpoints": "/api/v1/meta/endpoints",
      "openapi": "/api/v1/meta/openapi",
      "pages": "/api/v1/pages/{uri}"
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

Discovery does not list every route. For the live route list use [`/meta/endpoints`](meta.md). For route shapes and parameters, use [OpenAPI](../openapi.yaml) or [`/meta/openapi`](meta.md). For registered objects and fields, call [`/schema`](schema.md).

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
