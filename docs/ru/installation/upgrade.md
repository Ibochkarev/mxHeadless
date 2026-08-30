# Обновление

## Обновление пакета

1. Сделайте бэкап файлов и БД
2. Удалите старые `core/components/mxheadless` и `assets/components/mxheadless`, если Package Manager не заменил их чисто
3. Установите новый transport через Package Manager
4. Выполните шаги из release notes (схема, settings)
5. Очистите кэш MODX
6. Проверьте `GET /api/v1/health` и `GET /api/v1` (поле `version`)

Resolvers пакета создают или проверяют таблицы `{prefix}mxheadless_*` при install/upgrade.

## Breaking changes

Ломающие изменения HTTP не выходят внутри `/api/v1`. Новый major API идёт как `/api/v2` и major bump пакета.

## См. также

- [Установка](install.md)
- [Удаление](uninstall.md)
- [Чеклист production](../operations/production-checklist.md)
