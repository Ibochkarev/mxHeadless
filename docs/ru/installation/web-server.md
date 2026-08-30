# Настройка веб-сервера

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

- С PATH_INFO: `.../api.php/v1/health`
- Без PATH_INFO (типичный nginx/Herd): `.../api.php?route=/v1/health`

Голый `api.php` — discovery. См. [установку](install.md).
