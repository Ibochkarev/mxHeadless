# Nuxt integration

This guide connects a Nuxt 3/4 app to mxHeadless as a headless CMS backend.

## Setup

Install a fetch utility (Nuxt includes `$fetch` built on ofetch):

```bash
npx nuxi@latest init modx-headless
cd modx-headless
```

## Runtime config

`nuxt.config.ts`:

```typescript
export default defineNuxtConfig({
  runtimeConfig: {
    mxheadlessApiKey: '', // server-only
    public: {
      mxheadlessBaseUrl: 'https://example.com/api/v1',
    },
  },
})
```

`.env`:

```env
NUXT_PUBLIC_MXHEADLESS_BASE_URL=https://example.com/api/v1
NUXT_MXHEADLESS_API_KEY=mxh_a1b2c3d4_secret
```

## Composable

`composables/useModxRest.ts`:

```typescript
type ModxRestEnvelope<T> = {
  data: T
  meta?: Record<string, unknown>
  links?: Record<string, string>
}

export function useModxRest() {
  const config = useRuntimeConfig()
  const baseURL = config.public.mxheadlessBaseUrl as string

  async function get<T>(path: string, query?: Record<string, string | number | boolean>) {
    return $fetch<ModxRestEnvelope<T>>(path, {
      baseURL,
      query,
      headers: import.meta.server && config.mxheadlessApiKey
        ? { Authorization: `Bearer ${config.mxheadlessApiKey}` }
        : undefined,
    })
  }

  return { get, baseURL }
}
```

Use the API key only on the server (SSR, server routes). Public reads often need no key.

## Dynamic page by URI

`pages/[...slug].vue`:

```vue
<script setup lang="ts">
const route = useRoute()
const uri = Array.isArray(route.params.slug)
  ? route.params.slug.join('/') + '.html'
  : `${route.params.slug}.html`

const { get } = useModxRest()

const { data, error } = await useAsyncData(
  `page-${uri}`,
  () => get<Record<string, unknown>>(`/pages/${uri}`, {
    fields: 'id,pagetitle,content,uri',
  }),
)

if (error.value) {
  throw createError({ statusCode: 404, statusMessage: 'Page not found' })
}

useSeoMeta({
  title: () => String(data.value?.data?.pagetitle ?? ''),
})
</script>

<template>
  <article v-if="data?.data">
    <h1>{{ data.data.pagetitle }}</h1>
    <!-- Sanitize CMS HTML (e.g. DOMPurify) before rendering -->
    <div>{{ data.data.content }}</div>
  </article>
</template>
```

Adjust URI suffix (`.html` or `/`) to match your MODX friendly URL config. For rich HTML in `content`, sanitize then render trusted markup. Do not pipe raw CMS HTML into `v-html` without a sanitizer.

## Resource list with pagination

```vue
<script setup lang="ts">
const page = ref(1)
const limit = 10

const { get } = useModxRest()

const { data, refresh } = await useAsyncData(
  'news-list',
  () => get<Record<string, unknown>[]>('/resources', {
    limit,
    offset: (page.value - 1) * limit,
    'filter[published][eq]': 1,
    'filter[parent][eq]': 2,
    sort: '-publishedon',
    fields: 'id,pagetitle,uri,introtext',
  }),
  { watch: [page] },
)

const items = computed(() => data.value?.data ?? [])
const total = computed(() => Number(data.value?.meta?.total ?? 0))
</script>

<template>
  <ul>
    <li v-for="item in items" :key="item.id">
      <NuxtLink :to="`/${String(item.uri).replace(/\\.html$/, '')}`">
        {{ item.pagetitle }}
      </NuxtLink>
    </li>
  </ul>
  <button :disabled="page <= 1" @click="page--">Previous</button>
  <button :disabled="page * limit >= total" @click="page++">Next</button>
</template>
```

## Server route proxy (hide API key)

`server/api/news.get.ts`:

```typescript
export default defineEventHandler(async (event) => {
  const config = useRuntimeConfig()
  const query = getQuery(event)

  return $fetch('/resources', {
    baseURL: config.public.mxheadlessBaseUrl,
    query: {
      ...query,
      'filter[published][eq]': 1,
    },
    headers: {
      Authorization: `Bearer ${config.mxheadlessApiKey}`,
    },
  })
})
```

Client:

```typescript
const { data } = await useFetch('/api/news')
```

## Caching

For public pages, forward `ETag` from mxHeadless:

```typescript
const response = await $fetch.raw(`${baseURL}/resources/5`)
const etag = response.headers.get('etag')
// Store in useState or pass to Nitro cached handler
```

Or rely on Nuxt `useAsyncData` with a stable key and ISR / `routeRules` cache.

## Type generation

Generate TypeScript types from [OpenAPI](../openapi.yaml):

```bash
npx openapi-typescript docs/openapi.yaml -o types/mxheadless.d.ts
```

## MiniShop3 storefront

```typescript
const { data: products } = await useAsyncData('products', () =>
  get('/objects/products', {
    'filter[parent][eq]': categoryId,
    limit: 24,
    sort: 'price',
    fields: 'id,pagetitle,price,uri',
  }),
)
```

## Related

- [Resources API](../api/resources.md)
- [Filtering](../api/filtering.md)
- [cURL examples](curl.md)
