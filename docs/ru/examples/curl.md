# Примеры cURL

Практические рецепты для mxHeadless v1. Подставьте свой URL вместо `https://example.com`.

## Discovery и health

```bash
# Метаданные API
curl -s https://example.com/api/v1 | jq

# Проверка health
curl -s https://example.com/api/v1/health | jq

# Зарегистрированная схема объектов
curl -s https://example.com/api/v1/schema | jq
```

## Публичные ресурсы

```bash
# Последние опубликованные ресурсы
curl -s 'https://example.com/api/v1/resources?limit=5&filter[published][eq]=1&sort=-id&fields=id,pagetitle,uri' | jq

# Один ресурс
curl -s https://example.com/api/v1/resources/5 | jq

# Страница по URI
curl -s 'https://example.com/api/v1/pages/about.html?fields=id,pagetitle,content' | jq

# Поиск
curl -s 'https://example.com/api/v1/resources?q=contact&limit=10' | jq
```

## Фильтрация

```bash
# Дочерние ресурсы родителя 2
curl -s 'https://example.com/api/v1/resources?filter[parent][eq]=2&filter[published][eq]=1' | jq

# Несколько ID
curl -s 'https://example.com/api/v1/resources?filter[id][in]=1,5,10' | jq

# Паттерн в заголовке
curl -s 'https://example.com/api/v1/resources?filter[pagetitle][like]=%News%' | jq
```

## Аутентификация по API key

```bash
export MXHEADLESS_API_KEY='mxh_a1b2c3d4_your_secret_here'

curl -s https://example.com/api/v1/resources \
  -H "Authorization: Bearer $MXHEADLESS_API_KEY" | jq
```

Заголовки `Authorization: Bearer` и `X-API-Key` принимают ключи `mxh_*`.

## Создание ресурса (API key)

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

## Сессия + CSRF (same-origin)

Когда вызываете API из браузерной сессии, где уже выполнен вход в MODX:

```bash
# CSRF-токен берёте из фронтенда (сессия MODX)
CSRF='token-from-modx-session'

curl -s -X PATCH https://example.com/api/v1/resources/5 \
  -H "X-CSRF-Token: $CSRF" \
  -H 'Content-Type: application/json' \
  -b 'PHPSESSID=your_session_id' \
  -d '{"pagetitle":"Updated title"}' | jq
```

## Generic-объекты (пример MiniShop3)

```bash
curl -s 'https://example.com/api/v1/objects/products?filter[parent][eq]=15&limit=12&sort=price' | jq
```

## Conditional GET (кэш)

```bash
ETAG=$(curl -sI https://example.com/api/v1/resources/5 | awk -F': ' '/^[Ee]tag/{print $2}' | tr -d '\r')

curl -s -o /dev/null -w '%{http_code}\n' \
  -H "If-None-Match: $ETAG" \
  https://example.com/api/v1/resources/5
# Ожидается 304, если ресурс не менялся
```

## Заголовок контекста

```bash
curl -s https://example.com/api/v1/resources/5 \
  -H 'X-Context: web' | jq
```

## Разбор ошибок

```bash
curl -s 'https://example.com/api/v1/resources?filter[not_allowed][eq]=1' | jq
# 422 с ошибками problem+json
```

## См. также

- [Resources API](../api/resources.md)
- [Аутентификация](../api/authentication.md)
- [Фильтрация](../api/filtering.md)
