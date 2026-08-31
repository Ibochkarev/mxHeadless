# Релизы

mxHeadless поставляется как MODX transport package. Версия пакета следует SemVer с MODX-суффиксом (`pl`, `rc1` и т.д.). Публичный HTTP surface версионируется префиксом URL (`/api/v1`).

## Согласование версий

Перед каждым релизом обновите:

| Файл | Поле |
|------|------|
| `_build/config.inc.php` | `version`, `release` |
| `core/components/mxheadless/src/Version.php` | `Version::STRING` |
| `core/components/mxheadless/docs/changelog.txt` | `## [X.Y.Z-pl] - YYYY-MM-DD` |
| `docs/openapi.yaml` | `info.version` (если менялась документация API) |

| Артефакт | Правило |
|----------|---------|
| Transport package | `{version}-{release}` из `_build/config.inc.php` |
| Discovery `data.version` | Совпадает с `version` пакета |
| Git-тег | `{version}-{release}` (пример: `1.0.42-pl`, без префикса `v`) |
| URL prefix | `/api/v1` стабилен в рамках 1.x |

Breaking HTTP changes требуют новый API prefix (например `/api/v2`) и major bump пакета. Additive fields и endpoints — minor или patch.

## Автоматический GitHub Release

Push тега с нужным именем запускает [`.github/workflows/release.yml`](../../.github/workflows/release.yml):

1. В CI ставится MODX Revolution 3.2.3-pl (сервис MySQL).
2. Собирается `mxheadless-{version}-{release}.transport.zip` через `php _build/build.php`.
3. Из `changelog.txt` берётся секция этой версии для описания release.
4. Создаётся [GitHub Release](https://github.com/Ibochkarev/mxHeadless/releases) с `.transport.zip` и файлом `.sha256`.

### Чеклист maintainer

1. Обновите версию в `_build/config.inc.php`, `Version.php` и добавьте секцию в `changelog.txt`.
2. Закоммитьте и запушьте в `main`.
3. Создайте и запушьте тег (имя должно совпадать с `{version}-{release}` из config):

```bash
git tag 1.0.42-pl
git push origin main --tags
```

CI проверяет совпадение тега с `_build/config.inc.php` и `Version.php` перед публикацией.

### Сборка без публикации release

**Actions → Release → Run workflow** (`workflow_dispatch`). Job собирает transport-пакет и кладёт его в Artifacts. GitHub Release не создаётся.

### Локальная сборка (при установленном MODX)

```bash
cd _build
php build.php
```

Если Extra лежит вне дерева MODX, укажите путь к core:

```bash
MODX_CORE_PATH=/path/to/modx/core/ php _build/build.php
```

Файл `.transport.zip` появится в `{MODX_CORE_PATH}/packages/`.

## Changelog

В `core/components/mxheadless/docs/changelog.txt` и в теле GitHub Release укажите:

- Новые или изменённые routes (обновите `docs/openapi.yaml`)
- Новые system settings ([settings](../configuration/settings.md))
- Изменения схемы БД (`_build/resolvers/tables.php`)
- Security fixes (шаги ротации keys, если затронуты)

Заголовки секций — по [Keep a Changelog](https://keepachangelog.com/) (`Added`, `Changed`, `Fixed`, `Removed`).

## Upgrade path

Для операторов сайта:

1. Backup базы и `core/components/mxheadless/`.
2. Установите новый transport (upgrade запускает migrations resolver).
3. Smoke tests из [production checklist](../operations/production-checklist.md).
4. Очистите MODX cache при изменении shape ответов.

Отдельной CLI migrate команды нет. Resolvers работают при upgrade пакета.

## GitHub Actions

### CI при каждом push

[`.github/workflows/ci.yml`](../../.github/workflows/ci.yml): PHPUnit (PHP 8.2–8.5), PHPStan, PHPCS, Composer validate/audit, lint синтаксиса PHP, проверка структуры OpenAPI.

### Аннотация Node.js на runner

У job **Build transport package** (и части CI jobs) на GitHub-hosted runners может появиться аннотация:

```text
Node.js 20 is deprecated. The following actions target Node.js 20 but are being forced to run on Node.js 24: actions/checkout@v4, actions/upload-artifact@v4.
```

Это информационное сообщение от GitHub Actions ([changelog](https://github.blog/changelog/2025-09-19-deprecation-of-node-20-on-github-actions-runners/)). Workflow завершается успешно. Текущие workflow используют Node.js 24 (`actions/checkout@v5`, `actions/upload-artifact@v5`). На старых прогонах сообщение может остаться до обновления major-версий actions.

## См. также

- [Development](development.md)
- [Testing](testing.md)
- [Security review](security.md)
