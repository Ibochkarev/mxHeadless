# Releases

mxHeadless ships as a MODX transport package. Package version follows SemVer. The public HTTP surface is versioned by URL prefix (`/api/v1`).

## Version alignment

| Artifact | Version rule |
|----------|--------------|
| Transport package | SemVer in `_build/build.config.php` |
| Discovery `data.version` | Same as package |
| URL prefix | `/api/v1` stable for 1.x package releases |

Breaking HTTP changes require a new API prefix (e.g. `/api/v2`) and a major package bump. Additive fields and endpoints are minor or patch releases.

## Build steps

```bash
cd _build
php build.php
```

Upload the generated `.transport.zip` via **Packages → Install Package**. Test on staging before production.

## Changelog expectations

Document in the release notes or MR:

- New or changed routes (update `docs/openapi.yaml`)
- New system settings (update [settings](../configuration/settings.md))
- Database schema changes (resolver in `_build/resolvers/tables.php`)
- Security fixes (call out rotation steps if keys are affected)

## Upgrade path

Site operators:

1. Back up database and `core/components/mxheadless/`.
2. Install the new transport package (upgrade action runs table migrations).
3. Run smoke tests from [production checklist](../operations/production-checklist.md).
4. Clear MODX cache if response shapes changed.

No separate CLI migrate command exists. Resolvers run during package upgrade.

## Tags and Git

Tag releases in Git with the package version (`v1.0.11`). Keep commit messages conventional (`feat:`, `fix:`, `docs:`).

## Related

- [Development](development.md)
- [Testing](testing.md)
- [Security review](security.md)
