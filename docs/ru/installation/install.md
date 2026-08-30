# Установка

mxHeadless рассчитан на MODX Revolution **3.2.3+** и PHP **8.1+**.

## Установка пакета

### Через Package Manager

1. Соберите transport-пакет (или скачайте релиз):

   ```bash
   cd _build
   php build.php
   ```

2. В Manager: **Packages → Install Package**, загрузите `.transport.zip`.

3. Завершите установку. mxHeadless зарегистрирует namespace, плагин и системные настройки.

### Вручную (разработка)

Скопируйте или смонтируйте `core/components/mxheadless/` в установку MODX и выполните Composer в компоненте:

```bash
cd core/components/mxheadless
composer install --no-dev --optimize-autoloader
```

Проверьте namespace `mxheadless` в **Система → Пространства имён**.

## HTTP-шлюз

mxHeadless перехватывает запросы до разбора ресурса MODX.

### Основной путь: плагин `OnHandleRequest`

Префикс по умолчанию: `/api`. Запросы к `/api/v1/...` обрабатывает `MxHeadless\Application`.

Настройки (**Системные настройки**, namespace `mxheadless`):

| Настройка | По умолчанию | Назначение |
|-----------|--------------|------------|
| `mxheadless_api_prefix` | `/api` | Префикс URL |
| `mxheadless_allowed_contexts` | `web,mgr` | Разрешённые контексты |
| `mxheadless_max_limit` | `100` | Максимальный limit |
| `mxheadless_debug` | `false` | Подробные ошибки (только dev) |

### Запасной путь: `api.php`

Без ЧПУ. Если веб-сервер отдаёт PATH_INFO:

```
https://your-site.example/assets/components/mxheadless/api.php/v1/health
```

На nginx/Herd (часто без PATH_INFO у вложенных `.php`) используйте query `route`:

```
https://your-site.example/assets/components/mxheadless/api.php?route=/v1/health
https://your-site.example/assets/components/mxheadless/api.php?route=/api/v1/resources&limit=5
```

Голый `api.php` ведёт на discovery (`/api/v1`). Оба входа используют один pipeline middleware и одни сервисы.

## ЧПУ

Включите friendly URLs в MODX. Отдельный документ-ресурс для API не нужен.

За балансировщиком настройте [доверенные прокси](../security.md).

## Проверка

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
curl -s 'https://your-site.example/api/v1/resources?limit=5&filter[published][eq]=1' | jq
```

## CORS (опционально)

По умолчанию выключен. Включайте и задайте origins, если фронтенд на другом домене. Не используйте `*` с credentials.

## Дальше

- [Resources API](../../api/resources.md)
- [Аутентификация](../../api/authentication.md)
- [Чеклист безопасности](../../security.md)
