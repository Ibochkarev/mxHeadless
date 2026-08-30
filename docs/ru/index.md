# Документация mxHeadless

mxHeadless — REST API gateway для [MODX Revolution 3](https://modx.com/). Отдаёт ресурсы, страницы и зарегистрированные xPDO-объекты в JSON для headless-фронтендов (Nuxt, Next.js, SvelteKit, мобильные приложения и свои клиенты).

## Быстрые ссылки

| Тема | Описание |
|------|----------|
| [Установка](installation/install.md) | Пакет, gateway, первый запрос |
| [Требования](installation/requirements.md) | PHP, MODX, xPDO |
| [Веб-сервер](installation/web-server.md) | Apache, Nginx, fallback |
| [Resources API](api/resources.md) | CRUD ресурсов и страниц по URI |
| [Аутентификация](api/authentication.md) | Сессии, API keys, OAuth tokens, CSRF |
| [Фильтрация](api/filtering.md) | Фильтры, сортировка, пагинация, fields, includes |
| [Расширения](extensions/overview.md) | Регистрация объектов из Extras ([MiniShop3](extensions/minishop3.md)) |
| [OpenAPI](openapi.yaml) | Машиночитаемая спецификация |
| [Архитектура](architecture.md) | Дизайн и security model |
| [Безопасность](security.md) | Threat model и checklist |
| [Contributing](contributing/development.md) | Локальная разработка, тесты, релизы |
| [Чеклист тестирования](contributing/testing.md) | Автоматические gate и ручная матрица проверок |

## Справочник API

- [Discovery](api/discovery.md) · [Health](api/health.md) · [Schema](api/schema.md) · [Meta-каталог](api/meta.md)
- [Resources](api/resources.md) · [Pages](api/pages.md) · [Objects](api/objects.md) · [Элементы](api/elements.md)
- [Сортировка](api/sorting.md) · [Пагинация](api/pagination.md) · [Поля](api/fields.md) · [Связи](api/relations.md)
- [TVs](api/tv.md) · [Media](api/media.md) · [Contexts](api/contexts.md) · [Search](api/search.md)
- [Мутации](api/mutations.md) · [Ошибки](api/errors.md) · [Превью](api/preview.md)
- [API keys](api/api-keys.md) · [OAuth tokens](api/auth.md) · [Авторизация](api/authorization.md)
- [Rate limit](api/rate-limiting.md) · [HTTP-кэш](api/http-caching.md) · [Idempotency](api/idempotency.md) · [Webhooks](api/webhooks.md)

## Конфигурация

- [Настройки](configuration/settings.md) · [CORS](configuration/cors.md) · [Прокси](configuration/trusted-proxies.md) · [Лимиты](configuration/limits.md)

## Эксплуатация

- [Деплой](operations/deployment.md) · [Чеклист production](operations/production-checklist.md) · [Кэш](operations/cache.md) · [Workers](operations/workers.md) · [ISR revalidation](operations/isr-revalidation.md) · [Логирование](operations/logging.md)
- [Мониторинг](operations/monitoring.md) · [Производительность](operations/performance.md) · [Ротация ключей](operations/key-rotation.md)
- [Инциденты](operations/incident-response.md) · [Диагностика](operations/troubleshooting.md)

## Базовый URL

```
https://your-site.example/api/v1
```

Discovery и health публичные:

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
```

## Формат ответа

Успех:

```json
{
  "data": {},
  "meta": {
    "total": 100,
    "count": 20,
    "limit": 20,
    "offset": 0,
    "has_more": true
  },
  "links": {
    "self": "/api/v1/resources?limit=20&offset=0",
    "next": "/api/v1/resources?limit=20&offset=20"
  }
}
```

Ошибки: RFC 9457 (`application/problem+json`).

## Основные идеи

**Закрыто по умолчанию.** Ничего не отдаётся, пока объект не зарегистрирован в `ObjectRegistry` с явными fields, filters и permissions.

**Без произвольных классов.** Имена вроде `resources` или `products` мапятся только на `ObjectDefinition`.

**Whitelist запросов.** `QueryParser` и `XpdoQueryCompiler` проверяют каждое field, filter и sort по definition.

## Примеры

- [cURL](examples/curl.md) · [JavaScript](examples/javascript.md) · [TypeScript](examples/typescript.md)
- [Nuxt](examples/nuxt.md) · [Next.js](examples/nextjs.md) · [SvelteKit](examples/sveltekit.md)

Полная английская версия: [docs/index.md](../index.md).

## Сравнение и roadmap

- [mxHeadless vs mxApi](comparison/mxapi.md)
- [Roadmap переноса практик mxApi](roadmap/mxapi-adoption.md)

## Лицензия

GPL-2.0-or-later. mxHeadless полностью open source без feature tiers и искусственных лимитов.

## Contributing

См. [WRITING.md](../WRITING.md) (modx-docs + humanizer).
