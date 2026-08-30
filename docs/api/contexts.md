# Contexts

MODX contexts isolate sites, languages, or manager vs web. mxHeadless reads the active context from the request and enforces a whitelist.

## Context catalog (read-only)

Core registers `contexts` (`modContext`) as a read-only object. List and fetch metadata for contexts the caller may access.

```
GET /api/v1/contexts
GET /api/v1/contexts/{key}
GET /api/v1/objects/contexts
GET /api/v1/objects/contexts/{key}
```

Authentication is required. Scope: `contexts.read`. Each record is filtered by MODX ACL (`context_{key}` for session users, `context.{key}` for API keys). Contexts outside `mxheadless.allowed_contexts` are never returned.

Fields: `key`, `name`, `description`, `rank`.

Context settings (URLs, start page):

```
GET /api/v1/contexts/{key}/settings
```

Same `contexts.read` scope and ACL as the catalog. Response includes `site_url`, `base_url`, `http_host`, `site_start`, `error_page`, `unauthorized_page`, `cultureKey`, `locale`.

## How to set

Query parameter:

```
GET /api/v1/resources?context=web
```

Header (preferred for caches):

```
X-Context: web
```

Default when omitted: current MODX context, usually `web`.

## Whitelist

Only contexts listed in `mxheadless.allowed_contexts` are accepted (default `web,mgr`). Others return `422 Invalid context`.

Object definitions may further restrict contexts (resources allow `web` and `mgr` in core bootstrap).

Writing `context_key` on a resource must use a context from the whitelist that MODX can load in the current request. Unknown contexts and contexts that cannot be loaded (often `mgr` from a web front controller) return `422`, not `500`. Mutations by id find the row across contexts, then check `context.{key}` / `context_{key}` access.

## Authorization

Cross-context reads require `context_{key}` permission or equivalent ACL. API keys inherit the linked user's context rights.

## Related

- [Resources](resources.md)
- [Configuration](../configuration/settings.md)
