# Authorization

Authorization runs after authentication. It answers whether this identity may perform an action on this object, field, relation, or context.

mxHeadless combines four layers:

1. **Route permission** (for example `resources.read`)
2. **API key scope** (same string names as permissions)
3. **MODX ACL** (`hasPermission`, context access, resource `view`)
4. **Field policy** (hidden vs protected fields)

All layers must pass. A valid API key with `resources.read` still fails if the linked MODX user cannot view the resource in the target context.

## Public vs privileged routes

Routes registered with `public: true` allow anonymous read when the object definition also allows it. Mutations are never anonymous.

| Route group | Typical caller | Cache namespace |
|-------------|----------------|-----------------|
| Content read | Anonymous, session, API key | Public GET cache |
| Admin mutations | Session or scoped API key | `private, no-store` |

## Field visibility

| Policy | Behavior |
|--------|----------|
| Hidden | Never selected in SQL, never serialized |
| Protected | Omitted unless caller has field permission |
| Normal | Included when listed in `fields` or default projection |

## Preview

`?preview=true` requires `view_unpublished` (session) or `preview` scope (API key). Anonymous preview always returns 403 or 404 depending on policy.

## 403 vs 404

When revealing that a resource exists would leak information, the API returns 404 instead of 403.

## Related

- [Authentication](authentication.md)
- [API keys](api-keys.md)
- [Preview](preview.md)
- [Security](../security.md)
