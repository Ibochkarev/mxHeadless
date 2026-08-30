# Деплой

mxHeadless работает на том же MODX-хосте, где лежит контент. API использует общие PHP, базу и кэш с CMS.

## Требования

- MODX Revolution **3.2.3+**, PHP **8.1+**
- Friendly URLs или fallback `api.php` (см. [веб-сервер](../installation/web-server.md))
- TLS на публичном hostname

Матрица совместимости: [requirements](../installation/requirements.md).

## Установка пакета

1. Соберите или скачайте transport-пакет (`_build/build.php`).
2. Загрузите через **Packages → Install Package** в Manager.
3. Убедитесь, что плагин mxHeadless включён и висит на `OnHandleRequest` (приоритет по умолчанию `-100`).

Dev-установка: symlink `core/components/mxheadless/`, `composer install --no-dev`, namespace `mxheadless`. Подробнее в [installation](../installation/install.md).

## Маршрутизация на MODX

Gateway смотрит на `mxheadless.api.prefix` (по умолчанию `/api`). Все запросы `/api/v1/*` должны попадать в MODX `index.php`.

| Схема | Заметки |
|-------|---------|
| Apache + friendly URLs | Стандартный rewrite MODX. Отдельное правило не нужно, если `/api` уже идёт в `index.php` |
| Nginx | `try_files` на `index.php` для неизвестных путей |
| Без rewrite | `assets/components/mxheadless/api.php/v1/...` |

За балансировщиком добавьте его IP в `mxheadless.trusted_proxies`, чтобы rate limit и audit видели реальный IP клиента. См. [trusted proxies](../configuration/trusted-proxies.md).

## Проверки после деплоя

```bash
curl -s https://your-site.example/api/v1/health | jq
curl -s https://your-site.example/api/v1/meta/endpoints | jq '.meta.count'
```

Ожидайте `health.data.status` = `ok`, когда база отвечает.

## Фоновые workers

При webhooks или ISR revalidation запускайте worker каждую минуту:

```bash
php core/components/mxheadless/bin/webhook-worker.php --limit=50
```

Cron, systemd и audit prune: [workers](workers.md).

## Production-настройки

Перед запуском пройдите [production checklist](production-checklist.md):

- `mxheadless.debug` = `false`
- `mxheadless.enabled` = `true`
- Явные CORS origins, если фронт на другом домене
- Rate limit и cache TTL под ваш CDN
- API keys с минимальными scopes

## Несколько PHP-нод

При общей базе:

- Нужен общий MODX cache backend (для [idempotency](../api/idempotency.md))
- Одинаковая версия пакета на всех нодах
- Один webhook worker (или координация, чтобы outbox не дублировался)

## Откат

1. `mxheadless.enabled` = `false` (discovery и health остаются, остальное отдаёт `503`).
2. Отключите плагин, если gateway должен полностью остановиться.
3. Переустановите предыдущий transport через Package Manager.

Отзовите скомпрометированные ключи до или во время отката. См. [key rotation](key-rotation.md).

## См. также

- [Installation](../installation/install.md)
- [Production checklist](production-checklist.md)
- [Monitoring](monitoring.md)
- [Troubleshooting](troubleshooting.md)
