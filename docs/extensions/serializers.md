# Custom serializers

Custom serializers are **not pluggable yet**.

mxHeadless serializes registered objects through `XpdoObjectSerializer` and relation loading. Field visibility follows `ObjectDefinition` (`fields`, `hiddenFields`, `protectedFields`) plus the caller scopes.

`ExtensionApi::registerSerializer` is planned. See [overview](overview.md).

## Related

- [Fields](../api/fields.md)
- [Extension overview](overview.md)
