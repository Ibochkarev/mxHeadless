# Health

`GET /api/v1/health` показывает, отвечает ли база данных. Подходит для probe балансировщика и uptime-мониторинга.

Аутентификация не нужна.

## Запрос

```bash
curl -s https://example.com/api/v1/health
```

## Ответ

Если `SELECT 1` проходит:

```json
{
  "data": {
    "status": "ok",
    "database": true,
    "timestamp": "2026-08-27T12:00:00+00:00"
  },
  "meta": {}
}
```

Если запрос к БД падает:

```json
{
  "data": {
    "status": "degraded",
    "database": false,
    "timestamp": "2026-08-27T12:00:00+00:00"
  },
  "meta": {}
}
```

| Поле | Смысл |
|------|--------|
| `status` | `ok`, если БД доступна, иначе `degraded` |
| `database` | Результат ping БД |
| `timestamp` | Время проверки в UTC (ISO 8601) |

Health не проверяет кэш MODX, диск и webhook worker. Про эксплуатацию см. [мониторинг](../operations/monitoring.md).

## См. также

- [Discovery](discovery.md)
- [Деплой](../operations/deployment.md)
