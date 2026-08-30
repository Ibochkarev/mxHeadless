# CORS

Required when a **browser** on another origin calls the API directly (Nuxt SPA, Next client components). Server-side Nuxt/Next (`$fetch` in server routes, RSC, Route Handlers) does not need CORS.

## Settings

| Key | Default | Notes |
|-----|---------|-------|
| `mxheadless_cors_enabled` | `false` | Master switch |
| `mxheadless_cors_allowed_origins` | empty | Comma-separated exact origins, or `*` |
| `mxheadless_cors_allowed_methods` | GET,POST,PUT,PATCH,DELETE,OPTIONS | |
| `mxheadless_cors_allowed_headers` | Authorization,Content-Type,X-Request-ID,X-CSRF-Token,X-Context,X-API-Key,Idempotency-Key | |
| `mxheadless_cors_expose_headers` | ETag,X-Request-ID,X-RateLimit-*,Idempotency-Replayed | Browser JS can read these |
| `mxheadless_cors_allow_credentials` | `false` | Do not combine with `*` origins |

## Nuxt / Next example

```text
mxheadless_cors_enabled = true
mxheadless_cors_allowed_origins = http://localhost:3000,https://app.example.com
```

OPTIONS preflight returns `204` with ACAO headers when the `Origin` matches. Discovery (`GET /api/v1`) exposes `data.cors.enabled` and `data.cors.allowed_origins` so the frontend can fail fast if CORS is misconfigured.

`Access-Control-Expose-Headers` includes `ETag` so client `fetch` can implement conditional revalidation.

When MiniShop3 Web API runs on the same site, mirror the SPA origin in `ms3_cors_allowed_origins`. Details: [MiniShop3 coexistence](../extensions/minishop3.md#coexistence-mxheadless--ms3-web-api).
