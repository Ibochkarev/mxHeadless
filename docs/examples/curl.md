# cURL examples

Practical recipes for mxHeadless v1. Replace `https://example.com` with your site URL.

## Discovery and health

```bash
# API metadata
curl -s https://example.com/api/v1 | jq

# Health check
curl -s https://example.com/api/v1/health | jq

# Registered object schema
curl -s https://example.com/api/v1/schema | jq
```

## Public resources

```bash
# Latest published resources
curl -s 'https://example.com/api/v1/resources?limit=5&filter[published][eq]=1&sort=-id&fields=id,pagetitle,uri' | jq

# Single resource
curl -s https://example.com/api/v1/resources/5 | jq

# Page by URI
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content' | jq

# Search
curl -s 'https://example.com/api/v1/resources?q=contact&limit=10' | jq
```

## Filtering

```bash
# Children of resource 2
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=2&filter[published][eq]=1' | jq

# Multiple IDs
curl -s 'https://example.com/api/v1/resources?filter[id][in]=1,5,10' | jq

# Title pattern
curl -s 'https://example.com/api/v1/resources?filter[pagetitle][like]=%News%' | jq
```

## API key authentication

```bash
export MXHEADLESS_API_KEY='mxh_a1b2c3d4_your_secret_here'

curl -s https://example.com/api/v1/resources \
  -H "Authorization: Bearer $MXHEADLESS_API_KEY" | jq
```

`Authorization: Bearer` and `X-API-Key` both accept `mxh_*` keys.

## Create resource (API key)

```bash
curl -s -X POST https://example.com/api/v1/resources \
  -H "Authorization: Bearer $MXHEADLESS_API_KEY" \
  -H 'Content-Type: application/json' \
  -d '{
    "pagetitle": "API created page",
    "parent": 2,
    "template": 1,
    "published": 0
  }' | jq
```

## Session + CSRF (same-origin)

When calling from a browser session that already logged into MODX:

```bash
# Obtain CSRF token from your frontend (MODX session)
CSRF='token-from-modx-session'

curl -s -X PATCH https://example.com/api/v1/resources/5 \
  -H "X-CSRF-Token: $CSRF" \
  -H 'Content-Type: application/json' \
  -b 'PHPSESSID=your_session_id' \
  -d '{"pagetitle":"Updated title"}' | jq
```

## Generic objects (MiniShop3 example)

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[parent][eq]=15&limit=12&sort=price' | jq
```

## Conditional GET (cache)

```bash
ETAG=$(curl -sI https://example.com/api/v1/resources/5 | awk -F': ' '/^[Ee]tag/{print $2}' | tr -d '\r')

curl -s -o /dev/null -w '%{http_code}\n' \
  -H "If-None-Match: $ETAG" \
  https://example.com/api/v1/resources/5
# Expected: 304 when unchanged
```

## Context header

```bash
curl -s https://example.com/api/v1/resources/5 \
  -H 'X-Context: web' | jq
```

## Error inspection

```bash
curl -s 'https://example.com/api/v1/resources?filter[not_allowed][eq]=1' | jq
# 422 with problem+json errors
```

## Related

- [Resources API](../api/resources.md)
- [Authentication](../api/authentication.md)
- [Filtering](../api/filtering.md)
