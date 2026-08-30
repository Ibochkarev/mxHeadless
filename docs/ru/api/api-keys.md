# API keys

Доступ сервер-к-серверу через bearer token. Для браузера удобнее сессия MODX с CSRF.

Короткоживущие `mxt_*` — в [token endpoint](auth.md). Здесь про долгоживущие `mxh_*` keys.

Полный поток в [аутентификации](authentication.md).

## Создание через CLI

```bash
php core/components/mxheadless/bin/api-key-create.php \
  --name="CI build" \
  --scopes="resources.read,contexts.read"
```

Скрипт печатает полный `mxh_…` token один раз.

## Создание в mgr

Право `mxheadless_apikeys` (по умолчанию у Administrator). **Компоненты → mxHeadless**: создание показывает полный `mxh_{lookupId}_{secret}` один раз. Скопируйте сразу. Там же правка scopes и отзыв.

## Хранение

- В БД: lookup ID, user ID, scopes, срок, `password_hash(secret)`, опционально per-key rate limits
- Не коммитьте keys в git и не вставляйте в тикеты
- При смене интеграции сначала новый key, потом отзыв старого

## Scopes

Минимальный набор под задачу:

| Задача | Scopes |
|--------|--------|
| Статическая сборка, только чтение | `resources.read` |
| Headless-редактор | `resources.read`, `resources.create`, `resources.update` |
| Preview pipeline | + `preview` |

## Отзыв

Мгновенный. Кэш по старой identity уйдёт в пределах `mxheadless.cache_ttl`.

## См. также

- [Token endpoint](auth.md)
- [Авторизация](authorization.md)
- [Ротация ключей](../operations/key-rotation.md)
