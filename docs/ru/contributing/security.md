# Security review

Изменения в auth, authorization, input parsing, cache или webhooks нуждаются во второй проверке до merge.

## Когда review обязателен

- Новые routes или порядок middleware в `MiddlewareStackBuilder`
- `ObjectRegistry`, `Authorizer`, проверки scopes
- API key, OAuth или CSRF
- Query compiler или whitelist фильтров
- Webhook outbox, SSRF settings, проверка подписи
- Новые `mxheadless.*` settings, которые ослабляют defaults

Исправления только в документации не требуют security reviewer, если не описывают новое поведение.

## Checklist reviewer

1. **Deny by default.** Route или object закрыт, пока явно не разрешён?
2. **No client SQL.** Новые filters используют bound parameters и whitelisted fields?
3. **No secret leakage.** Logs, errors и cache keys без tokens и bodies?
4. **Auth ≠ authz.** Valid identity всё равно проверяет scopes и MODX ACL?
5. **Context isolation.** Caller не достаёт чужой context без permission?
6. **Webhook safety.** Private URLs заблокированы в production (`mxheadless.webhook.allow_private_urls`)?

Полная threat model: [security.md](../security.md).

## Сообщение об уязвимостях

Не открывайте публичные issues для exploitable findings. Пишите maintainers privately:

- Затронутый route или setting
- Шаги воспроизведения (secrets redacted)
- Impact (read, write, bypass)

После fix ротируйте затронутые keys.

## Тесты

Security regressions добавляйте в `tests/Security/`:

- `InjectionTest.php`
- `ArbitraryClassTest.php`

Перед review: `composer test`.

## См. также

- [Development](development.md)
- [Testing](testing.md)
- [Incident response](../operations/incident-response.md)
