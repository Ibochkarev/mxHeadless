# Релизы

mxHeadless поставляется как MODX transport package. Версия пакета следует SemVer. Публичный HTTP surface версионируется префиксом URL (`/api/v1`).

## Согласование версий

| Артефакт | Правило |
|----------|---------|
| Transport package | SemVer в `_build/build.config.php` |
| Discovery `data.version` | Совпадает с пакетом |
| URL prefix | `/api/v1` стабилен в рамках 1.x |

Breaking HTTP changes требуют новый API prefix (например `/api/v2`) и major bump пакета. Additive fields и endpoints — minor или patch.

## Сборка

```bash
cd _build
php build.php
```

Загрузите `.transport.zip` через **Packages → Install Package**. Сначала staging, потом production.

## Changelog

В release notes или MR укажите:

- Новые или изменённые routes (обновите `docs/openapi.yaml`)
- Новые system settings ([settings](../configuration/settings.md))
- Изменения схемы БД (`_build/resolvers/tables.php`)
- Security fixes (шаги ротации keys, если затронуты)

## Upgrade path

Для операторов сайта:

1. Backup базы и `core/components/mxheadless/`.
2. Установите новый transport (upgrade запускает migrations resolver).
3. Smoke tests из [production checklist](../operations/production-checklist.md).
4. Очистите MODX cache при изменении shape ответов.

Отдельной CLI migrate команды нет. Resolvers работают при upgrade пакета.

## Tags и Git

Тегируйте релизы версией пакета (`v1.0.11`). Commit messages — conventional (`feat:`, `fix:`, `docs:`).

## См. также

- [Development](development.md)
- [Testing](testing.md)
- [Security review](security.md)
