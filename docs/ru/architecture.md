# Архитектура mxHeadless

mxHeadless даёт JSON API для MODX Revolution 3: ресурсы, страницы по URI и зарегистрированные xPDO-объекты. Пакет встраивается в контейнер MODX, ACL и кэш. Extras регистрируют свои модели без правок ядра.

Полная английская версия: [architecture.md](../architecture.md).

## Цели

- Headless-фронт (Nuxt, Next.js, мобильные клиенты) читает и меняет контент через HTTP
- Безопасность по умолчанию: ничего не открыто, пока не описано в `ObjectRegistry`
- Один gateway на префикс `/api`, fallback `assets/components/mxheadless/api.php`

## Не делаем

- Обёртку над legacy `modRest`
- Произвольный SQL или доступ к любому классу xPDO
- JSON:API в v1 (опционально позже через serializer)

## Жизненный цикл запроса

1. Плагин `OnHandleRequest` ловит URI с префиксом API
2. Сборка PSR-7 request из superglobals
3. Middleware pipeline (ошибки, proxy, лимиты, CORS, rate limit, auth, CSRF, authz, кэш)
4. Router → сервис → `QueryParser` / `XpdoQueryCompiler` → xPDO
5. Serializer → JSON envelope → emitter

## ObjectRegistry

Публичное имя (`resources`, `products`) мапится на `ObjectDefinition`: класс xPDO, поля, фильтры, sort, relations, флаги мутаций. Автоэкспозиции таблиц нет.

## Query engine

HTTP-параметры становятся `ObjectQuery`. Компилятор проверяет whitelist и строит `xPDOQuery` только с bindings. Criteria-строки от клиента не принимаются.

## Аутентификация и авторизация

`Identity`: anonymous, сессия MODX, API key. Ключ: lookup ID + hash секрета, scopes.

Авторизация: scope + право MODX + ACL контекста + политика полей. Аутентификация не заменяет права.

## Кэш и webhooks

Публичные GET могут иметь ETag. Preview и персональные ответы: `private, no-store`.

Мутации пишут события в outbox. Доставка с HMAC и защитой от SSRF, повторы через CLI worker.

## Версионирование

Стабильный контракт: `/api/v1`. Breaking changes только в `/api/v2`.

## ADR

- [0001 Gateway](../decisions/0001-http-gateway.md)
- [0002 PSR-15](../decisions/0002-psr15.md)
- [0003 JSON envelope](../decisions/0003-json-format.md)
- [0004 JSON:API](../decisions/0004-json-api.md)
- [0005 Cache](../decisions/0005-cache.md)
- [0006 Webhook outbox](../decisions/0006-webhook-outbox.md)
