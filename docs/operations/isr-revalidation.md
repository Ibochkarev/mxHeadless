# ISR / revalidation

Use webhook `meta.revalidate` tags to invalidate or regenerate pages on your headless frontend after MODX content changes.

## Flow

```
MODX mutation → outbox → worker POST → your revalidate endpoint → Next.js / Nuxt cache purge
```

mxHeadless does not call your frontend directly during the API request. Delivery is async through the webhook worker.

## Tag format

Tags are strings in `meta.revalidate`:

| Tag | Invalidate when |
|-----|-----------------|
| `mxheadless:resources` | Any resource change |
| `mxheadless:resources:{id}` | One resource |
| `mxheadless:uri:{path}` | Page by URI (`about.html`) |
| `mxheadless:context:{key}` | Context-wide content |
| `mxheadless:resources:list` | Resource deleted (list views) |
| `mxheadless:resources:{parentId}` | Child changed (parent menus) |

Map tags to your router paths in the revalidate handler.

## Next.js App Router example

Webhook route `app/api/revalidate/route.ts`:

```typescript
import { revalidateTag } from 'next/cache';
import { NextRequest, NextResponse } from 'next/server';
import { createHmac, timingSafeEqual } from 'crypto';

export async function POST(request: NextRequest) {
  const secret = process.env.MXHEADLESS_WEBHOOK_SECRET ?? '';
  const rawBody = await request.text();
  const signature = request.headers.get('x-mxheadless-signature') ?? '';

  if (secret && !verifySignature(secret, rawBody, signature)) {
    return NextResponse.json({ error: 'invalid signature' }, { status: 401 });
  }

  const event = JSON.parse(rawBody) as {
    type: string;
    data: { uri?: string; id?: string };
    meta?: { revalidate?: string[] };
  };

  for (const tag of event.meta?.revalidate ?? []) {
    revalidateTag(tag);
  }

  if (event.data.uri) {
    revalidateTag(`mxheadless:uri:${event.data.uri.replace(/^\/+|\/+$/g, '')}`);
  }

  return NextResponse.json({ revalidated: true, type: event.type });
}

function verifySignature(secret: string, body: string, header: string): boolean {
  const expected = 'sha256=' + createHmac('sha256', secret).update(body).digest('hex');
  const a = Buffer.from(expected);
  const b = Buffer.from(header);
  return a.length === b.length && timingSafeEqual(a, b);
}
```

Set the same secret on the MODX webhook subscription.

## Nuxt (Nitro) example

Server route `server/api/revalidate.post.ts`:

```typescript
export default defineEventHandler(async (event) => {
  const body = await readRawBody(event);
  const payload = JSON.parse(body ?? '{}');
  const storage = useStorage('cache');

  for (const tag of payload.meta?.revalidate ?? []) {
    await storage.removeItem(`nitro:handlers:${tag}`);
  }

  return { revalidated: true, type: payload.type };
});
```

Adjust storage keys to match your caching layer.

## Subscription setup

1. Insert a row in `mxheadless_webhook_subscriptions` (or future manager UI).
2. URL: `https://frontend.example/api/revalidate`
3. Events: `resources.*` or `*` for all core events.
4. Secret: shared with the frontend env var.
5. Run [webhook worker](workers.md) every minute in production.

## Related

- [Webhooks](../api/webhooks.md)
- [Workers](workers.md)
- [Production checklist](production-checklist.md)
