# Webhook events

Core mutations emit events through `WebhookEventFactory`. The payload follows the [webhooks schema](../api/webhooks.md#payload-schema-v1).

## Core events

| Pattern | Example |
|---------|---------|
| `{name}.created` | `resources.created`, `chunks.created` |
| `{name}.updated` | `resources.updated` |
| `{name}.deleted` | `resources.deleted` |

Subscribe with exact names or `*` for all events.

## Custom payloads (future)

`registerWebhookEvent()` is planned for Extras that need non-mutation events (order paid, import finished). Until then, use `registerEndpoint()` and call `WebhookOutbox::enqueue()` from your handler.

## Extras

Register objects in `OnMxHeadlessRegister`. Mutations on those objects automatically emit `{name}.{action}` with the same envelope and `meta.revalidate` tags.

## Related

- [Overview](overview.md)
- [ISR revalidation](../operations/isr-revalidation.md)
