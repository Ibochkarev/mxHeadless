# Webhooks

mxHeadless writes webhook events to an outbox when resources or registered objects are created, updated, or deleted. A CLI worker delivers pending rows to subscriber URLs.

## Create a subscription (CLI)

```bash
php core/components/mxheadless/bin/webhook-subscribe.php \
  --name="ISR" \
  --url="https://frontend.example/api/revalidate" \
  --events="resources.created,resources.updated,resources.deleted" \
  --secret="shared-hmac-secret"
```

Omit `--secret` to generate one. Use `--inactive` to insert a disabled row.

## Events (core)

| Event | When |
|-------|------|
| `resources.created` | After successful `POST /resources` |
| `resources.updated` | After successful `PUT` or `PATCH /resources/{id}` |
| `resources.deleted` | After successful `DELETE /resources/{id}` |
| `{name}.created` | Generic object create |
| `{name}.updated` | Generic object update |
| `{name}.deleted` | Generic object delete |

## Payload schema (v1)

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

| Field | Description |
|-------|-------------|
| `id` | Unique event id (hex) |
| `type` | Event name, same as subscription filter |
| `created_at` | ISO-8601 UTC timestamp |
| `data.object` | Public object name |
| `data.action` | `created`, `updated`, or `deleted` |
| `data.id` | Primary key (string) |
| `data.context` | MODX context when present |
| `data.uri` | Resource URI when present |
| `data.parent` | Parent resource id when present |
| `meta.revalidate` | Suggested cache tags for ISR (see [ISR revalidation](../operations/isr-revalidation.md)) |

Legacy subscribers that only read `object` and `id` should switch to `data.*`. The envelope is stable for v1.

## HTTP delivery

1. Mutation saves the xPDO object.
2. `WebhookOutbox::enqueue()` writes a delivery row per matching subscription.
3. `WebhookDispatcher` sends `POST` with the JSON body.

Headers:

| Header | Value |
|--------|-------|
| `Content-Type` | `application/json` |
| `User-Agent` | `MxHeadless-Webhook/1.0` |
| `X-MxHeadless-Event` | Event type (`resources.updated`, …) |
| `X-MxHeadless-Delivery-Id` | Outbox row id |
| `X-MxHeadless-Signature` | `sha256=...` when subscription secret is set |

Delivery uses a built-in curl PSR-18 client by default. If MODX already registers `ClientInterface`, that client is used instead.

## Retries

Failed deliveries stay `pending` with exponential backoff until `mxheadless.webhook.max_attempts` (default `5`). After that the row is marked `failed`.

| Attempt | Backoff |
|---------|---------|
| 1 | 30s |
| 2 | 60s |
| 3 | 120s |
| 4 | 240s |
| 5+ | up to 3600s |

Run the worker from cron or systemd. See [workers](../operations/workers.md).

## SSRF protection

The dispatcher blocks:

- Non-HTTP(S) schemes
- `localhost`, `*.local`, and `*.test` hosts (unless overridden)
- Private and link-local IPs after DNS resolution

For local development only, set `mxheadless.webhook.allow_private_urls` to `true`. Keep it `false` in production.

Point subscriptions only to hosts you control.

## Related

- [ISR revalidation](../operations/isr-revalidation.md)
- [Workers](../operations/workers.md)
- [Webhook events (extensions)](../extensions/webhook-events.md)
- [Mutations](mutations.md)
- [Security](../security.md#webhooks)
