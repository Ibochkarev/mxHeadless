# Custom permissions

Permission checks stay on scopes and MODX ACL. There is no Extra-level `registerPermission` hook yet.

Use `{name}.read` / `{name}.create` / `{name}.update` / `{name}.delete` (or `*` on an API key) and MODX policies for manager-backed identities. Field-level protection uses `protectedFields` on `ObjectDefinition`.

When `registerPermission` lands, it will be documented in [overview](overview.md).

## Related

- [Authorization](../api/authorization.md)
- [Extension overview](overview.md)
