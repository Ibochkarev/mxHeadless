# Кастомные permissions

Проверки идут через scopes и MODX ACL. Хука `registerPermission` для Extra ещё нет.

Используйте `{name}.read` / `{name}.create` / `{name}.update` / `{name}.delete` (или `*` на API key) и политики MODX для session-идентичностей. Поля с `protectedFields` требуют отдельного права на запись.

Когда появится `registerPermission`, это будет в [overview](overview.md).

## См. также

- [Авторизация](../api/authorization.md)
- [Обзор расширений](overview.md)
