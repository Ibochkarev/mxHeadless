#!/usr/bin/env python3
"""Generate missing mxHeadless documentation pages (EN + RU).

WARNING: Do not run after manual doc expansion — this overwrites files.
Prefer editing docs/ directly and follow docs/WRITING.md.
"""
from pathlib import Path

ROOT = Path(__file__).resolve().parents[1] / "docs"


def write_pair(rel: str, en: str, ru: str) -> None:
    en_path = ROOT / rel
    ru_path = ROOT / "ru" / rel
    en_path.parent.mkdir(parents=True, exist_ok=True)
    ru_path.parent.mkdir(parents=True, exist_ok=True)
    en_path.write_text(en.strip() + "\n", encoding="utf-8")
    ru_path.write_text(ru.strip() + "\n", encoding="utf-8")


pairs = [
    (
        "installation/requirements.md",
        """# Requirements

## Runtime

- MODX Revolution **3.2.3+** (PHP **8.1+**)
- xPDO **~3.1** on stable MODX; **^3.2** on current `3.x` branch
- MySQL/MariaDB with InnoDB
- Pretty URLs recommended for `/api/v1` gateway

## Optional

- Cron or CLI for webhook retry worker
- HTTPS in production

## Compatibility matrix

| MODX | xPDO | PHP |
|------|------|-----|
| 3.2.3-pl | ~3.1 | 8.1–8.3 |
| 3.x (dev) | ^3.2 | 8.2+ |
""",
        """# Требования

## Среда

- MODX Revolution **3.2.3+** (PHP **8.1+**)
- xPDO **~3.1** на стабильной ветке; **^3.2** на актуальной `3.x`
- MySQL/MariaDB с InnoDB
- Pretty URLs желательны для gateway `/api/v1`

## Опционально

- Cron или CLI для webhook worker
- HTTPS в production

## Матрица совместимости

| MODX | xPDO | PHP |
|------|------|-----|
| 3.2.3-pl | ~3.1 | 8.1–8.3 |
| 3.x (dev) | ^3.2 | 8.2+ |
""",
    ),
    (
        "installation/web-server.md",
        """# Web server configuration

## Apache

Enable `mod_rewrite`. MODX friendly URLs must route unknown paths to `index.php`. Gateway prefix defaults to `/api` (`mxheadless.api.prefix`).

## Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

Ensure `/api/v1/*` reaches MODX `index.php`.

## Fallback entry

If rewrite is unavailable, use `assets/components/mxheadless/api.php`.
""",
        """# Настройка веб-сервера

## Apache

Включите `mod_rewrite`. Friendly URLs MODX должны направлять неизвестные пути в `index.php`. Префикс gateway по умолчанию `/api` (`mxheadless.api.prefix`).

## Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

Путь `/api/v1/*` должен попадать в `index.php` MODX.

## Резервный вход

Без rewrite используйте `assets/components/mxheadless/api.php`.
""",
    ),
    (
        "installation/upgrade.md",
        """# Upgrade

## Package upgrade

1. Back up files and database
2. Remove old `core/components/mxheadless` and `assets/components/mxheadless`
3. Install new transport via Package Manager
4. Run migrations from release notes if any
5. Clear MODX cache

## Breaking changes

Breaking changes never ship inside `/api/v1`. New major API versions use `/api/v2`.
""",
        """# Обновление

## Обновление пакета

1. Сделайте бэкап файлов и БД
2. Удалите старые `core/components/mxheadless` и `assets/components/mxheadless`
3. Установите новый transport через Package Manager
4. Выполните миграции из release notes
5. Очистите кэш MODX

## Breaking changes

Breaking changes не выходят внутри `/api/v1`. Новые major версии API используют `/api/v2`.
""",
    ),
    (
        "installation/uninstall.md",
        """# Uninstall

1. Disable the mxHeadless plugin
2. Uninstall via Package Manager (optionally drop tables)
3. Remove remnants under `core/` and `assets/`
4. Revoke all API keys

Tables `{prefix}mxheadless_api_keys` and `{prefix}mxheadless_webhooks` are removed when the uninstall resolver runs.
""",
        """# Удаление

1. Отключите плагин mxHeadless
2. Удалите через Package Manager (при необходимости удалите таблицы)
3. Удалите остатки в `core/` и `assets/`
4. Отзовите все API keys

Таблицы `{prefix}mxheadless_api_keys` и `{prefix}mxheadless_webhooks` удаляются resolver при uninstall.
""",
    ),
]

# Add more via loop
api_specs = [
    ("discovery.md", "Discovery", "Discovery", "GET `/api/v1`", "API metadata and links."),
    ("health.md", "Health", "Health", "GET `/api/v1/health`", "Liveness probe."),
    ("schema.md", "Schema", "Схема", "GET `/api/v1/schema`", "Registered object definitions."),
    ("pages.md", "Pages by URI", "Страницы по URI", "GET `/api/v1/pages/{uri}`", "Resolve resource by URI."),
    ("objects.md", "Objects", "Объекты", "CRUD `/api/v1/objects/{name}`", "Registered objects only."),
    ("sorting.md", "Sorting", "Сортировка", "`sort=field`", "Whitelisted fields only."),
    ("pagination.md", "Pagination", "Пагинация", "`limit` / `offset`", "Capped by system settings."),
    ("fields.md", "Fields", "Поля", "`fields=`", "Projection-first serialization."),
    ("relations.md", "Relations", "Связи", "`include=`", "Depth and count limits."),
    ("tv.md", "Template variables", "TV", "`tv_fields=`", "On resources/pages."),
    ("media.md", "Media", "Медиа", "Media source URLs", "Absolute URLs in JSON."),
    ("contexts.md", "Contexts", "Контексты", "`X-Context` header", "Allowed contexts whitelist."),
    ("search.md", "Search", "Поиск", "`search=`", "Definition searchable fields."),
    ("mutations.md", "Mutations", "Мутации", "POST/PUT/PATCH/DELETE", "Auth, ACL, CSRF, transactions."),
    ("errors.md", "Errors", "Ошибки", "RFC 9457", "Problem Details JSON."),
    ("api-keys.md", "API keys", "API-ключи", "Bearer `mxh_*`", "Scopes + MODX ACL."),
    ("authorization.md", "Authorization", "Авторизация", "Policy engine", "Deny by default."),
    ("preview.md", "Preview", "Превью", "Unpublished content", "Separate scope required."),
    ("rate-limiting.md", "Rate limiting", "Rate limit", "Per IP/key", "Configurable window."),
    ("idempotency.md", "Idempotency", "Идемпотентность", "`Idempotency-Key`", "Safe POST retries."),
    ("http-caching.md", "HTTP caching", "HTTP-кэш", "ETag / 304", "Public GET only."),
    ("webhooks.md", "Webhooks", "Webhooks", "Outbox + HMAC", "SSRF-safe delivery."),
]

for fname, en_t, ru_t, ep, desc in api_specs:
    pairs.append(
        (
            f"api/{fname}",
            f"# {en_t}\n\n{desc}\n\n**Endpoint:** {ep}\n\nSee [OpenAPI](../openapi.yaml) and [Security](../security.md).\n",
            f"# {ru_t}\n\n{desc}\n\n**Endpoint:** {ep}\n\nСм. [OpenAPI](../openapi.yaml) и [Security](../security.md).\n",
        )
    )

config_pages = [
    (
        "configuration/settings.md",
        """# Configuration reference

| Key | Default | Description |
|-----|---------|-------------|
| `mxheadless.api.prefix` | `/api` | URL prefix |
| `mxheadless.debug` | `false` | Exception details |
| `mxheadless.cache.enabled` | `true` | Response cache |
| `mxheadless.rate_limit.enabled` | `true` | Rate limiting |

Full list in Package Manager → System Settings → namespace `mxheadless`.
""",
        """# Справочник настроек

| Ключ | По умолчанию | Описание |
|------|--------------|----------|
| `mxheadless.api.prefix` | `/api` | Префикс URL |
| `mxheadless.debug` | `false` | Детали исключений |
| `mxheadless.cache.enabled` | `true` | Кэш ответов |
| `mxheadless.rate_limit.enabled` | `true` | Rate limiting |

Полный список: System Settings → namespace `mxheadless`.
""",
    ),
    (
        "configuration/cors.md",
        """# CORS

- `mxheadless.cors.enabled` — master switch
- `mxheadless.cors.allowed_origins` — comma-separated list
- `mxheadless.cors.allow_credentials` — only with explicit origins

OPTIONS preflight is handled by middleware without hitting handlers.
""",
        """# CORS

- `mxheadless.cors.enabled` — главный переключатель
- `mxheadless.cors.allowed_origins` — список origins
- `mxheadless.cors.allow_credentials` — только с явными origins

OPTIONS обрабатывается middleware без вызова handlers.
""",
    ),
    (
        "configuration/trusted-proxies.md",
        """# Trusted proxies

Forwarded headers (`X-Forwarded-For`, `Forwarded`) are trusted only from peers listed in `mxheadless.trusted_proxies`.

Misconfiguration allows IP spoofing for rate limits and audit.
""",
        """# Доверенные прокси

Forwarded headers доверяются только от peer из `mxheadless.trusted_proxies`.

Ошибка конфигурации позволяет подмену IP для rate limit и audit.
""",
    ),
    (
        "configuration/limits.md",
        """# Limits

| Setting | Default |
|---------|---------|
| `mxheadless.max_body_bytes` | 1048576 |
| `mxheadless.max_uri_bytes` | 2048 |
| `mxheadless.max_limit` | 100 |
| `mxheadless.max_offset` | 100000 |
| `mxheadless.max_fields` | 50 |
| `mxheadless.max_include_relations` | 10 |
| `mxheadless.max_include_depth` | 2 |
""",
        """# Лимиты

| Настройка | По умолчанию |
|-----------|--------------|
| `mxheadless.max_body_bytes` | 1048576 |
| `mxheadless.max_uri_bytes` | 2048 |
| `mxheadless.max_limit` | 100 |
| `mxheadless.max_offset` | 100000 |
| `mxheadless.max_fields` | 50 |
| `mxheadless.max_include_relations` | 10 |
| `mxheadless.max_include_depth` | 2 |
""",
    ),
]
pairs.extend(config_pages)

ext_specs = [
    ("objects.md", "Register objects", "Регистрация объектов", "`registerObject()`"),
    ("relations.md", "Relations", "Связи", "`registerRelation()`"),
    ("serializers.md", "Serializers", "Сериализаторы", "`registerSerializer()`"),
    ("filters.md", "Filters", "Фильтры", "`registerFilter()`"),
    ("endpoints.md", "Endpoints", "Endpoints", "`registerEndpoint()`"),
    ("permissions.md", "Permissions", "Права", "`registerPermission()`"),
    ("webhook-events.md", "Webhook events", "События webhook", "Outbox from mutations"),
]
for fname, en_t, ru_t, api in ext_specs:
    pairs.append(
        (
            f"extensions/{fname}",
            f"# {en_t}\n\nExtension API: {api}. Register in bootstrap or `OnMxHeadlessRegister`. See [overview](overview.md).\n",
            f"# {ru_t}\n\nExtension API: {api}. Регистрация в bootstrap или `OnMxHeadlessRegister`. См. [overview](overview.md).\n",
        )
    )

ops_specs = [
    ("deployment.md", "Deployment", "Деплой", "Install package, enable plugin, configure rewrite."),
    ("cache.md", "Cache", "Кэш", "Tag-based invalidation on mutations."),
    ("workers.md", "Workers", "Workers", "Webhook retry via CLI/cron."),
    ("logging.md", "Logging", "Логирование", "PSR-3 to MODX log with redaction."),
    ("monitoring.md", "Monitoring", "Мониторинг", "Health endpoint for probes."),
    ("performance.md", "Performance", "Производительность", "Tune limits and cache TTL."),
    ("key-rotation.md", "Key rotation", "Ротация ключей", "Rotate API secrets before revoke."),
    ("incident-response.md", "Incident response", "Инциденты", "Revoke keys, review logs."),
    ("troubleshooting.md", "Troubleshooting", "Диагностика", "404: plugin/prefix/rewrite. 403: ACL."),
]
for fname, en_t, ru_t, desc in ops_specs:
    pairs.append((f"operations/{fname}", f"# {en_t}\n\n{desc}\n", f"# {ru_t}\n\n{desc}\n"))

examples_specs = [
    ("javascript.md", "fetch(`${base}/resources?limit=10`)"),
    ("typescript.md", "npx openapi-typescript docs/openapi.yaml -o types/mxheadless.ts"),
    ("nextjs.md", "// app/api/modx/route.ts — proxy or direct fetch with ETag"),
    ("sveltekit.md", "// +page.server.ts — event.fetch(base + '/pages' + uri)"),
]
for fname, code in examples_specs:
    title = fname.replace(".md", "").title()
    pairs.append(
        (
            f"examples/{fname}",
            f"# {title}\n\n```javascript\n{code}\n```\n\nSee [curl](curl.md).\n",
            f"# {title}\n\n```javascript\n{code}\n```\n\nСм. [curl](curl.md).\n",
        )
    )

pairs.extend(
    [
        (
            "contributing/testing.md",
            "# Testing\n\n```bash\ncomposer test\ncomposer phpcs\ncomposer phpstan\n```\n",
            "# Тестирование\n\n```bash\ncomposer test\ncomposer phpcs\ncomposer phpstan\n```\n",
        ),
        (
            "contributing/security.md",
            "# Security review\n\nSecurity-sensitive changes require independent review. See [security.md](../security.md).\n",
            "# Security review\n\nИзменения с security-риском требуют независимого review. См. [security.md](../security.md).\n",
        ),
        (
            "contributing/releases.md",
            "# Releases\n\nSemVer for the package. `/api/v1` remains stable within major releases.\n",
            "# Релизы\n\nSemVer для пакета. `/api/v1` стабилен внутри major-релизов.\n",
        ),
    ]
)

# RU copies for existing EN-only pages
existing_ru = {
    "architecture.md": ("# Архитектура mxHeadless", "Полное описание: [architecture.md](../architecture.md) (каноническая EN-версия)."),
    "security.md": ("# Безопасность mxHeadless", "Полное описание: [security.md](../security.md) (каноническая EN-версия)."),
    "api/resources.md": ("# Resources API", "См. [resources.md](../api/resources.md)."),
    "api/authentication.md": ("# Аутентификация", "См. [authentication.md](../api/authentication.md)."),
    "api/filtering.md": ("# Фильтрация", "См. [filtering.md](../api/filtering.md)."),
    "extensions/overview.md": ("# Расширения", "См. [overview.md](../extensions/overview.md)."),
    "extensions/yandex-maps-locator.md": ("# YandexMapsLocator", "См. [yandex-maps-locator.md](../extensions/yandex-maps-locator.md)."),
    "extensions/minishop3.md": ("# MiniShop3", "См. [minishop3.md](../extensions/minishop3.md)."),
    "examples/curl.md": ("# cURL", "См. [curl.md](../examples/curl.md)."),
    "examples/nuxt.md": ("# Nuxt", "См. [nuxt.md](../examples/nuxt.md)."),
    "contributing/development.md": ("# Разработка", "См. [development.md](../contributing/development.md)."),
}

for rel, (h, body) in existing_ru.items():
    ru_path = ROOT / "ru" / rel
    if not ru_path.exists():
        ru_path.parent.mkdir(parents=True, exist_ok=True)
        ru_path.write_text(f"{h}\n\n{body}\n", encoding="utf-8")

for rel, en, ru in pairs:
    write_pair(rel, en, ru)

print(f"Wrote {len(pairs)} documentation pairs")
