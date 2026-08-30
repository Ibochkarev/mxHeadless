# События webhook

Core mutations эмитят events через `WebhookEventFactory`. Payload следует [схеме webhooks](../api/webhooks.md#payload-schema-v1).

## Core events

| Pattern | Пример |
|---------|--------|
| `{name}.created` | `resources.created`, `chunks.created` |
| `{name}.updated` | `resources.updated` |
| `{name}.deleted` | `resources.deleted` |

Подписка на exact names или `*` для всех events.

## Custom payloads (будущее)

`registerWebhookEvent()` запланирован для Extras с non-mutation events (order paid, import finished). Пока используйте `registerEndpoint()` и `WebhookOutbox::enqueue()` из handler.

## Extras

Register objects в `OnMxHeadlessRegister`. Мутации на них автоматически эмитят `{name}.{action}` с тем же envelope и `meta.revalidate` tags.

## См. также

- [Overview](overview.md)
- [ISR revalidation](../operations/isr-revalidation.md)
