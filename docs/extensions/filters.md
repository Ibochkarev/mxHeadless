# Custom filters

Custom filter operators beyond the core set are **not registered yet**.

Today every `filter[field][op]` value must match operators implemented in `QueryParser` / `FilterOperator` (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `in`, `not_in`, `null`, `not_null`). Unknown operators return validation errors.

`ExtensionApi::registerFilter` is listed as planned in [overview](overview.md). Until it ships, keep filtering on whitelist fields and built-in operators.

## Related

- [Filtering](../api/filtering.md)
- [Extension overview](overview.md)
