# Releases

mxHeadless ships as a MODX transport package. Package version follows SemVer with a MODX release suffix (`pl`, `rc1`, etc.). The public HTTP surface is versioned by URL prefix (`/api/v1`).

## Version alignment

Keep these files in sync before every release:

| File | Field |
|------|-------|
| `_build/config.inc.php` | `version`, `release` |
| `core/components/mxheadless/src/Version.php` | `Version::STRING` |
| `core/components/mxheadless/docs/changelog.txt` | `## [X.Y.Z-pl] - YYYY-MM-DD` |
| `docs/openapi.yaml` | `info.version` (when API docs change) |

| Artifact | Version rule |
|----------|--------------|
| Transport package | `{version}-{release}` from `_build/config.inc.php` |
| Discovery `data.version` | Same as package `version` |
| Git tag | `{version}-{release}` (example: `1.0.42-pl`, no `v` prefix) |
| URL prefix | `/api/v1` stable for 1.x package releases |

Breaking HTTP changes require a new API prefix (e.g. `/api/v2`) and a major package bump. Additive fields and endpoints are minor or patch releases.

## Automated GitHub Release

Pushing a matching Git tag triggers [`.github/workflows/release.yml`](../../.github/workflows/release.yml):

1. Installs MODX Revolution 3.2.3-pl in CI (MySQL service).
2. Builds `mxheadless-{version}-{release}.transport.zip` via `php _build/build.php`.
3. Extracts the matching section from `changelog.txt` for release notes.
4. Creates a [GitHub Release](https://github.com/Ibochkarev/mxHeadless/releases) with the `.transport.zip` and a `.sha256` checksum file.

### Maintainer checklist

1. Bump version in `_build/config.inc.php`, `Version.php`, and add a `changelog.txt` section.
2. Commit and push to `main`.
3. Tag and push (tag must equal `{version}-{release}` from config):

```bash
git tag 1.0.42-pl
git push origin main --tags
```

CI verifies that the tag matches `_build/config.inc.php` and `Version.php` before publishing.

### Test build without publishing a release

Use **Actions → Release → Run workflow** (`workflow_dispatch`). The job builds the transport package and uploads it as a workflow artifact. It does not create a GitHub Release.

### Local build (with MODX installed)

```bash
cd _build
php build.php
```

If the Extra is not under a MODX tree, point at an existing core:

```bash
MODX_CORE_PATH=/path/to/modx/core/ php _build/build.php
```

The `.transport.zip` appears in `{MODX_CORE_PATH}/packages/`.

## Changelog expectations

Document in `core/components/mxheadless/docs/changelog.txt` and the GitHub Release body:

- New or changed routes (update `docs/openapi.yaml`)
- New system settings (update [settings](../configuration/settings.md))
- Database schema changes (resolver in `_build/resolvers/tables.php`)
- Security fixes (call out rotation steps if keys are affected)

Sections use [Keep a Changelog](https://keepachangelog.com/) headings (`Added`, `Changed`, `Fixed`, `Removed`).

## Upgrade path

Site operators:

1. Back up database and `core/components/mxheadless/`.
2. Install the new transport package (upgrade action runs table migrations).
3. Run smoke tests from [production checklist](../operations/production-checklist.md).
4. Clear MODX cache if response shapes changed.

No separate CLI migrate command exists. Resolvers run during package upgrade.

## GitHub Actions notes

### CI on every push

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml) runs PHPUnit (PHP 8.2–8.5), PHPStan, PHPCS, Composer validate/audit, PHP syntax lint, and OpenAPI structure checks.

### Node.js runner annotation

The **Build transport package** job (and some CI jobs) may show this annotation on GitHub-hosted runners:

```text
Node.js 20 is deprecated. The following actions target Node.js 20 but are being forced to run on Node.js 24: actions/checkout@v4, actions/upload-artifact@v4.
```

This is informational from GitHub Actions ([changelog](https://github.blog/changelog/2025-09-19-deprecation-of-node-20-on-github-actions-runners/)). Current workflows use Node.js 24 actions (`actions/checkout@v7`, `actions/upload-artifact@v7`, `actions/cache@v6`).

## Related

- [Development](development.md)
- [Testing](testing.md)
- [Security review](security.md)
