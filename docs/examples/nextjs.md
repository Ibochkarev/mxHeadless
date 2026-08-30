# Next.js integration

Connect a Next.js App Router app to mxHeadless.

## Env

`.env.local`:

```env
MXHEADLESS_BASE_URL=https://example.com/api/v1
MXHEADLESS_API_KEY=mxh_a1b2c3d4_secret
```

Use the API key only in Server Components, Route Handlers, or `server-only` modules. Public reads often need no key.

## Server fetch helper

`lib/mxheadless.ts`:

```ts
import 'server-only'

type Envelope<T> = {
  data: T
  meta?: Record<string, unknown>
  links?: Record<string, string>
}

const baseURL = process.env.MXHEADLESS_BASE_URL!
const apiKey = process.env.MXHEADLESS_API_KEY

export async function mxGet<T>(
  path: string,
  query?: Record<string, string | number | boolean>,
  init?: RequestInit,
): Promise<Envelope<T>> {
  const url = new URL(path.replace(/^\//, ''), baseURL.endsWith('/') ? baseURL : baseURL + '/')
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      url.searchParams.set(k, String(v))
    }
  }

  const res = await fetch(url, {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(apiKey ? { Authorization: `Bearer ${apiKey}` } : {}),
      ...(init?.headers || {}),
    },
    next: init?.next ?? { revalidate: 60 },
  })

  if (!res.ok) {
    throw new Error(`mxHeadless ${res.status}: ${await res.text()}`)
  }

  return res.json() as Promise<Envelope<T>>
}
```

## Page by URI

`app/[[...slug]]/page.tsx`:

```tsx
import { notFound } from 'next/navigation'
import { mxGet } from '@/lib/mxheadless'

type Props = { params: Promise<{ slug?: string[] }> }

export default async function CmsPage({ params }: Props) {
  const { slug } = await params
  const uri = slug?.length ? `${slug.join('/')}.html` : 'index.html'

  let page
  try {
    page = await mxGet<Record<string, unknown>>(`/pages/${encodeURIComponent(uri)}`, {
      fields: 'id,pagetitle,content,uri',
    })
  } catch {
    notFound()
  }

  const title = String(page.data.pagetitle ?? '')
  const content = String(page.data.content ?? '')

  return (
    <article>
      <h1>{title}</h1>
      {/* Render CMS HTML with your sanitizer of choice */}
      <div data-cms-html={content.length > 0 ? '1' : '0'}>{content}</div>
    </article>
  )
}
```

Match the URI suffix (`.html` or trailing `/`) to your MODX friendly URLs. For rich HTML from `content`, sanitize before rendering (for example DOMPurify) instead of injecting raw markup.

## List with pagination

```tsx
const { data, meta } = await mxGet<Record<string, unknown>[]>('/resources', {
  limit: 10,
  page: 1,
  'filter[published][eq]': 1,
  'filter[parent][eq]': 2,
  sort: '-publishedon',
  fields: 'id,pagetitle,uri,introtext',
})
```

## Route Handler proxy

`app/api/news/route.ts` keeps the key off the client:

```ts
import { NextRequest, NextResponse } from 'next/server'
import { mxGet } from '@/lib/mxheadless'

export async function GET(req: NextRequest) {
  const parent = req.nextUrl.searchParams.get('parent') ?? '2'
  const body = await mxGet('/resources', {
    'filter[published][eq]': 1,
    'filter[parent][eq]': parent,
    limit: 20,
    fields: 'id,pagetitle,uri',
  })
  return NextResponse.json(body)
}
```

## Conditional GET / ETag

```ts
const res = await fetch(`${process.env.MXHEADLESS_BASE_URL}/resources/5`, {
  headers: { Accept: 'application/json' },
})
const etag = res.headers.get('ETag')
// Pass If-None-Match on the next request; expect 304 when unchanged
```

## ISR from webhooks

Point a mxHeadless webhook at a Route Handler that verifies `X-MxHeadless-Signature` and calls `revalidatePath` / `revalidateTag`. See [ISR revalidation](../operations/isr-revalidation.md).

## Types from OpenAPI

```bash
npx openapi-typescript docs/openapi.yaml -o types/mxheadless.d.ts
```

## Related

- [JavaScript](javascript.md) · [TypeScript](typescript.md) · [cURL](curl.md)
- [Resources API](../api/resources.md) · [Filtering](../api/filtering.md)
