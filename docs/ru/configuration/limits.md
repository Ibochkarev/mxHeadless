# Лимиты

Потолки, чтобы клиент не вытянул всю таблицу и не прислал огромное тело. Меняются в System Settings.

| Настройка | По умолчанию | Назначение |
|-----------|--------------|------------|
| `mxheadless.max_body_bytes` | 1048576 | Макс. размер JSON body |
| `mxheadless.max_uri_bytes` | 2048 | Макс. длина URI |
| `mxheadless.max_limit` | 100 | Макс. `limit` на списках |
| `mxheadless.max_offset` | 100000 | Макс. `offset` (и offset из `page`) |
| `mxheadless.max_fields` | 50 | Макс. число `fields` |
| `mxheadless.max_include_relations` | 10 | Макс. путей в `include` |
| `mxheadless.max_include_depth` | 2 | Макс. вложенность `include=a.b` |

`limit` меньше 1 поднимается до 1. `page` без `offset` даёт `(page - 1) * limit`, затем обрезается `max_offset`.

## См. также

- [Пагинация](../api/pagination.md)
- [Фильтрация](../api/filtering.md)
- [Настройки](settings.md)
