# TypeScript

Generate types from the live OpenAPI document, then call mxHeadless with typed envelopes.

## Generate types

From the package root (or copy `docs/openapi.yaml` into your app):

```bash
npx openapi-typescript docs/openapi.yaml -o types/mxheadless.d.ts
```

Or from a running site:

```bash
npx openapi-typescript https://example.com/api/v1/meta/openapi -o types/mxheadless.d.ts
```

`GET /api/v1/meta/openapi` returns the same schema under `{ "data": { ...OpenAPI... } }`. Point the generator at the raw OpenAPI document if your tool expects the root `openapi` field.

## Envelope type

```ts
export type MxEnvelope<T> = {
  data: T
  meta?: {
    total?: number
    count?: number
    limit?: number
    offset?: number
    has_more?: boolean
    [key: string]: unknown
  }
  links?: {
    self?: string
    next?: string
    prev?: string
    [key: string]: string | undefined
  }
}

export type MxProblem = {
  type?: string
  title?: string
  status: number
  detail?: string
  instance?: string
  code?: string
}
```

## Typed fetch

```ts
async function mxGet<T>(
  path: string,
  query?: Record<string, string | number | boolean>,
  init?: RequestInit,
): Promise<MxEnvelope<T>> {
  const base = process.env.MXHEADLESS_BASE_URL!
  const url = new URL(path.replace(/^\//, ''), base.endsWith('/') ? base : base + '/')
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      url.searchParams.set(k, String(v))
    }
  }

  const res = await fetch(url, {
    ...init,
    headers: {
      Accept: 'application/json',
      ...(process.env.MXHEADLESS_API_KEY
        ? { Authorization: `Bearer ${process.env.MXHEADLESS_API_KEY}` }
        : {}),
      ...(init?.headers || {}),
    },
  })

  const body = await res.json()
  if (!res.ok) {
    throw body as MxProblem
  }

  return body as MxEnvelope<T>
}

type ResourceCard = {
  id: number
  pagetitle: string
  uri: string
}

const list = await mxGet<ResourceCard[]>('/resources', {
  limit: 10,
  'filter[published][eq]': 1,
  fields: 'id,pagetitle,uri',
  sort: '-id',
})
```

## Related

- [JavaScript](javascript.md) · [OpenAPI](../openapi.yaml) · [Meta catalog](../api/meta.md)
- [Nuxt](nuxt.md) · [Next.js](nextjs.md)
