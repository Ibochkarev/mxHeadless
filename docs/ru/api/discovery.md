# Discovery

`GET /api/v1` отдаёт базовые метаданные API. Удобно проверить, что gateway отвечает, и узнать версию перед подключением фронта или CI.

Аутентификация не нужна.

## Запрос

```bash
curl -s https://example.com/api/v1
```

Префикс пути задаётся настройкой `mxheadless_api_prefix` (по умолчанию `/api`). Сегмент `v1` зашит в роутер.

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
      "docs": "/api/v1/docs",
      "endpoints": "/api/v1/meta/endpoints",
      "openapi": "/api/v1/meta/openapi",
      "openapi_json": "/api/v1/meta/openapi.json",
      "auth_token": "/api/v1/auth/token",
      "resources": "/api/v1/resources",
      "pages": "/api/v1/pages/{uri}",
      "contexts": "/api/v1/contexts",
      "chunks": "/api/v1/chunks",
      "templates": "/api/v1/templates",
      "snippets": "/api/v1/snippets",
      "tvs": "/api/v1/tvs",
      "categories": "/api/v1/categories",
      "content_types": "/api/v1/content_types",
      "objects": "/api/v1/objects/{name}"
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

Discovery не перечисляет все endpoint. Живой список — [`/meta/endpoints`](meta.md). Формы маршрутов — [OpenAPI](../openapi.yaml), [`/meta/openapi`](meta.md) или интерактивный [`/docs`](meta.md) (Swagger UI). Объекты и поля — [`/schema`](schema.md).

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
