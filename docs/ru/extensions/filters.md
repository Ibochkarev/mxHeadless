# Кастомные фильтры

Регистрация своих операторов фильтрации **пока недоступна**.

Сейчас `filter[field][op]` принимает только операторы из `QueryParser` / `FilterOperator` (`eq`, `neq`, `gt`, `gte`, `lt`, `lte`, `like`, `in`, `not_in`, `null`, `not_null`). Неизвестный оператор даёт ошибку валидации.

`ExtensionApi::registerFilter` запланирован. См. [overview](overview.md). До релиза фильтруйте по whitelist-полям и встроенным операторам.

## См. также

- [Фильтрация](../api/filtering.md)
- [Обзор расширений](overview.md)
