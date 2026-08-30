# mxHeadless Documentation

mxHeadless is a REST API gateway for [MODX Revolution 3](https://modx.com/). It exposes resources, pages, and registered xPDO objects as JSON for headless frontends (Nuxt, Next.js, SvelteKit, mobile apps, and custom clients).

## Quick links

| Topic | Description |
|-------|-------------|
| [Installation](installation/install.md) | Package install, gateway setup, first request |
| [Requirements](installation/requirements.md) | PHP, MODX, xPDO compatibility matrix |
| [Web server](installation/web-server.md) | Apache, Nginx, fallback entry |
| [Resources API](api/resources.md) | CRUD for MODX resources and pages by URI |
| [Authentication](api/authentication.md) | Sessions, API keys, OAuth tokens, CSRF |
| [Filtering & querying](api/filtering.md) | Filters, sort, pagination, fields, includes |
| [Extensions](extensions/overview.md) | Register custom objects from Extras ([MiniShop3](extensions/minishop3.md)) |
| [OpenAPI spec](openapi.yaml) | Machine-readable API description |
| [Architecture](architecture.md) | Internal design and security model |
| [Security](security.md) | Threat model and production checklist |
| [Contributing](contributing/development.md) | Local dev, tests, release workflow |
| [Testing checklist](contributing/testing.md) | Automatic gates and manual verification matrix |

## API reference

- [Discovery](api/discovery.md) · [Health](api/health.md) · [Schema](api/schema.md) · [Meta catalog](api/meta.md)
- [Resources](api/resources.md) · [Pages](api/pages.md) · [Objects](api/objects.md) · [Elements](api/elements.md)
- [Sorting](api/sorting.md) · [Pagination](api/pagination.md) · [Fields](api/fields.md) · [Relations](api/relations.md)
- [TVs](api/tv.md) · [Media](api/media.md) · [Contexts](api/contexts.md) · [Search](api/search.md)
- [Mutations](api/mutations.md) · [Errors](api/errors.md) · [Preview](api/preview.md)
- [API keys](api/api-keys.md) · [OAuth tokens](api/auth.md) · [Authorization](api/authorization.md)
- [Rate limiting](api/rate-limiting.md) · [HTTP caching](api/http-caching.md) · [Idempotency](api/idempotency.md) · [Webhooks](api/webhooks.md)

## Configuration

- [Settings](configuration/settings.md) · [CORS](configuration/cors.md) · [Trusted proxies](configuration/trusted-proxies.md) · [Limits](configuration/limits.md)

## Operations

- [Deployment](operations/deployment.md) · [Production checklist](operations/production-checklist.md) · [Cache](operations/cache.md) · [Workers](operations/workers.md) · [ISR revalidation](operations/isr-revalidation.md) · [Logging](operations/logging.md)
- [Monitoring](operations/monitoring.md) · [Performance](operations/performance.md) · [Key rotation](operations/key-rotation.md)
- [Incident response](operations/incident-response.md) · [Troubleshooting](operations/troubleshooting.md)

## API base URL

All v1 endpoints live under:

```
https://your-site.example/api/v1
```

Discovery and health endpoints are public:

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
```

## Response format

Successful responses use a consistent envelope:

```json
{
  "data": {},
  "meta": {
    "total": 100,
    "count": 20,
    "limit": 20,
    "offset": 0,
    "has_more": true
  },
  "links": {
    "self": "/api/v1/resources?limit=20&offset=0",
    "next": "/api/v1/resources?limit=20&offset=20"
  }
}
```

Errors follow [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) (`application/problem+json`).

## Core concepts

**Closed by default.** Nothing is exposed until registered in `ObjectRegistry` with explicit fields, filters, and permissions.

**No arbitrary class access.** Public names like `resources` or `products` map to registered `ObjectDefinition` instances.

**Whitelisted queries.** `QueryParser` and `XpdoQueryCompiler` validate every field, filter, and sort against the definition.

## Examples

- [cURL](examples/curl.md) · [JavaScript](examples/javascript.md) · [TypeScript](examples/typescript.md)
- [Nuxt](examples/nuxt.md) · [Next.js](examples/nextjs.md) · [SvelteKit](examples/sveltekit.md)

## Language

Russian documentation: [docs/ru/index.md](ru/index.md).

## License

GPL-2.0-or-later. mxHeadless is fully open source with no feature tiers or artificial limits.

## Contributing

Contributors: see [WRITING.md](WRITING.md) (modx-docs + humanizer).

## Comparison and roadmap

- [mxHeadless vs mxApi](comparison/mxapi.md): when to use which package
- [mxApi adoption roadmap](roadmap/mxapi-adoption.md): planned infrastructure parity on MODX 3
