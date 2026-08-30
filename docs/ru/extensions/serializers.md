# Кастомные сериализаторы

Подключаемые сериализаторы **пока недоступны**.

mxHeadless сериализует объекты через `XpdoObjectSerializer` и загрузку связей. Видимость полей задаёт `ObjectDefinition` (`fields`, `hiddenFields`, `protectedFields`) и scopes вызывающего.

`ExtensionApi::registerSerializer` в планах. См. [overview](overview.md).

## См. также

- [Поля](../api/fields.md)
- [Обзор расширений](overview.md)
