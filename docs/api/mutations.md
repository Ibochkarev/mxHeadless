# Mutations

`POST`, `PUT`, `PATCH`, and `DELETE` change data through the same authorization stack as reads. Authentication alone is not enough: you need scope, MODX permission, and a writable field list on the object definition.

## Endpoints

| Action | Resources | Generic objects |
|--------|-----------|-----------------|
| Create | `POST /api/v1/resources` | `POST /api/v1/objects/{name}` |
| Replace | `PUT /api/v1/resources/{id}` | `PUT /api/v1/objects/{name}/{id}` |
| Partial update | `PATCH /api/v1/resources/{id}` | `PATCH /api/v1/objects/{name}/{id}` |
| Delete | `DELETE /api/v1/resources/{id}` | `DELETE /api/v1/objects/{name}/{id}` |

## Request body

JSON object with field names as keys. Only fields listed on `ObjectDefinition` are accepted. Hidden fields (`properties` on resources) and immutable system fields (`id`, `createdon`, `editedon`, `deletedon`, …) return `422` when sent on write. Arrays and objects are rejected (`422`). Boolean fields accept `true`/`false`/`0`/`1`; integer fields (`parent`, `template`, …) must be integers. `class_key` must name an existing `modResource` subclass. `parent` must be `0` or an existing resource id (not self, and not a descendant that would create a cycle). `content_type` must reference an existing content type. `template` must be `0` or an existing template id. `alias` must be unique among non-deleted siblings in the same context (max 255 characters).

```bash
curl -s -X POST https://example.com/api/v1/resources \
  -H 'Authorization: Bearer mxh_...' \
  -H 'Content-Type: application/json' \
  -d '{"pagetitle":"Draft","parent":2,"published":0}'
```

## Session auth and CSRF

Browser sessions must send `X-CSRF-Token` on mutations. API keys skip CSRF.

## Delete behavior

Objects that expose a `deleted` field (including MODX resources) are **soft-deleted** by default: `deleted=1`, `deletedon`/`deletedby` set, children of a resource tree are marked the same way. Soft-deleted rows stay out of normal list/get responses (`deleted = 0` visibility filter).

Use `?include_deleted=true` on GET list/get to include trash (requires `preview`, `resources.update`, or `resources.delete`). That flag also skips the published-only filter so unpublished drafts in the recycle bin are visible. Restore with `PATCH` and `{"deleted":0}` (clears `deletedon`/`deletedby`). `PATCH` with `{"deleted":true}` runs the same soft-delete path as `DELETE` (audit fields + resource tree cascade) and needs delete permission. Pass `?force=true` to permanently remove the row (`xPDOObject::remove()`).

Response:

```json
{ "data": { "id": "12", "deleted": true, "permanent": false }, "meta": [] }
```

## Transactions and webhooks

Successful saves enqueue webhook events (`resource.created`, `resource.updated`, `resource.deleted`, or `{name}.{action}` for generic objects). Delivery runs through the outbox; see [webhooks](webhooks.md).

## Errors

| Status | Typical cause |
|--------|----------------|
| 401 | No credentials |
| 403 | Missing scope or MODX permission |
| 404 | Object not found or not visible |
| 422 | Unknown field, validation failure, malformed JSON |

## Related

- [Authentication](authentication.md)
- [Authorization](authorization.md)
- [Resources](resources.md)
- [Objects](objects.md)
