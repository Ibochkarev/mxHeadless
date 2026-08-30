# Ошибки

Неуспешные запросы возвращают [RFC 9457](https://www.rfc-editor.org/rfc/rfc9457) в формате `application/problem+json`. Обёртки `{data, meta}` на ошибках нет.

## Формат

```json
{
  "type": "https://mxheadless.dev/problems/unauthorized",
  "title": "Unauthorized",
  "status": 401,
  "detail": "Authentication required",
  "instance": "/api/v1/resources",
  "code": "token_required"
}
```

| Поле | Роль |
|------|------|
| `type` | URI категории проблемы |
| `title` | Краткий заголовок |
| `status` | HTTP-код |
| `detail` | Текст, безопасный для production |
| `instance` | Путь запроса |
| `code` | Опциональный стабильный код для smoke-тестов и клиентов |
| `errors` | Опционально: ошибки по полям |

## Машиночитаемые коды

| `code` | HTTP | Когда |
|--------|------|-------|
| `service_disabled` | 503 | `mxheadless.enabled` = `false` (кроме discovery и health) |
| `token_required` | 401 | Нет `Authorization` / `X-API-Key` на защищённом маршруте |
| `invalid_token` | 401 | Credential есть, но неверный, истёкший или отозванный |
| `scope_denied` | 403 | Вошли, но действие запрещено |
| `rate_limited` | 429 | Превышен rate limit |
| `idempotency_conflict` | 409 | Параллельный `POST` с тем же `Idempotency-Key` или тот же ключ с другим телом |
| `invalid_grant` | 400 | OAuth token отклонён (client, grant, endpoint выключен) |

Не у каждой ошибки есть `code`. Для общей обработки используйте `status` + `type`; `code` — когда нужен фиксированный идентификатор.

## Раскрытие в production

При `mxheadless.debug` = `false` (по умолчанию) в ответе нет SQL, stack trace, путей к файлам и имён PHP-классов. Debug включайте только локально.

## Частые HTTP-коды

| Код | Когда |
|-----|-------|
| 400 | Неподдерживаемый media type |
| 401 | Нет или неверная аутентификация |
| 403 | Вошли, но действие запрещено |
| 404 | Маршрут или ресурс не найден |
| 405 | Метод не разрешён |
| 422 | Валидация, битый JSON, неизвестное поле, фильтр или sort |
| 429 | Превышен rate limit |
| 500 | Необработанная ошибка сервера |
| 503 | API выключен через `mxheadless.enabled` |

## Rate limit

На 429 могут быть заголовки `X-RateLimit-*`. См. [rate limiting](rate-limiting.md).

## См. также

- [Аутентификация](authentication.md)
- [Безопасность](../security.md)
- [Kill switch](../configuration/settings.md)
