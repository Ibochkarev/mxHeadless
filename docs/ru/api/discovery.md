# Discovery

`GET /api/v1` отдаёт базовые метаданные API. Удобно проверить, что gateway отвечает, и узнать версию перед подключением фронта или CI.

Аутентификация не нужна.

## Запрос

```bash
curl -s https://example.com/api/v1
```

Префикс пути задаётся настройкой `mxheadless.api.prefix` (по умолчанию `/api`). Сегмент `v1` зашит в роутер.

## Ответ

```json
{
  "data": {
    "name": "mxHeadless",
    "version": "1.0.11",
    "api": "/api/v1",
    "cors": {
      "enabled": true,
      "allowed_origins": ["http://localhost:3000"]
    },
    "links": {
      "health": "/api/v1/health",
      "schema": "/api/v1/schema",
      "endpoints": "/api/v1/meta/endpoints",
      "openapi": "/api/v1/meta/openapi",
      "pages": "/api/v1/pages/{uri}"
    }
  },
  "meta": {}
}
```

| Поле | Смысл |
|------|--------|
| `name` | Идентификатор пакета |
| `version` | Версия релиза mxHeadless |
| `api` | Базовый путь для маршрутов v1 |
| `cors` | Включение CORS и список origins (bootstrap Nuxt/Next) |
| `links` | Связанные публичные URL |

Discovery не перечисляет все endpoint. Живой список — [`/meta/endpoints`](meta.md). Формы маршрутов — [OpenAPI](../openapi.yaml) или [`/meta/openapi`](meta.md). Объекты и поля — [`/schema`](schema.md).

## Когда вызывать

- Простая проверка «API жив» в мониторинге
- Скрипты сборки клиентов
- Первая ручная проверка после установки (до `GET /health` и `GET /schema`)

## См. также

- [Health](health.md)
- [Schema](schema.md)
- [Meta catalog](meta.md)
- [OpenAPI](../openapi.yaml)
- [CORS](../configuration/cors.md)
