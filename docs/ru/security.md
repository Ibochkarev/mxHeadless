# Безопасность mxHeadless

Каноническая EN-версия: [security.md](../security.md).

## Модель угроз

**Активы:** БД MODX, API keys, секреты webhook, сессии, кэш, конфигурация.

**Границы доверия:** TLS и лимиты на периметре. Gateway матчит только префикс API. Middleware fail-closed. Авторизация до xPDO. Кэш учитывает видимость. Webhook с SSRF-политикой и подписью.

**Угрозы:** anonymous в интернете, пользователи фронта, скомпрометированные keys, вредоносные Extras, атаки на URL webhook.

## Инварианты

1. Deny by default: объект, поле, фильтр, мутация закрыты, пока не зарегистрированы.
2. Аутентификация не равна авторизации.
3. Нет доступа к произвольным классам xPDO.
4. Нет SQL от клиента: только whitelist и bindings.
5. Секреты не попадают в логи, ошибки и ключи кэша.
6. Контексты изолированы.
7. Preview привилегирован.
8. Публичное чтение и admin-мутации разведены по политикам middleware.

## Аутентификация

- **Anonymous:** только явно public маршруты и объекты.
- **Сессия MODX:** cookie контекста. Мутации с `X-CSRF-Token`.
- **API key:** `mxh_{lookupId}_{secret}`, в БД hash, scopes, ротация, секрет показывают один раз.

## Матрица (кратко)

| Действие | Anonymous | Сессия | API key |
|----------|-----------|--------|---------|
| GET опубликованного | ✓ | ✓ | ✓ (scope + ACL) |
| GET черновика | ✗ | ✓ (`view_unpublished`) | ✓ (scope `preview`) |
| POST ресурса | ✗ | ✓ (+ CSRF) | ✓ |
| Admin-объекты | ✗ | ✓ | ✓ |

`hiddenFields` не грузятся. `protectedFields` требуют отдельного права.

## Лимиты ввода

`limit` до 100, `offset` до 100000, до 50 полей в `fields`, include depth 2 и до 10 relations, body 1 MB, URI 2048 байт (настраивается).

## CORS, proxy, ошибки

CORS по умолчанию выключен. `X-Forwarded-For` доверяем только от `mxheadless_trusted_proxies`.

В production нет SQL, stack trace, путей и имён классов в ответах. `mxheadless_debug` только для разработки.

## Webhook SSRF

Блок private IP, link-local, не-HTTP(S), опасные редиректы.

## Чеклист production

- [ ] `mxheadless_debug` = false
- [ ] CORS с явными origins
- [ ] Rate limit включён
- [ ] Trusted proxies за балансировщиком
- [ ] Минимальные scopes у keys
- [ ] Webhook только HTTPS
- [ ] TLS на сайте

## Тесты

Suite в `tests/Security/`: injection, arbitrary class, context escalation, preview bypass, SSRF webhook и др.
