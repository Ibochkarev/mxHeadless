# SvelteKit integration

Connect a SvelteKit app to mxHeadless with server-side `fetch`.

## Env

`.env`:

```env
MXHEADLESS_BASE_URL=https://example.com/api/v1
MXHEADLESS_API_KEY=mxh_a1b2c3d4_secret
```

`src/lib/server/mxheadless.ts` (import only from `+page.server.ts` / `+server.ts`):

```ts
import { env } from '$env/dynamic/private'
import { env as publicEnv } from '$env/dynamic/public'

type Envelope<T> = {
  data: T
  meta?: Record<string, unknown>
  links?: Record<string, string>
}

const baseURL = publicEnv.PUBLIC_MXHEADLESS_BASE_URL ?? env.MXHEADLESS_BASE_URL
const apiKey = env.MXHEADLESS_API_KEY

export async function mxGet<T>(
  fetchFn: typeof fetch,
  path: string,
  query?: Record<string, string | number | boolean>,
): Promise<Envelope<T>> {
  const url = new URL(path.replace(/^\//, ''), baseURL.endsWith('/') ? baseURL : baseURL + '/')
  if (query) {
    for (const [k, v] of Object.entries(query)) {
      url.searchParams.set(k, String(v))
    }
  }

  const res = await fetchFn(url, {
    headers: {
      Accept: 'application/json',
      ...(apiKey ? { Authorization: `Bearer ${apiKey}` } : {}),
    },
  })

  if (!res.ok) {
    throw new Error(`mxHeadless ${res.status}`)
  }

  return res.json()
}
```

Prefer `event.fetch` so SvelteKit can track the request during SSR.

## Page by URI

`src/routes/[...slug]/+page.server.ts`:

```ts
import { error } from '@sveltejs/kit'
import type { PageServerLoad } from './$types'
import { mxGet } from '$lib/server/mxheadless'

export const load: PageServerLoad = async ({ params, fetch }) => {
  const uri = `${params.slug}.html`

  try {
    const page = await mxGet<Record<string, unknown>>(fetch, `/pages/${encodeURIComponent(uri)}`, {
      fields: 'id,pagetitle,content,uri',
    })
    return { page: page.data }
  } catch {
    error(404, 'Page not found')
  }
}
```

`+page.svelte`:

```svelte
<script lang="ts">
  let { data } = $props()
</script>

<article>
  <h1>{data.page.pagetitle}</h1>
  <!-- Sanitize CMS HTML (e.g. DOMPurify) before inserting markup -->
  <p>{data.page.content}</p>
</article>
```

Field `content` is HTML from MODX. Sanitize it before rendering. Do not use `{@html}` on unsanitized strings.

## Resource list

```ts
export const load: PageServerLoad = async ({ url, fetch }) => {
  const page = Number(url.searchParams.get('page') ?? '1')
  const list = await mxGet<Record<string, unknown>[]>(fetch, '/resources', {
    limit: 10,
    page,
    'filter[published][eq]': 1,
    sort: '-id',
    fields: 'id,pagetitle,uri',
  })
  return { items: list.data, meta: list.meta }
}
```

## API proxy endpoint

`src/routes/api/news/+server.ts`:

```ts
import { json } from '@sveltejs/kit'
import type { RequestHandler } from './$types'
import { mxGet } from '$lib/server/mxheadless'

export const GET: RequestHandler = async ({ fetch, url }) => {
  const parent = url.searchParams.get('parent') ?? '2'
  const body = await mxGet(fetch, '/resources', {
    'filter[published][eq]': 1,
    'filter[parent][eq]': parent,
    limit: 20,
    fields: 'id,pagetitle,uri',
  })
  return json(body)
}
```

## Related

- [JavaScript](javascript.md) · [cURL](curl.md) · [Nuxt](nuxt.md)
- [Resources API](../api/resources.md) · [Filtering](../api/filtering.md)
