# Webhooks

mxHeadless пишет события в outbox при create/update/delete ресурсов и зарегистрированных объектов. CLI worker доставляет pending-строки на URL подписчиков.

## Создать подписку (CLI)

```bash
php core/components/mxheadless/bin/webhook-subscribe.php \
  --name="ISR" \
  --url="https://frontend.example/api/revalidate" \
  --events="resources.created,resources.updated,resources.deleted" \
  --secret="shared-hmac-secret"
```

Без `--secret` секрет сгенерируется. `--inactive` — создать выключенную подписку.

## События (core)

| Событие | Когда |
|---------|-------|
| `resources.created` | После успешного `POST /resources` |
| `resources.updated` | После `PUT` / `PATCH /resources/{id}` |
| `resources.deleted` | После `DELETE /resources/{id}` |
| `{name}.created` | Создание generic-объекта |
| `{name}.updated` | Обновление generic-объекта |
| `{name}.deleted` | Удаление generic-объекта |

## Схема payload (v1)

```json
{
  "id": "a1b2c3d4e5f6478990abcdef12345678",
  "type": "resources.updated",
  "created_at": "2026-08-27T14:00:00+00:00",
  "data": {
    "object": "resources",
    "action": "updated",
    "id": "42",
    "context": "web",
    "uri": "about.html",
    "parent": 1
  },
  "meta": {
    "revalidate": [
      "mxheadless:resources",
      "mxheadless:context:web",
      "mxheadless:resources:42",
      "mxheadless:uri:about.html",
      "mxheadless:resources:1"
    ]
  }
}
```

| Поле | Описание |
|------|----------|
| `id` | Уникальный id события (hex) |
| `type` | Имя события для фильтра подписки |
| `created_at` | ISO-8601 UTC |
| `data.object` | Публичное имя объекта |
| `data.action` | `created`, `updated`, `deleted` |
| `data.id` | Первичный ключ (строка) |
| `data.context` | Контекст MODX, если есть |
| `data.uri` | URI ресурса, если есть |
| `data.parent` | id родителя, если есть |
| `meta.revalidate` | Теги для ISR (см. [ISR revalidation](../operations/isr-revalidation.md)) |

Старые подписчики с полями `object` и `id` на верхнем уровне переходят на `data.*`.

## HTTP-доставка

1. Мутация сохраняет xPDO-объект.
2. `WebhookOutbox::enqueue()` создаёт delivery на каждую подписку.
3. `WebhookDispatcher` шлёт `POST` с JSON.

Заголовки:

| Заголовок | Значение |
|-----------|----------|
| `Content-Type` | `application/json` |
| `User-Agent` | `MxHeadless-Webhook/1.0` |
| `X-MxHeadless-Event` | Тип события |
| `X-MxHeadless-Delivery-Id` | id строки outbox |
| `X-MxHeadless-Signature` | `sha256=...` при заданном secret |

Доставка через встроенный curl PSR-18 client. Если в MODX уже зарегистрирован `ClientInterface`, используется он.

## Повторы

При ошибке строка остаётся `pending` с exponential backoff до `mxheadless.webhook.max_attempts` (по умолчанию `5`). Затем статус `failed`.

| Попытка | Пауза |
|---------|-------|
| 1 | 30 с |
| 2 | 60 с |
| 3 | 120 с |
| 4 | 240 с |
| 5+ | до 3600 с |

Worker запускайте из cron или systemd. См. [workers](../operations/workers.md).

## SSRF

Блокируются не-HTTP(S) схемы, `localhost` / `*.local` / `*.test`, private/link-local IP после DNS.
Для локальной разработки: `mxheadless.webhook.allow_private_urls = true` (в production держите `false`).

## См. также

- [ISR revalidation](../operations/isr-revalidation.md)
- [Workers](../operations/workers.md)
- [Webhook events (extensions)](../extensions/webhook-events.md)
- [Мутации](mutations.md)
- [Безопасность](../security.md)
