# Security review

Changes that touch auth, authorization, input parsing, cache, or webhooks need a second pair of eyes before merge.

## When review is required

- New routes or middleware order in `MiddlewareStackBuilder`
- `ObjectRegistry`, `Authorizer`, or scope checks
- API key, OAuth, or CSRF handling
- Query compiler or filter whitelist logic
- Webhook outbox, SSRF settings, or signature verification
- New system settings under `mxheadless.*` that weaken defaults

Documentation-only fixes do not need a security reviewer unless they describe new behavior.

## Reviewer checklist

1. **Deny by default.** Is the route or object closed until explicitly allowed?
2. **No client SQL.** Do new filters use bound parameters and whitelisted fields?
3. **No secret leakage.** Logs, errors, and cache keys exclude tokens and bodies?
4. **Auth ≠ authz.** Does valid identity still check scopes and MODX ACL?
5. **Context isolation.** Can a caller reach another context without permission?
6. **Webhook safety.** Are private URLs blocked in production (`mxheadless.webhook.allow_private_urls`)?

Full threat model: [security.md](../security.md).

## Reporting vulnerabilities

Do not open public issues for exploitable findings. Contact maintainers privately with:

- Affected route or setting
- Reproduction steps (redacted secrets)
- Impact (read, write, bypass)

Rotate affected keys after a fix ships.

## Tests

Security regressions should add or extend cases under `tests/Security/`:

- `InjectionTest.php`
- `ArbitraryClassTest.php`

Run `composer test` before requesting review.

## Related

- [Development](development.md)
- [Testing](testing.md)
- [Incident response](../operations/incident-response.md)
