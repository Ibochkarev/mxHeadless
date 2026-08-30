# Логирование

mxHeadless разделяет три канала: request ID на каждом ответе, MODX error log для сбоев и опциональный audit trail в БД.

## Request ID

`RequestIdMiddleware` выставляет `X-Request-ID` на каждом ответе. Клиент может передать свой ID (8–64 символа, `[a-zA-Z0-9\-_.]`). Иначе gateway генерирует новый.

Используйте ID для связи тикетов с audit и problem+json.

## MODX error log

Неожиданные исключения (не `HttpException`) пишутся в лог MODX:

```
[mxHeadless] <message>
```

При `mxheadless.debug` = `true` problem+json может содержать `debug.message`. В production debug держите выключенным.

Сбой записи audit log тоже попадает в MODX на уровне error. Ответ API при этом отдаётся.

## Чего нет в логах

По [модели безопасности](../security.md):

- Сырые секреты API key и значения `Authorization`
- Секреты webhook-подписок
- Тела запросов и ответов (в том числе в audit log)
- CSRF-токены и session cookies

Сообщения об ошибках не должны повторять пользовательский SQL или filter string. Указывайте `request_id`.

## Опциональный audit log

При `mxheadless.audit.enabled` = `true` `AuditLogMiddleware` пишет строку в `{prefix}mxheadless_api_log`:

| Пишется | Не пишется |
|---------|------------|
| Method, path, status, duration | Body, query values |
| `identity_key`, `api_key_id` | Bearer token |
| `request_id`, `context_key` | Пароли |

По умолчанию только мутации (`mxheadless.audit.log_get` = `false`). Полное описание: [audit log](audit-log.md).

Ежедневная очистка:

```bash
php core/components/mxheadless/bin/audit-prune.php
```

## Логи worker

Перенаправьте stdout/stderr webhook worker в файл или journal:

```cron
* * * * * www-data php .../webhook-worker.php --limit=50 >> /var/log/mxheadless-webhook.log 2>&1
```

Смотрите на `Processed N webhook(s)` и ненулевой exit code.

## PSR-3

Компонент зависит от `psr/log` для внутренних интерфейсов. Сейчас приложение пишет через `$modx->log()`. Не регистрируйте logger, который дампит body запросов.

## См. также

- [Audit log](audit-log.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
- [Settings](../configuration/settings.md)
