# Meta catalog

Live discovery of registered routes and OpenAPI for the current install. Public, no authentication.

Static [`docs/openapi.yaml`](../openapi.yaml) remains the repo contract for CI and client generators. Runtime meta is the source of truth on a deployed site (see [ADR 0007](../decisions/0007-live-openapi.md)).

## Endpoints list

```bash
curl -s https://example.com/api/v1/meta/endpoints | jq
```

```json
{
  "data": {
    "endpoints": [
      {
        "name": "resources.list",
        "methods": ["GET"],
        "pattern": "/resources",
        "public": true,
        "permission": "resources.read",
        "object": "resources"
      }
    ]
  },
  "meta": { "count": 20 }
}
```

Includes core routes and Extra routes registered via `registerEndpoint`.

## Live OpenAPI

```bash
curl -s https://example.com/api/v1/meta/openapi | jq '.data.openapi, .data.paths | keys'
```

Response envelope:

```json
{
  "data": { "openapi": "3.0.3", "info": {}, "paths": {}, "components": {} },
  "meta": { "routes": 20, "objects": 3 }
}
```

`data` is a full OpenAPI 3.0.3 document generated from `RouteCollection` and `ObjectRegistry`.

## Raw OpenAPI JSON

For Swagger UI and client generators that expect a root `openapi` field (no envelope):

```bash
curl -s https://example.com/api/v1/meta/openapi.json | jq '.openapi, (.paths | keys)'
```

Content-Type: `application/openapi+json`.

## Swagger UI

```bash
open https://example.com/api/v1/docs
```

Interactive docs (CDN Swagger UI) load `/meta/openapi.json`. Toggle with system setting `mxheadless_swagger_enabled` (default Yes). When disabled, `/docs` returns `404`; raw OpenAPI stays available.

## Related

- [Discovery](discovery.md)
- [Schema](schema.md)
- [Static OpenAPI](../openapi.yaml)
