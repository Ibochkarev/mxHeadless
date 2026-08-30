# JavaScript examples

`fetch` recipes for mxHeadless v1 in the browser or Node 18+. Replace `https://example.com` with your site URL.

```js
const base = 'https://example.com/api/v1'
```

## Helper

```js
async function api(path, options = {}) {
  const res = await fetch(`${base}${path}`, {
    ...options,
    headers: {
      Accept: 'application/json',
      ...(options.headers || {}),
    },
  })

  const body = await res.json().catch(() => null)
  if (!res.ok) {
    const err = new Error(body?.detail || res.statusText)
    err.status = res.status
    err.code = body?.code
    err.body = body
    throw err
  }

  return body
}
```

Success responses look like `{ data, meta, links }`. Errors use RFC 9457 (`application/problem+json`) with `status`, `detail`, and often `code`.

## Discovery and health

```js
const discovery = await api('')
const health = await api('/health')
const schema = await api('/schema')
```

## Public resources

```js
const list = await api(
  '/resources?' +
    new URLSearchParams({
      limit: '5',
      'filter[published][eq]': '1',
      sort: '-id',
      fields: 'id,pagetitle,uri',
    }),
)

const one = await api('/resources/5')

const page = await api(
  '/pages/' +
    encodeURIComponent('about.html') +
    '?' +
    new URLSearchParams({ fields: 'id,pagetitle,content' }),
)

const search = await api(
  '/resources?' + new URLSearchParams({ q: 'contact', limit: '10' }),
)
```

## Filtering

```js
const children = await api(
  '/resources?' +
    new URLSearchParams({
      'filter[parent][eq]': '2',
      'filter[published][eq]': '1',
    }),
)

const byIds = await api(
  '/resources?' + new URLSearchParams({ 'filter[id][in]': '1,5,10' }),
)

const byTitle = await api(
  '/resources?' +
    new URLSearchParams({ 'filter[pagetitle][like]': '%News%' }),
)
```

`page` works when you omit `offset`: `page=3` with `limit=50` is offset `100`.

## API key

```js
const apiKey = process.env.MXHEADLESS_API_KEY // mxh_...

async function apiAuth(path, options = {}) {
  return api(path, {
    ...options,
    headers: {
      Authorization: `Bearer ${apiKey}`,
      // or: 'X-API-Key': apiKey,
      ...(options.headers || {}),
    },
  })
}

const contexts = await apiAuth('/contexts?limit=5')
```

Keep the key on the server. Do not ship it in browser bundles for public sites.

## Create resource

```js
const created = await apiAuth('/resources', {
  method: 'POST',
  headers: {
    'Content-Type': 'application/json',
    'Idempotency-Key': crypto.randomUUID(),
  },
  body: JSON.stringify({
    pagetitle: 'API created page',
    parent: 2,
    template: 1,
    published: 0,
  }),
})
```

Same `Idempotency-Key` + same body replays the first response. A different body with that key returns `409` (`idempotency_conflict`).

## Update and delete

```js
await apiAuth('/resources/5', {
  method: 'PATCH',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ pagetitle: 'Updated title' }),
})

// Soft-delete (resources). Permanent: '/resources/5?force=true'
await apiAuth('/resources/5', { method: 'DELETE' })
```

## Session + CSRF (same-origin)

When the browser already has a MODX session:

```js
await api('/resources/5', {
  method: 'PATCH',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json',
    'X-CSRF-Token': csrfTokenFromSession,
  },
  body: JSON.stringify({ pagetitle: 'Updated title' }),
})
```

API keys skip CSRF. Session mutations need `X-CSRF-Token`.

## Generic objects

```js
const products = await api(
  '/objects/products?' +
    new URLSearchParams({
      'filter[parent][eq]': '15',
      limit: '12',
      sort: 'price',
    }),
)
```

Only objects registered in the ObjectRegistry respond here.

## Conditional GET

```js
const head = await fetch(`${base}/resources/5`, { method: 'HEAD' })
const etag = head.headers.get('ETag')

const res = await fetch(`${base}/resources/5`, {
  headers: { 'If-None-Match': etag },
})
// 304 when unchanged
```

## Context header

```js
await api('/resources/5', {
  headers: { 'X-Context': 'web' },
})
```

## Errors

```js
try {
  await api('/resources?' + new URLSearchParams({ 'filter[not_allowed][eq]': '1' }))
} catch (e) {
  // e.status === 422, e.code, e.body (problem+json)
}
```

## Related

- [cURL](curl.md)
- [Resources API](../api/resources.md)
- [Authentication](../api/authentication.md)
- [Filtering](../api/filtering.md)
- [Mutations](../api/mutations.md)
- [Idempotency](../api/idempotency.md)
