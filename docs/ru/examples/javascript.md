# Примеры на JavaScript

Рецепты `fetch` для mxHeadless v1 в браузере или Node 18+. Подставьте свой URL вместо `https://example.com`.

```js
const base = 'https://example.com/api/v1'
```

## Хелпер

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

Успех: `{ data, meta, links }`. Ошибки: RFC 9457 (`application/problem+json`) с `status`, `detail` и часто `code`.

## Discovery и health

```js
const discovery = await api('')
const health = await api('/health')
const schema = await api('/schema')
```

## Публичные ресурсы

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

## Фильтры

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

Параметр `page` работает без `offset`: `page=3` при `limit=50` даёт offset `100`.

## API key

```js
const apiKey = process.env.MXHEADLESS_API_KEY // mxh_...

async function apiAuth(path, options = {}) {
  return api(path, {
    ...options,
    headers: {
      Authorization: `Bearer ${apiKey}`,
      // или: 'X-API-Key': apiKey,
      ...(options.headers || {}),
    },
  })
}

const contexts = await apiAuth('/contexts?limit=5')
```

Держите ключ на сервере. Не кладите его в браузерный бандл публичного сайта.

## Создание ресурса

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

Тот же `Idempotency-Key` и то же тело повторяют первый ответ. Другое тело с тем же ключом даёт `409` (`idempotency_conflict`).

## Обновление и удаление

```js
await apiAuth('/resources/5', {
  method: 'PATCH',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ pagetitle: 'Updated title' }),
})

// Soft-delete для resources. Навсегда: '/resources/5?force=true'
await apiAuth('/resources/5', { method: 'DELETE' })
```

## Сессия и CSRF (same-origin)

Если в браузере уже есть сессия MODX:

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

API keys CSRF не требуют. Мутации по сессии нуждаются в `X-CSRF-Token`.

## Generic-объекты

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

Отвечают только объекты, зарегистрированные в ObjectRegistry.

## Conditional GET

```js
const head = await fetch(`${base}/resources/5`, { method: 'HEAD' })
const etag = head.headers.get('ETag')

const res = await fetch(`${base}/resources/5`, {
  headers: { 'If-None-Match': etag },
})
// 304, если ничего не изменилось
```

## Заголовок контекста

```js
await api('/resources/5', {
  headers: { 'X-Context': 'web' },
})
```

## Ошибки

```js
try {
  await api('/resources?' + new URLSearchParams({ 'filter[not_allowed][eq]': '1' }))
} catch (e) {
  // e.status === 422, e.code, e.body (problem+json)
}
```

## См. также

- [cURL](curl.md)
- [Resources API](../api/resources.md)
- [Аутентификация](../api/authentication.md)
- [Фильтрация](../api/filtering.md)
- [Мутации](../api/mutations.md)
- [Idempotency](../api/idempotency.md)
