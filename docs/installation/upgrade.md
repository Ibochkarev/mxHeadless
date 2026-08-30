# Upgrade

## Package upgrade

1. Back up files and database
2. Remove old `core/components/mxheadless` and `assets/components/mxheadless` if the Package Manager does not replace them cleanly
3. Install the new transport via Package Manager
4. Apply any schema or setting notes from the release
5. Clear the MODX cache
6. Hit `GET /api/v1/health` and `GET /api/v1` (check `version`)

Table resolvers in the package create or ensure `{prefix}mxheadless_*` tables on install/upgrade.

## Breaking changes

Breaking HTTP changes do not ship inside `/api/v1`. A new major API uses `/api/v2` and a semver major package bump.

## Related

- [Install](install.md)
- [Uninstall](uninstall.md)
- [Production checklist](../operations/production-checklist.md)
