# mxHeadless

REST API для MODX Revolution 3. JSON-бэкенд для headless-сайтов и приложений.

## Возможности

- REST API на `/api/v1`: discovery, health и schema
- Ресурсы, страницы по URI и зарегистрированные xPDO-объекты
- Фильтры, сортировка, пагинация, выбор полей и связи
- Закрытый доступ по умолчанию: MODX ACL, API keys, CSRF, rate limiting
- Extension API для Extras (MiniShop3, YandexMapsLocator, свои модели)
- OpenAPI и документация в [docs/ru/](docs/ru/index.md) ([EN](docs/index.md))

## Требования

- MODX Revolution 3.2.3+
- PHP 8.1+
- MySQL или MariaDB (xPDO)

## Установка

Установите пакет через Package Manager в Manager. Или соберите transport-пакет:

```bash
cd _build
php build.php
```

Затем загрузите пакет в MODX Manager.

## Быстрый старт

Проверьте, что API отвечает:

```bash
curl -s https://your-site.example/api/v1 | jq
curl -s https://your-site.example/api/v1/health | jq
curl -s 'https://your-site.example/api/v1/resources?limit=5&filter[published]=1' | jq
```

## Документация

[Русская](docs/ru/index.md) · [English](docs/index.md)

## Лицензия

GPL-2.0-or-later
