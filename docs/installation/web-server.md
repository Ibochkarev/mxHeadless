# Web server configuration

## Apache

Enable `mod_rewrite`. MODX friendly URLs must route unknown paths to `index.php`. Gateway prefix defaults to `/api` (`mxheadless_api_prefix`).

## Nginx

```nginx
location / {
    try_files $uri $uri/ /index.php?$args;
}
```

Ensure `/api/v1/*` reaches MODX `index.php`.

## Fallback entry

If rewrite is unavailable, use `assets/components/mxheadless/api.php`.

- With PATH_INFO: `.../api.php/v1/health`
- Without PATH_INFO (typical nginx/Herd): `.../api.php?route=/v1/health`

Bare `api.php` serves discovery. See [install](install.md).
