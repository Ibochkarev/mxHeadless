# Installation

mxHeadless targets MODX Revolution **3.2.3+** and PHP **8.1+**.

## Package install

### Via MODX Package Manager

1. Build the transport package (or download a release):

   ```bash
   cd _build
   php build.php
   ```

2. In MODX Manager, open **Packages → Install Package** and upload the `.transport.zip`.

3. Complete the installer. mxHeadless registers its namespace, plugin, and default system settings.

### Manual (development)

Symlink or copy `core/components/mxheadless/` into your MODX install and run Composer inside the component:

```bash
cd core/components/mxheadless
composer install --no-dev --optimize-autoloader
```

Ensure the namespace `mxheadless` points to the component path in **System → Namespaces**.

## HTTP gateway

mxHeadless intercepts requests before resource resolution.

### Primary: `OnHandleRequest` plugin

Default API prefix: `/api`. Requests to `/api/v1/...` are handled by `MxHeadless\Application`.

Configure in **System Settings** (namespace `mxheadless`):

| Setting | Default | Purpose |
|---------|---------|---------|
| `mxheadless.api.prefix` | `/api` | URL prefix |
| `mxheadless.allowed_contexts` | `web,mgr` | Contexts clients may request |
| `mxheadless.max_limit` | `100` | Max page size |
| `mxheadless.debug` | `false` | Verbose problem+json (dev only) |

### Fallback: `api.php`

If friendly URLs are unavailable, use:

```
https://your-site.example/assets/components/mxheadless/api.php/v1/health
```

Both entry points share the same middleware pipeline and services.

## Friendly URLs

Ensure MODX friendly URLs are enabled. The plugin matches the configured prefix; no dedicated resource document is required.

Behind nginx or Apache, proxy all `/api/*` traffic to MODX as usual. Configure [trusted proxies](../security.md) when using a load balancer.

## Verify

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
curl -s 'https://your-site.example/api/v1/resources?limit=5&filter[published][eq]=1' | jq
```

Expected discovery payload:

```json
{
  "data": {
    "name": "mxHeadless",
    "version": "1.0.11",
    "api": "/api/v1"
  },
  "meta": {}
}
```

## CORS (optional)

CORS is disabled by default. Enable and set allowed origins when the frontend runs on another domain. Never use `*` with credentials.

## Next steps

- [Resources API](../api/resources.md)
- [Authentication](../api/authentication.md)
- [Production security checklist](../security.md)
